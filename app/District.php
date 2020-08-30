<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    // buat relasi ke model city.php
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
