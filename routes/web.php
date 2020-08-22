<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

// ini adalah grouping route, sehingga semua route yang ada di dalamnya 
// secara otomatis akan diawali dengan administrator
// contoh : /administrator/category atau /administrator/product, dan sebagainya
Route::group(['prefix' => 'administrator', 'middleware' => 'auth'], function () {
    Route::get('/home', 'HomeController@index')->name('home'); //JADI ROUTING INI SUDAH ADA DARI ARTIKEL SEBELUMNYA TAPI KITA PINDAHKAN KEDALAM GROUPING

    //INI ADALAH ROUTE BARU
    Route::resource('category', 'CategoryController')->except(['create', 'show']);
});
