<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_set_id', 'type', 'label', 'values', 'required', 'position'
    ];


    protected $casts = [
        'required' => 'boolean',
    ];

    public function optionSet()
    {
        return $this->belongsTo(OptionSet::class);
    }
}
