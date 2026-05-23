<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductApiService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    protected ProductApiService $productApi;

    public function __construct(ProductApiService $productApi)
    {
        $this->productApi = $productApi;
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $category = $request->input('category', '');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 24;

        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($category) $filters['category'] = (int) $category;

        $result = $this->productApi->getFilteredProducts($filters, $page, $perPage);

        $products = $result['items'] ?? [];
        $total = $result['total'] ?? 0;
        $lastPage = $result['last_page'] ?? 1;

        $categories = $this->productApi->getCategories();

        return view('admin.catalog.index', compact(
            'products', 'total', 'page', 'lastPage', 'perPage',
            'search', 'category', 'categories'
        ));
    }
}
