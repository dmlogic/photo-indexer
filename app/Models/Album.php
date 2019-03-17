<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $table = 'albums';
    protected $guarded = [];


    public function album()
    {
        return $this->hasMany('App\Models\Photo');
    }
}
