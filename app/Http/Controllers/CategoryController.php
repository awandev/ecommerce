<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        //BUAT QUERY KE DATABASE MENGGUNAKAN MODEL CATEGORY DENGAN MENGURUTKAN BERDASARKAN CREATED_AT DAN DISET DESCENDING, KEMUDIAN PAGINATE(10) BERARTI HANYA ME-LOAD 10 DATA PER PAGENYA
        //YANG MENARIK ADALAH FUNGSI WITH(), DIMANA FUNGSI INI DISEBUT EAGER LOADING
        //ADAPUN NAMA YANG DISEBUTKAN DIDALAMNYA ADALAH NAMA METHOD YANG DIDEFINISIKAN DIDALAM MODEL CATEGORY
        //METHOD TERSEBUT BERISI FUNGSI RELATIONSHIPS ANTAR TABLE
        //JIKA LEBIH DARI 1 MAKA DAPAT DIPISAHKAN DENGAN KOMA, 
        // CONTOH: with(['parent', 'contoh1', 'contoh2'])
        $category = Category::with(['parent'])->orderBy('created_at', 'DESC')->paginate(10);

        // query ini mengambil semua list category dari table categories, perhatikan akhirannya adalah GET(), tanpa ada limit
        $parent = Category::getParent()->orderBy('name', 'ASC')->get();

        // load view dari folder categories, dan di dalamnya ada file index.blade.php
        // kemudian passing data dari variabel $category & $parent ke view agar dapat digunakan pada view terkait
        return view('categories.index', compact('category', 'parent'));
    }

    public function store(Request $request)
    {
        // jadi kita validasi data yang diterima, dimana name category wajib diisi
        // tipenya ada string dan max karakternya adalah 50 dan bersifat unik
        // unik maksudnya jika data dengan nama yang sama sudah ada maka validasinya akan mengembalikan error
        $this->validate($request, [
            'name'  => 'required|string|max:50|unique:categories'
        ]);

        // field slug akan ditambahkan ke dalam collection $request
        $request->request->add(['slug' => $request->name]);

        // sehingga pada bagian ini kita tinggal menggunakan $request->except()
        // yakni menggunakan semua data yang ada di dalam $request kecuali index _TOKEN
        // fungsi request ini secara otomatis akan menjadi array
        // category::create adalah mass assigment untuk memberikan instruksi ke model agar menambahkan data ke tabel terkait
        Category::create($request->except('_token'));

        // apabila berhasil, maka redirect ke halaman list kategori
        // dan buat flash session menggunakan with()
        // jadi with() disini berbeda fungsinya dengan with() yang disambungkan dengan model
        return redirect(route('category.index'))->with(['success' => 'Kategori Baru Ditambahkan!']);
    }

    public function edit($id)
    {
        $category = Category::find($id); //query mengambil data berdasarkan id
        $parent   = Category::getParent()->orderBy('name', 'ASC')->get();

        // load view edit.blade.php pada folder categories
        // dan passing variabel category dan parent
        return view('categories.edit', compact('category', 'parent'));
    }

    public function update(Request $request, $id)
    {
        //VALIDASI FIELD NAME
        //YANG BERBEDA ADA TAMBAHAN PADA RULE UNIQUE
        //FORMATNYA ADALAH unique:nama_table,nama_field,id_ignore
        //JADI KITA TETAP MENGECEK UNTUK MEMASTIKAN BAHWA NAMA CATEGORYNYA UNIK
        //AKAN TETAPI KHUSUS DATA DENGAN ID YANG AKAN DIUPDATE DATANYA DIKECUALIKAN
        $this->validate($request, [
            'name'  => 'required|string|max:50|unique:categories,name,' . $id
        ]);

        //query untuk mengambil data berdasarkan id
        $category = Category::find($id);
        // kemudian perbarui datanya
        // posisi kiri adalah nama field yang ada di table categories
        // posisi kanan adalah value dari form edit
        $category->update([
            'name'  => $request->name,
            'parent_id' => $request->parent_id,
        ]);

        // redirect ke halaman list kategori
        return redirect(route('category.index'))->with(['success' => 'Kategori Diperbaharui!']);
    }


    public function destroy($id)
    {
        //Buat query untuk mengambil category berdasarkan id menggunakan method find()
        //ADAPUN withCount() SERUPA DENGAN EAGER LOADING YANG MENGGUNAKAN with()
        //HANYA SAJA withCount() RETURNNYA ADALAH INTEGER
        //JADI NNTI HASIL QUERYNYA AKAN MENAMBAHKAN FIELD BARU BERNAMA child_count YANG BERISI JUMLAH DATA ANAK KATEGORI
        $category = Category::withCount(['child'])->find($id);

        // jika category ini tidak digunakan sebagai parent atau childnya = 0
        if ($category->child_count == 0) {
            // maka hapus kategori ini
            $category->delete();

            // dan redirect kembali ke halaman list kategori
            return redirect(route('category.index'))->with(['success' => 'Kategori Dihapus']);
        }

        // selain itu, maka redirect ke list tapi flash messagenya error yang berarti kategori ini sedang digunakan
        return redirect(route('category.index'))->with(['error' => 'Kategori ini memiliki anak kategori']);
    }
}
