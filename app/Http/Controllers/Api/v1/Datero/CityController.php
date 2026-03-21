<?php

namespace App\Http\Controllers\Api\v1\Datero;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cities = City::query()
            ->where('is_active', true)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = (string) $request->input('search');
                $query->where(function ($nestedQuery) use ($term) {
                    $nestedQuery->where('name', 'like', '%'.$term.'%')
                        ->orWhere('department', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $cities->map(fn (City $city): array => [
                'id' => $city->id,
                'name' => $city->name,
                'department' => $city->department,
                'code' => $city->code,
            ])->all(),
        ]);
    }
}
