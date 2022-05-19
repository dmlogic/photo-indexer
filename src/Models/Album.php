<?php

namespace DmLogic\GooglePhotoIndex\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    protected $table = 'albums';
    protected $guarded = [];

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
