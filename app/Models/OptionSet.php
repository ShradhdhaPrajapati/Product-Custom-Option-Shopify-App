<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionSet extends Model
{
    use HasFactory;

    protected $fillable = ['shop_id', 'title', 'status', 'priority'];


    public function options()
    {
        return $this->hasMany(Option::class, 'option_set_id');
    }

    public function products()
    {
        return $this->belongsToMany(User::class, 'product_option_set', 'option_set_id', 'product_id');

    }
}
