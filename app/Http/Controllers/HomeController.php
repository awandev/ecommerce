<?php

namespace App\Http\Controllers;

use App\Order;
use Carbon\Carbon;
use PDF;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function orderReport()
    {
        // inisiasi 30 hari range saat ini jika halaman pertama kali di-load
        // kita gunakan startofmonth untuk mengambil tanggal 1   
        $start = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        // dan endofmonth untuk mengambil tanggal terakhir di bulan yang berlaku saat ini
        $end = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');

        // jika user melakukan filter manual, maka parameter date akan terisi
        if (request()->date != '') {
            // maka formatting tanggalnya berdasarkan filter user
            $date = explode(' - ', request()->date);
            $start = Carbon::parse($date[0])->format('Y-m-d') . ' 00:00:01';
            $end = Carbon::parse($date[1])->format('Y-m-d') . ' 23:59:59';
        }

        // buat query ke DB menggunakan wherebetween dari tanggal filter
        $orders = Order::with(['customer.district'])->whereBetween('created_at', [$start, $end])->get();
        // kemudian load view
        return view('report.order', compact('orders'));
    }

    public function orderReportPdf($daterange)
    {
        $date = explode('+', $daterange); //explode tanggalnya untuk memisahkan start dan end   
        // definisikan variabelnya dengan format timestamps
        $start = Carbon::parse($date[0])->format('Y-m-d') . ' 00:00:01';
        $end   = Carbon::parse($date[1])->format('Y-m-d') . ' 23:59:59';

        // kemudian buat query berdasarkan range created_at yang telah ditetapkan rangenya dari $start ke $end
        $orders = Order::with(['customer.district'])->whereBetween('created_at', [$start, $end])->get();
        // load view untuk pdf nya dengan mengirimkan data dari hasil query
        $pdf = PDF::loadView('report.order_pdf', compact('orders', 'date'));

        // generate PDF nya
        return $pdf->stream();
    }


    public function returnReport()
    {
        $start = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $end   = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');

        if (request()->date != '') {
            $date = explode(' - ', request()->date);
            $start = Carbon::parse($date[0])->format('Y-m-d') . ' 00:00:01';
            $end = Carbon::parse($date[1])->format('Y-m-d') . ' 23:59:59';
        }

        $orders = Order::with(['customer.district'])->has('return')->whereBetween('created_at', [$start, $end])->get();
        return view('report.return', compact('orders'));
    }

    public function returnReportPdf($daterange)
    {
        $date = explode('+', $daterange);
        $start = Carbon::parse($date[0])->format('Y-m-d') . ' 00:00:01';
        $end   = Carbon::parse($date[1])->format('Y-m-d') . ' 23:59:59';

        $orders = Order::with(['customer.district'])->has('return')->whereBetween('created_at', [$start, $end])->get();
        $pdf = PDF::loadView('report.return_pdf', compact('orders', 'date'));
        return $pdf->stream();
    }
}
