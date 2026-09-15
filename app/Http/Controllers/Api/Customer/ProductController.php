<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductRepository $products) {}

    public function index(Request $request)
    {
        $request->merge(['per_page' => $request->integer('per_page', 50)]);

        $paginator = $this->products->filtered($request)
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return ProductResource::collection($paginator);
    }
}
