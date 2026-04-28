<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VenueResource;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * List venues, filterable by city_id and sport_id.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Venue::active()->with('city');

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }

        $venues = $query->orderBy('name')->get();

        return response()->json([
            'data' => VenueResource::collection($venues),
        ]);
    }
}
