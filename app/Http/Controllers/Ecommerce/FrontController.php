<?php

namespace App\Http\Controllers\Ecommerce;

use App\Category;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

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
        // $categories = Category::with(['child'])->withCount(['child'])->getParent()->orderBy('name', 'ASC')->get();
        // load ke view product.blade.php dan passing kedua data di atas

        // untuk kategori , otomatis ter-load karena sudah dibuatkan view di file categoryComposer, dan di load di appserviceprovider
        return view('ecommerce.product', compact('products'));
    }

    public function categoryProduct($slug)
    {
        // jadi querynya adalah kita cari dulu kategori berdasarkan slug, setelah datanya ditemukan
        // maka slug akan mengambil data product yang berelasi menggunakan method product() 
        // yang telah didefinisikan pada file category.php serta diurutkan berdasarkan created_at
        // dan diload 12 data per sekali load
        $products = Category::where('slug', $slug)->first()->product()->orderBy('created_at', 'DESC')->paginate(12);
        // load view yang sama yakni product.blade.php karena tampilannya akan kita sama
        return view('ecommerce.product', compact('products'));
    }


    public function show($slug)
    {
        // query untuk mengambil single data berdasarkan slug-nya
        $product = Product::with(['category'])->where('slug', $slug)->first();

        // load view show.blade.php dan passing data product
        return view('ecommerce.show', compact('product'));
    }

    public function verifyCustomerRegistration($token)
    {
        // jadi kita buat query untuk mengambil data user berdasarkan token yang diterima
        $customer = Customer::where('activate_token', $token)->first();
        if ($customer) {
            // jika ada , maka datanya diupdate dengan mengosongkan tokennya dan statusnya jadi aktif
            $customer->update([
                'activate_token' => null,
                'status' => 1
            ]);

            // redirect ke halaman login dengan mengirimkan flash session success
            return redirect(route('customer.login'))->with(['success' => 'Verifikasi Berhasil, Silahkan Login']);
        }

        // jika tidak ada , maka redirect ke halaman login
        // dengan mengirimkan flash session error
        return redirect(route('customer.login'))->with(['error' => 'Invalid Verifikasi Token']);
    }
}
