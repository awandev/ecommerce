<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    // membuat relasi ke model district.php
    public function disctict()
    {
        return $this->belongsTo(District::class);
    }
}
