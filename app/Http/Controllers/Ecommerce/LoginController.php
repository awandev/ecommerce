<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{



    public function loginForm()
    {
        if (auth()->guard('customer')->check()) return redirect(route('customer.dashboard'));
        return view('ecommerce.login');
    }
    public function login(Request $request)
    {
        // validasi data yang diterima
        $this->validate($request, [
            'email'     => 'required|email|exists:customers,email',
            'password'  => 'required|string',
        ]);

        // cukup mengambil email dan password saja dari request
        // karena juga di sertakan token
        $auth = $request->only('email', 'password');
        $auth['status'] = 1; //tambahkan juga status yang bisa login harus 1

        // check untuk proses otentikasi
        // dari guard customer, kita attempt proses dari data $auth 
        if (auth()->guard('customer')->attempt($auth)) {
            //JIKA BERHASIL MAKA AKAN DIREDIRECT KE DASHBOARD
            return redirect()->intended(route('customer.dashboard'));
        }
        //JIKA GAGAL MAKA REDIRECT KEMBALI BERSERTA NOTIFIKASI
        return redirect()->back()->with(['error' => 'Email / Password Salah']);
    }


    public function dashboard()
    {
        return view('ecommerce.dashboard');
    }

    public function logout()
    {
        auth()->guard('customer')->logout();
        return redirect(route('customer.login'));
    }
}
