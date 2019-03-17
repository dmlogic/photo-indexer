<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $table = 'photos';

    protected $guarded = [];

    public function album()
    {
        return $this->belongsTo('App\Models\Album');
    }
}
