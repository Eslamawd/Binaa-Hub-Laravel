<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    //
    
    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'status',
        'image',
        'vendor_id',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

     public function scopeVisible($query)
    {
        return $query->where('status', 'active');
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'store_id');
    }

  public function getImageAttribute($value)
    {
        if (!$value) return null;

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return asset('storage/' . $value);
    }

}
