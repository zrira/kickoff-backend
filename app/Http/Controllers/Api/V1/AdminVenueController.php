<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Http\Resources\VenueResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Carbon;
use App\Models\Game;

class AdminVenueController extends Controller
{
    /**
     * Get matches for a venue by date range.
     */
    public function schedule(Request $request, Venue $venue)
    {
        $startDate = $request->input('date', now()->toDateString());
        $days = (int) $request->input('days', 1);
        
        $startOfDay = Carbon::parse($startDate)->startOfDay();
        $endOfDay = Carbon::parse($startDate)->addDays($days - 1)->endOfDay();

        $matches = Game::where('venue_id', $venue->id)
            ->whereBetween('scheduled_at', [$startOfDay, $endOfDay])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => $matches->map(fn($m) => [
                'id' => $m->id,
                'status' => $m->status,
                'scheduled_at' => $m->scheduled_at->toISOString(),
                'sport' => $m->sport?->name,
            ])
        ]);
    }

    public function index(Request $request)
    {
        $query = Venue::with('city');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
        }

        $venues = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));
            
        return VenueResource::collection($venues);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:5', // Max 5 images
            'images.*' => 'image|max:2048', // Max 2MB per image
        ]);

        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('venues', 'public');
                $imageUrls[] = Storage::url($path);
            }
        }
        $validated['image_urls'] = $imageUrls;

        $venue = Venue::create($validated);
        $venue->load('city');

        return new VenueResource($venue);
    }

    public function show(Venue $venue)
    {
        $venue->load('city');
        return new VenueResource($venue);
    }

    public function update(Request $request, Venue $venue)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'city_id' => 'sometimes|exists:cities,id',
            'address' => 'sometimes|string',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:2048',
        ]);

        if ($request->hasFile('images')) {
            // Delete old images if replacing
            if ($venue->image_urls) {
                foreach ($venue->image_urls as $oldUrl) {
                    $oldPath = str_replace('/storage/', '', $oldUrl);
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('venues', 'public');
                $imageUrls[] = Storage::url($path);
            }
            $validated['image_urls'] = $imageUrls;
        }

        $venue->update($validated);
        $venue->load('city');

        return new VenueResource($venue);
    }

    public function destroy(Venue $venue)
    {
        $venue->delete();
        return response()->json(null, 204);
    }
}
