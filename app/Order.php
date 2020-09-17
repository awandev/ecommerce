<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];
    protected $appends = ['status_label', 'ref_status_label', 'commission'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }


    public function getStatusLabelAttribute()
    {
        if ($this->status == 0) {
            return '<span class="badge badge-secondary">Baru</span>';
        } elseif ($this->status == 1) {
            return '<span class="badge badge-primary">Dikonfirmasi</span>';
        } elseif ($this->status == 2) {
            return '<span class="badge badge-info">Proses</span>';
        } elseif ($this->status == 3) {
            return '<span class="badge badge-warning">Dikirim</span>';
        }
        return '<span class="badge badge-success">Selesai</span>';
    }

    public function getCommissionAttribute()
    {
        // komisinya adalah 10% dari subtotal
        $commission = ($this->subtotal * 10) / 100;
        // tapi jika lebih dari 10.000 maka yang dikembalikan adalah 10.000
        return $commission > 10000 ? 10000 : $commission;
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function return()
    {
        return $this->hasOne(OrderReturn::class);
    }
}
