<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use App\Jobs\ProductJob;


class ProductController extends Controller
{
    public function index()
    {
        //BUAT QUERY MENGGUNAKAN MODEL PRODUCT, DENGAN MENGURUTKAN DATA BERDASARKAN CREATED_AT
        //KEMUDIAN LOAD TABLE YANG BERELASI MENGGUNAKAN EAGER LOADING WITH()
        //ADAPUN CATEGORY ADALAH NAMA FUNGSI YANG NNTINYA AKAN DITAMBAHKAN PADA PRODUCT MODEL   
        $product = Product::with(['category'])->orderBy('created_at', 'DESC');

        // jika terdapat parameter pencarian URL atau Q pada URL tidak sama dengan kosong
        if (request()->q != '') {
            // maka lakukan filtering data berdasarkan name atau valuenya sesuai dengan pencarian yang dilakukan user
            $product = $product->where('name', 'LIKE', '%' . request()->q . '%');
        }

        // terakhir load 10 data per halamannya
        $product = $product->paginate(10);

        // load view index.blade.php yang berada di folder products
        // dan passing variabel $product ke view agar dapat digunakan
        return view('products.index', compact('product'));
    }

    public function create()
    {
        // query untuk mengambil semua data di category
        $category = Category::orderBy('name', 'DESC')->get();
        // load view create.blade.php yang berada di dalam folder products
        // dan passing data category
        return view('products.create', compact('category'));
    }

    public function store(Request $request)
    {
        // validasi requestnya
        $this->validate($request, [
            'name'  => 'required|string|max:100',
            'description' => 'required',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|integer',
            'weight' => 'required|integer',
            'image' => 'required|image|mimes:png,jpeg,jpg' //gambar divalidasi harus bertipe png, jpg, dan jpeg
        ]);

        // jika filenya ada
        if ($request->hasFile('image')) {
            // maka kita simpan sementara file tersebut ke dalam variabel file
            $file = $request->file('image');
            // kemudian nama filenya kita buat customer dengan perpaduan time dan slug dari nama produk. adapun extensionnya kita gunakan bawaan file
            $filename = time() . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            // simpan filenya ke dalam folder public/products, dan parameter kedua adalah nama custom untuk file tersebut
            $file->storeAs('public/products', $filename);

            // setelah file tersebut disimpan, kita simpan informasi produknya ke dalam database
            $product = Product::create([
                'name'  => $request->name,
                'slug'  => $request->name,
                'category_id'   => $request->category_id,
                'description'   => $request->description,
                'image' => $filename,
                'price' => $request->price,
                'weight' => $request->weight,
                'status' => $request->status
            ]);

            // jika sudah maka redirect ke list product
            return redirect(route('product.index'))->with(['success' => 'Produk baru ditambahkan']);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id); //query ini untuk mengambil data produk berdasarkan id
        // hapus file image dari storage path diikuti dengan nama image yang diambil dari database
        File::delete(storage_path('app/public/products/' . $product->image));

        // kemudian hapus data produk dari database
        $product->delete();

        // redirect kembali ke halaman product
        return redirect(route('product.index'))->with(['success' => 'Produk sudah dihapus']);
    }


    public function massUploadForm()
    {
        $category = Category::orderBy('name', 'DESC')->get();
        return view('products.bulk', compact('category'));
    }

    public function massUpload(Request $request)
    {
        // validasi data yang dikirim
        $this->validate($request, [
            'category_id'   => 'required|exists:categories,id',
            'file'  => 'required|mimes:xlsx', //pastikan format file yang diterima adalah xlsx
        ]);

        // jika filenya ada
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '-product.' . $file->getClientOriginalExtension();
            $file->storeAs('public/uploads', $filename); //maka simpan file tersebut di storage/app/public/uploads

            // buat jadwal untuk proses file tersebut dengan menggunakan JOB
            // adapun pada dispatch kita mengirimkan dua parameter sebagai informasi
            // yakni kategori id dan nama filenya yang sudah disimpan
            ProductJob::dispatch($request->category_id, $filename);
            return redirect()->back()->with(['success' => 'Upload produk dijadwalkan']);
        }
    }


    public function edit($id)
    {
        $product = Product::find($id); //ambil data produk terkair berdasarkan ID
        $category = Category::orderBy('name', 'DESC')->get(); //ambil semua data kategori
        return view('products.edit', compact('product', 'category')); //load view dan passing datanya ke view
    }

    public function update(Request $request, $id)
    {
        // validasi data yang dikirim
        $this->validate($request, [
            'name'          => 'required|string|max:100',
            'description'   => 'required',
            'category_id'   => 'required|exists:categories,id',
            'price'         => 'required|integer',
            'weight'        => 'required|integer',
            'image'         => 'nullable|image|mimes:png,jpg,jpeg' //image bisa kosong
        ]);

        $product = Product::find($id); //ambil data produk yang akan diedit berdasarkan ID
        $filename = $product->image; //simpan sementara nama file image saat ini

        // jika ada file gambar yang dikirim
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();

            // upload file tersebut
            $file->storeAs('public/product', $filename);
            // dan hapus file gambar yang lama bila ada
            File::delete(storage_path('app/public/products/' . $product->image));
        }

        // kemudian update produk tersebut
        $product->update([
            'name'      => $request->name,
            'description'   => $request->description,
            'category_id'   => $request->category_id,
            'price'         => $request->price,
            'weight'        => $request->weight,
            'image'         => $filename
        ]);

        return redirect(route('product.index'))->with(['success' => 'Data Produk Diperbaharui']);
    }
}
