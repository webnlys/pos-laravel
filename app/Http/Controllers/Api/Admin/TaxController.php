<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use App\Repositories\TaxRepository;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function __construct(private TaxRepository $taxes) {}

    public function index(Request $request)
    {
        return TaxResource::collection($this->taxes->paginate($request, 'effective_from', 'desc'));
    }

    public function store(TaxRequest $request)
    {
        return new TaxResource(Tax::query()->create($request->validated()));
    }

    public function show(Tax $tax)
    {
        return new TaxResource($tax);
    }

    public function update(TaxRequest $request, Tax $tax)
    {
        $tax->update($request->validated());

        return new TaxResource($tax);
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
