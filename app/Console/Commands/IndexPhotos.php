<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Google_Client;
use App\Models\Album;
use Illuminate\Console\Command;
use Google_Service_PhotosLibrary;

class IndexPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:index {--forced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index photos inside Google Albums';
    protected $photoservice;
    protected $albums;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Google_Client $gclient)
    {
        parent::__construct();
        $this->photoservice = new Google_Service_PhotosLibrary($gclient);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->albums = Album::get()->keyBy('google_id')->all();
        $query = $this->performAlbumQuery(['pageSize' => 50]);
        $this->processQueryResults($query->albums);
        $moreToCome = $query->nextPageToken;
        while($moreToCome) {
            $query = $this->performAlbumQuery(['pageSize' => 50, 'pageToken' => $moreToCome]);
            $moreToCome = $query->nextPageToken;
            $this->processQueryResults($query->albums);
        }
        $this->comment('Finished album index');
    }

    protected function processQueryResults($albums)
    {
        foreach($albums as $album) {
            $this->processAlbum($album);
        }
    }

    protected function performAlbumQuery($opts)
    {
        return $this->photoservice->albums->listAlbums($opts);
    }

    protected function processAlbum($googleAlbum)
    {
        $localAlbum = null;
        if(!array_key_exists($googleAlbum->id, $this->albums)) {
            $localAlbum = Album::create([
                'google_id' => $googleAlbum->id,
                'title' => $googleAlbum->title,
            ]);
            $this->comment('Created album '.$localAlbum->title);
        } elseif($this->option('forced')) {
            $localAlbum = $this->albums[$googleAlbum->id];
            $this->info('Indexing album '.$localAlbum->title);
        }
        if(!$localAlbum) {
            return;
        }
        return $this->indexAlbum($localAlbum);
    }

    protected function indexAlbum(Album $album)
    {
        $alreadyGot = $album->photos()->keyBy('google_id')->get()->toArray();
        $this->info('already got');
        dd($aleadyGot);
        // Get any exisiting photo google IDs for this local album
        // Get album from API call
        // Which gives all photo IDs
    }
}
