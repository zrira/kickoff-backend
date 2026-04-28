<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['playerProfiles' => function($q) {
            $q->where('sport_id', 1); // Default football
        }])->withCount('playerProfiles');
        
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'elo') {
            $query->leftJoin('player_profiles', function($join) {
                $join->on('users.id', '=', 'player_profiles.user_id')
                     ->where('player_profiles.sport_id', 1);
            })
            ->orderBy('player_profiles.elo_points', $sortOrder)
            ->select('users.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->paginate($request->input('per_page', 50));
            
        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        $user->load(['playerProfiles.sport', 'eloHistory' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(20);
        }]);
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|nullable|min:8',
            'city_id' => 'sometimes|exists:cities,id',
            'role' => 'sometimes|string|in:superadmin,admin,player',
        ]);

        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
