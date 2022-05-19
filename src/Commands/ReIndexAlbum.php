<?php
/**
 * This is a fixer-up command in case one of your albums didn't index properly
 * All it really does is kill the data on the album in question and
 * then call then main index script again
 */

namespace DmLogic\GooglePhotoIndex\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use DmLogic\GooglePhotoIndex\Models\Album;
use DmLogic\GooglePhotoIndex\Models\Photo;

class ReIndexAlbum extends Command
{
    protected $signature = 'photos:reindex {albumId}';

    protected $description = 'Destroy and re-index a troublesome album';
    protected $photoservice;
    protected $storage;

    public function __construct()
    {
        parent::__construct();
        $this->storage = Storage::disk('photos');
    }

    public function handle(): void
    {
        $albumId = $this->argument('albumId');
        Album::where('id', '=', $albumId)->delete();
        Photo::where('album_id', '=', $albumId)->delete();
        if ($this->storage->has($albumId)) {
            $this->storage->deleteDirectory($albumId);
        }
        $this->call('photos:index');
    }
}
