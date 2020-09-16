<?php

namespace App\Http\Controllers;

use App\Order;
use App\Mail\OrderMail;
use Mail;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function index()
    {
        // query untuk mengambil semua pesanan dan load data yang berelasi menggunakan eager loading
        // dan urutkan berdasarkan created_at
        $orders = Order::with(['customer.district.city.province'])
            ->withCount('return')
            ->orderBy('created_at', 'DESC');

        // jika Q untuk pencarian tidak kosong
        if (request()->q != '') {
            // maka dibuat query untuk mencari data berdasarkan nama, invoice dan alamat
            $orders = $orders->where(function ($q) {
                $q->where('customer_name', 'LIKE', '%' . request()->q . '%')
                    ->orWhere('invoice', 'LIKE', '%' . request()->q . '%')
                    ->orWhere('customer_address', 'LIKE', '%' . request()->q . '%');
            });
        }

        // jika status tidak kosong
        if (request()->status != '') {
            // maka data difilter berdasarkan status
            $orders = $orders->where('status', request()->status);
        }

        $orders = $orders->paginate(10); //load data per 10 data
        return view('orders.index', compact('orders')); //load view index dan passing data tersebut
    }

    public function destroy($id)
    {
        $order = Order::find($id);
        $order->details()->delete();
        $order->payment()->delete();
        $order->delete();
        return redirect(route('orders.index'));
    }

    public function view($invoice)
    {
        $order = Order::with(['customer.district.city.province', 'payment', 'details.product'])->where('invoice', $invoice)->first();
        return view('orders.view', compact('order'));
    }

    public function acceptPayment($invoice)
    {
        // mengambil data customer berdasarkan invoice
        $order = Order::with(['payment'])->where('invoice', $invoice)->first();
        // ubaha status di table payment melalui order yang terkait
        $order->payment()->update(['status' => 1]);
        // ubah status order menjadi proses
        $order->update(['status' => 2]);
        // redirect ke halaman yang sama
        return redirect(route('orders.view', $order->invoice));
    }

    public function shippingOrder(Request $request)
    {
        // mengambil data order berdasarkan ID
        $order = Order::with(['customer'])->find($request->order_id);
        // update data order dengan memasukkan nomor resi dan mengubah status menjadi dikirim
        $order->update(['tracking_number' => $request->tracking_number, 'status' => 3]);
        // kirim email ke pelanggan terkait
        Mail::to($order->customer->email)->send(new OrderMail($order));
        // redirect kembali
        return redirect()->back();
    }

    public function return($invoice)
    {
        $order = Order::with(['return', 'customer'])->where('invoice', $invoice)->first();
        return view('orders.return', compact('order'));
    }

    public function approveReturn(Request $request)
    {
        $this->validate($request, ['status' => 'required']); //validasi status
        $order = Order::find($request->order_id); //query berdasarkan order_id
        $order->return()->update(['status' => $request->status]); // update status yang ada di table order_returns melalui order
        $order->update(['status' => 4]); // update status yang ada di table orders
        return redirect()->back();
    }
}
