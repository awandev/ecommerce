<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    // jika fillable akan menginzinkan field apa saja yang ada di dalam arraynya
    // maka guarded akan memblok field apa saja yang ada di dalam arraynya
    // jadi apabila fieldnya banyak maka kita bisa manfaatkan dengan hanya menuliskan array kosong
    // yang berarti tidak ada field yang diblock sehingga semua field tersebut sudah diizinkan
    // hal ini memudahkan kita karena tidak perlu menuliskannya satu persatu
    protected $guarded = [];

    // sedangkan ini adalah mutators
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = \Str::slug($value);
    }



    public function getStatusLabelAttribute()
    {
        // adapun valuenya akan mencetak html berdasarkan value dari field status
        if ($this->status == 0) {
            return '<span class="badge badge-secondary">Draft</span>';
        }
        return '<span class="badge badge-success">Aktif</span>';
    }

    // fungsi untuk menghandle relasi ke tabel category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
