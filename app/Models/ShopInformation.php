<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopInformation extends Model
{
    protected $fillable = [
        'shop_name', 'address', 'email', 'mobile', 'user_id'
    ];
}
