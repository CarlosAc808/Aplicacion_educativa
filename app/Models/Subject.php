<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short', 'icon', 'color', 'description'];

    public function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('sort_order');
    }
}