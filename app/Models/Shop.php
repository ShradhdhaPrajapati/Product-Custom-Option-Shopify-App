<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = "shops";
    protected $fillable = [
        'shop',
        'shop_email',
        'access_token',
        'app_id'
    ];
}
