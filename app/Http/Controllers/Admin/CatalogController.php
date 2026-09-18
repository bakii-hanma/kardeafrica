<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductApiService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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

        // Paginator réel (au lieu de rendre la pagination à la main dans le
        // blade) : la vue admin par défaut (`vendor.pagination.kardafrica`,
        // design « Layered ») s'applique automatiquement comme sur /admin/orders.
        $productsPaginator = new LengthAwarePaginator(
            $products,
            (int) $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $categories = $this->productApi->getCategories();

        return view('admin.catalog.index', compact(
            'products', 'productsPaginator', 'total', 'page', 'lastPage', 'perPage',
            'search', 'category', 'categories'
        ));
    }
}
