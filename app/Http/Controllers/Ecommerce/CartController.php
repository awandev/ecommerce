<?php

namespace App\Http\Controllers\Ecommerce;

use App\City;
use App\Order;
use App\Product;
use App\Customer;
use App\District;
use App\Province;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\OrderDetail;

class CartController extends Controller
{


    private function getCarts()
    {


        $carts = json_decode(request()->cookie('dw-carts'), true);
        $carts = $carts != '' ? $carts : [];
        return $carts;
    }


    public function addToCart(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer'
        ]);

        $carts = $this->getCarts();
        if ($carts && array_key_exists($request->product_id, $carts)) {
            $carts[$request->product_id]['qty'] += $request->qty;
        } else {
            $product = Product::find($request->product_id);
            $carts[$request->product_id] = [
                'qty' => $request->qty,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $product->price,
                'product_image' => $product->image,
                'weight' => $product->weight
            ];
        }

        $cookie = cookie('dw-carts', json_encode($carts), 2880);
        return redirect()->back()->with(['success' => 'Produk Ditambahkan ke Keranjang'])->cookie($cookie);
    }

    public function listCart()
    {
        $carts = $this->getCarts();
        //UBAH ARRAY MENJADI COLLECTION, KEMUDIAN GUNAKAN METHOD SUM UNTUK MENGHITUNG SUBTOTAL
        $subtotal = collect($carts)->sum(function ($q) {
            return $q['qty'] * $q['product_price']; //SUBTOTAL TERDIRI DARI QTY * PRICE
        });
        //LOAD VIEW CART.BLADE.PHP DAN PASSING DATA CARTS DAN SUBTOTAL
        return view('ecommerce.cart', compact('carts', 'subtotal'));
    }




    public function updateCart(Request $request)
    {
        // ambil data dari cookie
        // $carts = $this->getCarts();
        //AMBIL DATA DARI COOKIE
        $carts = json_decode(request()->cookie('dw-carts'), true);
        //KEMUDIAN LOOPING DATA PRODUCT_ID, KARENA NAMENYA ARRAY PADA VIEW SEBELUMNYA
        //MAKA DATA YANG DITERIMA ADALAH ARRAY SEHINGGA BISA DI-LOOPING
        foreach ($request->product_id as $key => $row) {
            //DI CHECK, JIKA QTY DENGAN KEY YANG SAMA DENGAN PRODUCT_ID = 0
            if ($request->qty[$key] == 0) {
                //MAKA DATA TERSEBUT DIHAPUS DARI ARRAY
                unset($carts[$row]);
            } else {
                //SELAIN ITU MAKA AKAN DIPERBAHARUI
                $carts[$row]['qty'] = $request->qty[$key];
            }
        }
        //SET KEMBALI COOKIE-NYA SEPERTI SEBELUMNYA
        $cookie = cookie('dw-carts', json_encode($carts), 2880);
        //DAN STORE KE BROWSER.
        return redirect()->back()->cookie($cookie);
    }



    public function checkout()
    {
        $provinces = Province::orderBy('created_at', 'DESC')->get();
        $carts = $this->getCarts();
        $subtotal = collect($carts)->sum(function ($q) {
            return $q['qty'] * $q['product_price'];
        });
        return view('ecommerce.checkout', compact('provinces', 'carts', 'subtotal'));
    }

    public function processCheckout(Request $request)
    {
        // validasi datanya
        $this->validate($request, [
            'customer_name'     =>  'required|string|max:100',
            'customer_phone'    =>  'required',
            'email'             =>  'required|email',
            'customer_address'  =>  'required|string',
            'province_id'       =>  'required|exists:provinces,id',
            'city_id'           =>  'required|exists:cities,id',
            'district_id'       =>  'required|exists:districts,id'
        ]);

        // inisiasi database transaction
        // database transaction berfungsi untuk memastikan semua proses sukses 
        // untuk kemudian di commit agar data benar benar disimpan.
        // jika terjadi error , maka kita rollback agar datanya selaras
        DB::beginTransaction();
        try {
            // check data customer berdasarkan email
            $customer = Customer::where('email', $request->email)->first();
            // jika dia tidak login dan data customernya ada
            if (!auth()->check() && $customer) {
                // maka redirect dan tampilkan instruksi untuk login
                return redirect()->back()->with(['error' => 'Silahkan Login Terlebih Dahulu']);
            }

            // ambil data keranjang
            $carts = $this->getCarts();

            // hitung subtotal belanjaan
            $subtotal = collect($carts)->sum(function ($q) {
                return $q['qty'] * $q['product_price'];
            });

            // simpan data customer baru
            $customer = Customer::create([
                'name'          => $request->customer_name,
                'email'         => $request->email,
                'phone_number'  => $request->customer_phone,
                'address'       => $request->customer_address,
                'district_id'   => $request->district_id,
                'status'        => false
            ]);

            // simpan data order
            $order = Order::create([
                'invoice'           => Str::random(4) . '-' . time(), //invoicenya kita buat dari string random dan waktu
                'customer_id'       => $customer->id,
                'customer_name'     => $customer->name,
                'customer_phone'    => $request->customer_phone,
                'customer_address'  => $request->customer_address,
                'district_id'       => $request->district_id,
                'subtotal'          => $subtotal
            ]);

            // looping data di carts
            foreach ($carts as $row) {
                // ambil data produk berdasarkan product_id
                $product = Product::find($row['product_id']);

                // simpan detail order
                OrderDetail::create([
                    'order_id'      => $order->id,
                    'product_id'    => $row['product_id'],
                    'price'         => $row['product_price'],
                    'qty'           => $row['qty'],
                    'weight'        => $product->weight
                ]);
            }


            // tidak terjadi error, maka commit datanya untuk menginformasikan bahwa data sudah fix
            DB::commit();

            $carts = [];

            // kosongkan data keranjang cookies
            $cookie = cookie('dw-carts', json_encode($carts), 2880);

            // redirect ke halaman finish transaksi
            return redirect(route('front.finish_checkout', $order->invoice))->cookie($cookie);
        } catch (\Exception $e) {
            // jika terjadi error, maka rollback datanya
            DB::rollback();
            // dan kembali ke form transaksi serta menampilkan error
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function checkoutFinish($invoice)
    {
        // ambil data pesanan berdasarkan invoice
        $order = Order::with(['district.city'])->where('invoice', $invoice)->first();

        // load view checkout_finish.blade.php dan passing data order
        return view('ecommerce.checkout_finish', compact('order'));
    }



    public function getCity()
    {
        // query untuk mengambil data kota / kabupaten berdasarkan province_id
        $cities = City::where('province_id', request()->province_id)->get();
        // kembalikan datanya dalam bentuk json
        return response()->json(['status' => 'success', 'data' => $cities]);
    }

    public function getDistrict()
    {

        $districts = District::where('city_id', request()->city_id)->get();
        return response()->json(['status' => 'success', 'data' => $districts]);
    }
}
