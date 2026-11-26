<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;

/** Web views for product listing and details (UC02.04 – UC02.07). */
class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('products.index');
    }

    public function show(Product $product, ProductService $products): View
    {
        $this->authorize('view', $product);

        return view('products.show', ['product' => $products->getDetail($product->getKey())]);
    }

    public function stockCard(Product $product): View
    {
        $this->authorize('view', $product);

        return view('products.stock-card', compact('product'));
    }
}
