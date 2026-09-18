<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Repositories\UnitRepository;
use App\Services\UnitService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        private UnitRepository $units,
        private UnitService $service,
    ) {}

    public function index(Request $request)
    {
        return UnitResource::collection($this->units->paginate($request, 'name', 'asc'));
    }

    public function store(UnitRequest $request)
    {
        $unit = $this->service->resolve(null, $request->validated()['name']);

        return (new UnitResource($unit))->response()->setStatusCode(201);
    }

    public function show(Unit $unit)
    {
        return new UnitResource($unit);
    }

    public function update(UnitRequest $request, Unit $unit)
    {
        $unit->update($request->validated());

        return new UnitResource($unit);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
