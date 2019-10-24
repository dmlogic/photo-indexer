<?php

namespace App\Console\Commands;

use Google_Client;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Console\Command;
use Google_Service_PhotosLibrary;
use Illuminate\Support\Facades\Storage;

class ReIndexAlbum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:reindex {albumId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Destroy and re-index a troublesome album';
    protected $photoservice;
    protected $storage;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Google_Client $gclient)
    {
        parent::__construct();
        $this->photoservice = new Google_Service_PhotosLibrary($gclient);
        $this->storage = Storage::disk('photos');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $albumId = $this->argument('albumId');
        Album::where('id','=',$albumId)->delete();
        Photo::where('album_id','=',$albumId)->delete();
        if($this->storage->has($albumId)) {
            $this->storage->deleteDirectory($albumId);
        }
        $this->call('photos:index');
    }
}
