<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    protected $fillable = ['name', 'parent_id', 'slug'];

    // mutator
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = \Str::slug($value);
    }

    // accessor
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }

    // ini adalah method untuk menghandle relationship
    public function parent()
    {
        // karena relasinya dengan dirinya sendiri, maka class model di dalam belongsTo() adalah nama classnya sendiri yakni Category
        // belongsTo digunakan untuk refleksi ke data induknya
        return $this->belongsTo(Category::class);
    }


    // untuk local scope nama methodnya diawali dengan kata scope dan diikuti dengan nama method yang diinginkan
    // contoh : scopeNamaMethod()
    public function scopeGetParent($query)
    {
        // semua query yang menggunakan local scope ini akan secara otomatis ditambahkan kondisi wherenul('parent_id)
        return $query->whereNull('parent_id');
    }


    public function child()
    {
        // menggunakan relasi one to many dengan foreign key parent_it
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function product()
    {
        // jenis relasinya adalah one to many, berarti kategori ini bisa digunakan oleh banyak produk
        return $this->hasMany(Product::class);
    }
}
