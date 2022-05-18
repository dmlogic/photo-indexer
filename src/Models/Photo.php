<?php

namespace DmLogic\GooglePhotoIndex\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $table = 'photos';

    protected $guarded = [];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}
