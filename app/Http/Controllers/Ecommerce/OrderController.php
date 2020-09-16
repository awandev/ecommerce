<?php

namespace App\Http\Controllers\Ecommerce;

use PDF;
use App\Order;
use App\Payment;
use Carbon\Carbon;
use App\OrderReturn;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index()
    {
        // query untuk mengambil data order berdasarkan customer yang sedang login dengan load 10 data per-page.
        $orders = Order::withCount(['return'])->where('customer_id', auth()->guard('customer')->user()->id)->orderBy('created_at', 'DESC')->paginate(10);
        return view('ecommerce.orders.index', compact('orders'));
    }

    public function view($invoice)
    {
        $order = Order::with(['district.city.province', 'details', 'details.product', 'payment'])
            ->where('invoice', $invoice)->first();

        // jadi kita cek, value forUser() nya adalah customer yang sedang login
        // dan allownya meminta dua parameter
        // pertama adalah nama gate yang dibuat sebelumnya, dan yang kedua adalah data order dari query di atas
        if (\Gate::forUser(auth()->guard('customer')->user())->allows('order-view', $order)) {
            // jika hasilnya true, maka kita tampilkan datanya
            return view('ecommerce.orders.view', compact('order'));
        }

        // jika false, maka redirect ke halaman yang diinginkan
        return redirect(route('customer.orders'))->with(['error' => 'Anda tidak diizinkan untuk mengakses order orang lain']);
    }

    public function paymentForm()
    {
        return view('ecommerce.payment');
    }

    public function storePayment(Request $request)
    {
        // validasi datanya 
        $this->validate($request, [
            'invoice'       => 'required|exists:orders,invoice',
            'name'          => 'required|string',
            'transfer_to'   => 'required|string',
            'transfer_date' => 'required',
            'amount'        => 'required|integer',
            'proof'         => 'required|image|mimes:jpg,png,jpeg',
        ]);

        // define database transaction untuk menghindari kesalahan sinkronisasi data jika terjadi error di tengah proses query
        DB::beginTransaction();
        try {
            // ambil data order berdasarkan invoice id
            $order = Order::where('invoice', $request->invoice)->first();
            if ($order->subtotal != $request->amount) return redirect()->back()->with(['error' => 'Error, Pembayaran harus sama dengan tagihan']);
            // jika statusnya masih 0 dan ada file bukti transfer yang dikirim
            if ($order->status == 0 && $request->hasFile('proof')) {
                // upload file gambar tersebut
                $file = $request->file('proof');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/payment', $filename);

                // kemudian simpan informasi pembayarannya
                Payment::create([
                    'order_id'      => $order->id,
                    'name'          => $request->name,
                    'transfer_to'   => $request->transfer_to,
                    'transfer_date' => Carbon::parse($request->transfer_date)->format('Y-m-d'),
                    'amount'        => $request->amount,
                    'proof'         => $filename,
                    'status'        => false
                ]);

                // dan ganti status order menjadi 1
                $order->update(['status' => 1]);

                // jika tidak error, maka commit untuk menandakan bahwa transaksi berhasil
                DB::commit();

                // redirect dan kirimkan pesan
                return redirect()->back()->with(['success' => 'Pesanan Dikonfirmasi']);
            }

            // redirect dengan error message
            return redirect()->back()->with(['error' => 'Error, Upload Bukti Transfer']);
        } catch (\Exception $e) {
            // jika terjadi error, maka rollback seluruh proses query
            DB::rollback();
            // dan kirimkan pesan error
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }


    public function pdf($invoice)
    {
        //GET DATA ORDER BERDASRKAN INVOICE
        $order = Order::with(['district.city.province', 'details', 'details.product', 'payment'])
            ->where('invoice', $invoice)->first();
        //MENCEGAH DIRECT AKSES OLEH USER, SEHINGGA HANYA PEMILIKINYA YANG BISA MELIHAT FAKTURNYA
        if (!\Gate::forUser(auth()->guard('customer')->user())->allows('order-view', $order)) {
            return redirect(route('customer.view_order', $order->invoice));
        }

        //JIKA DIA ADALAH PEMILIKNYA, MAKA LOAD VIEW BERIKUT DAN PASSING DATA ORDERS
        $pdf = PDF::loadView('ecommerce.orders.pdf', compact('order'));
        //KEMUDIAN BUKA FILE PDFNYA DI BROWSER
        return $pdf->stream();
    }

    public function acceptOrder(Request $request)
    {
        // cari data order berdasarkan id
        $order = Order::find($request->order_id);
        // validasi kepemilikan
        if (!\Gate::forUser(auth()->guard('customer')->user())->allows('order-view', $order)) {
            return redirect()->back()->with(['error' => 'Bukan Pesanan Anda']);
        }

        // ubah statusnya menjadi 4
        $order->update(['status' => 4]);
        // redirect kembali dengan menampilkan alert success
        return redirect()->back()->with(['success' => 'Pesanan Dikonfirmasi']);
    }


    public function returnForm($invoice)
    {
        // load data berdasarkan invoice
        $order = Order::where('invoice', $invoice)->first();
        // load view return.blade.php dan passing data order
        return view('ecommerce.orders.return', compact('order'));
    }

    public function processReturn(Request $request, $id)
    {
        // lakukan validasi data
        $this->validate($request, [
            'reason'         => 'required|string',
            'refund_transfer' => 'required|string',
            'photo'          => 'required|image|mimes:jpg,png,jpeg'
        ]);

        // cari data return berdasarkan order_id yang ada di table order_returns nantinya
        $return = OrderReturn::where('order_id', $id)->first();

        // jika ditemukan , maka tampilkan notifikasi 
        if ($return) return redirect()->back()->with(['error' => 'Permintaan Refund dalam proses']);

        // jika tidak, lakukan pengecekan untuk memastikan file photo dikirimkan
        if ($request->hasFile('photo')) {
            // get file 
            $file = $request->file('photo');
            // generate nama file berdasarkan time dan string random
            $filename = time() . Str::random(5) . '.' . $file->getClientOriginalExtension();

            // lalu upload ke dalam folder storage/app/public/return
            $file->storeAs('public/return', $filename);

            // simpan informasinya di tabel order_return
            OrderReturn::create([
                'order_id'      => $id,
                'photo'         => $filename,
                'reason'        => $request->reason,
                'refund_transfer' => $request->refund_transfer,
                'status'        => 0
            ]);

            // lalu tampilkan notifikasi
            return redirect()->back()->with(['success' => 'Permintaan Refund Dikirim']);
        }
    }
}
