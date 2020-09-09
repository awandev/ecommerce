<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Order;
use Carbon\Carbon;
use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        // query untuk mengambil data order berdasarkan customer yang sedang login dengan load 10 data per-page.
        $orders = Order::where('customer_id', auth()->guard('customer')->user()->id)->orderBy('created_at', 'DESC')->paginate(10);
        return view('ecommerce.orders.index', compact('orders'));
    }

    public function view($invoice)
    {
        $order = Order::with(['district.city.province', 'details', 'details.product', 'payment'])
            ->where('invoice', $invoice)->first();
        return view('ecommerce.orders.view', compact('order'));
    }

    public function paymentForm()
    {
        return view('ecommerce.payment');
    }

    public function storePayment(Request $request)
    {
        // validasi datanya 
        $this->validate($request, [
            'invoice'       => 'required|exists:orders, invoice',
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
}
