<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Repositories\PurchaseRepository;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseRepository $purchases,
        private PurchaseService $service,
    ) {}

    public function index(Request $request)
    {
        return PurchaseResource::collection($this->purchases->paginate($request));
    }

    public function store(DocumentRequest $request)
    {
        return new PurchaseResource($this->service->create($request->validated(), $request->user()->id));
    }

    public function show(Purchase $purchase)
    {
        return new PurchaseResource($purchase->load(['supplier', 'items']));
    }

    public function update(DocumentRequest $request, Purchase $purchase)
    {
        return new PurchaseResource($this->service->update($purchase, $request->validated()));
    }

    public function destroy(Purchase $purchase)
    {
        $this->service->delete($purchase);

        return response()->json(['message' => 'Deleted']);
    }
}
