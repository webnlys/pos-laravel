<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\UnitService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepository $products,
        private UnitService $units,
    ) {}

    public function index(Request $request)
    {
        return ProductResource::collection($this->products->paginate($request, 'name', 'asc', ['unit']));
    }

    public function store(ProductRequest $request)
    {
        $data = $this->payload($request);
        $product = Product::query()->create($data)->load('unit');

        return new ProductResource($product);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load('unit'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $this->payload($request);
        unset($data['stock_qty']);
        $product->update($data);

        return new ProductResource($product->fresh('unit'));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ProductRequest $request): array
    {
        $data = $request->validated();
        $unit = $this->units->resolve(
            isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            $data['unit_name'] ?? null,
        );
        unset($data['unit_name']);
        $data['unit_id'] = $unit?->id;

        return $data;
    }
}
