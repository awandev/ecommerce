<?php

namespace App\Http\Controllers\Ecommerce;

use App\Order;
use App\Product;
use App\Category;
use App\Customer;
use App\Province;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
            // jika ada,maka datanya diupdate dengan mengosongkan tokennya dan statusnya jadi aktif
            $customer->update([
                'activate_token'    => null,
                'status'            => 1
            ]);

            // redirect ke halaman login dengan mengirimkan flash session success
            return redirect(route('customer.login'))->with(['success' => 'Verifikasi Berhasial, Silahkan Login']);
        }

        // jika tidak ada, maka redirect ke halaman login
        // dengan mengirimkan flash session error
        return redirect(route('customer.login'))->with(['error' => 'Invalid Verifikasi Token']);
    }


    public function customerSettingForm()
    {
        // mengambil data customer yang sedang login
        $customer = auth()->guard('customer')->user()->load('district');

        // get data provinsi untuk ditampilkan pada select box
        $provinces = Province::orderBy('name', 'ASC')->get();

        // load view setting.blade.php dan passing data customer
        return view('ecommerce.setting', compact('customer', 'provinces'));
    }

    public function customerUpdateProfile(Request $request)
    {
        // validasi data yang dikirim
        $this->validate($request, [
            'name'          => 'required|string|max:100',
            'phone_number'  => 'required|max:15',
            'address'       => 'required|string',
            'district_id'   => 'required|exists:districts,id',
            'password'      => 'nullable|string|min:6'
        ]);

        // ambil data customer yang sedang login
        $user = auth()->guard('customer')->user();

        // ambil data yang dikirim dari form
        // tapi hanya 4 column saja sesuai yang ada di bawah
        $data = $request->only('name', 'phone_number', 'address', 'district_id');

        // adapun password kita cek dulu, jika tidak kosong
        if ($request->password != '') {
            // maka tambahkan ke dalam array
            $data['password'] = $request->password;
        }

        // terus update datanya
        $user->update($data);
        // dan redirect kembali dengan mengirimkan pesan berhasil
        return redirect()->back()->with(['success' => 'Profil Berhasil Diperbaharui']);
    }


    public function referalProduct($user, $product)
    {
        $code = $user . '-' . $product; //kita merge userid dan productid
        $product = Product::find($product); //find product berdasarkan productID
        $cookie = cookie('awan-afiliasi', json_encode($code, 2880)); //buat cookie dengan nama awan-afiliasi dan valuenya adalah code yang sudah di merge
        return redirect(route('front.show_product', $product->slug))->cookie($cookie);
    }


    public function listCommission()
    {
        $user = auth()->guard('customer')->user(); //ambil data user yang login
        // query berdasarkan ID user dari data ref yang ada diorder dengan status 4 atau selesai
        $orders = Order::where('ref', $user->id)->where('status', 4)->paginate(10);
        // load view affiliate.blade.php dan passing data orders
        return view('ecommerce.affiliate', compact('orders'));
    }
}
