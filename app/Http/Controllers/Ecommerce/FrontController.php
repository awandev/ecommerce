<?php

namespace App\Http\Controllers\Ecommerce;

use App\Category;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function index()
    {
        // membuat query untuk mengambil data produk yang diurutkan berdasarkan tanggal terbaru
        // dan di load 10 data per pagenya
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);
        // load view index.blade.php dan passing data dari variabel products
        return view('ecommerce.index', compact('products'));
    }

    public function product()
    {
        // buat query untuk mengambil data produk, load per pagenya 12 agar presisi pada halaman tersebut karena dalam sebaris memuat 4 buat produk
        $products = Product::orderBy('created_at', 'DESC')->paginate(12);
        // load juga data kategori yang akan ditampilkan pada sidebar
        $categories = Category::with(['child'])->withCount(['child'])->getParent()->orderBy('name', 'ASC')->get();
        // load ke view product.blade.php dan passing kedua data di atas
        return view('ecommerce.product', compact('products', 'categories'));
    }
}
