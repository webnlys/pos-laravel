<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductRepository $products) {}

    public function index(Request $request)
    {
        return ProductResource::collection($this->products->paginate($request, 'name', 'asc'));
    }

    public function store(ProductRequest $request)
    {
        $product = Product::query()->create($request->validated());

        return new ProductResource($product);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        unset($data['stock_qty']);
        $product->update($data);

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
