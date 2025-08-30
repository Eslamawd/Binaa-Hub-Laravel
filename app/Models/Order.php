<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
       protected $fillable = [
        'location',
        'user_id',
        'stripe_payment_intent',
        'stripe_session_id',
        'amount_platform_fee',
        'total_price',
        'status'
    ];




      public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
  
      public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    

}
