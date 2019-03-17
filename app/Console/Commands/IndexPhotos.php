<?php

namespace App\Console\Commands;

use Image;
use Carbon\Carbon;
use Google_Client;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Console\Command;
use Google_Service_PhotosLibrary;
use Illuminate\Support\Facades\Storage;
use Google_Service_PhotosLibrary_SearchMediaItemsRequest;

class IndexPhotos extends Command
{
    const MAX_WIDTH = 3840;
    const MAX_HEIGHT = 2160;
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
        $this->albums = Album::get()->keyBy('google_id')->all();
        $query = $this->performAlbumQuery(['pageSize' => 50]);
        $this->processQueryResults($query->albums);
        $paginationToken = $query->nextPageToken;
        while($paginationToken) {
            $query = $this->performAlbumQuery(['pageSize' => 50, 'pageToken' => $paginationToken]);
            $paginationToken = $query->nextPageToken;
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
        // Get any exisiting photo google IDs for this local album
        $alreadyGot = $album->photos->keyBy('google_id')->all();
        // Get album from API call
        $query = $this->performMediaQuery($album->google_id);
        $this->info('Media search results');
        $this->processMediaResults($query->mediaItems,$album->id,$alreadyGot);
        $paginationToken = $query->nextPageToken;
        while($paginationToken) {
            $query = $this->performMediaQuery($album->google_id, $paginationToken);
            $paginationToken = $query->nextPageToken;
            $this->processMediaResults($query->mediaItems,$album->id,$alreadyGot);
        }
        $album->indexed_at = Carbon::now();
        $album->save();
    }

    protected function performMediaQuery($albumId,$token = null)
    {
        $request = new Google_Service_PhotosLibrary_SearchMediaItemsRequest;
        $request->albumId = $albumId;
        $request->pageSize = 50;
        if($token) {
            $request->setPageToken($token);
        }
        return $this->photoservice->mediaItems->search($request);
    }

    protected function processMediaResults($mediaItems,$albumId,$alreadyGot)
    {
        foreach($mediaItems as $image) {
            if(strpos($image->mimeType, 'video') !== false) {
                continue;
            }
            if(!array_key_exists($image->id, $alreadyGot))  {
                $photo = Photo::create([
                    'album_id' => $albumId,
                    'google_id' => $image->id,
                    'title' => $image->description,
                    'created_at' => (new Carbon($image->getMediaMetadata()->creationTime))->__toString()
                ]);
                $this->comment('Created image '.$image->description);
            } else {
                $photo = $alreadyGot[$image->id];
            }
            $this->downloadImage($image,$photo);
        }
    }
    /**
     * https://developers.google.com/photos/library/guides/access-media-items#image-base-urls
     */
    protected function downloadImage($mediaItem,$model)
    {
        $target = $model->album_id.'/'.$mediaItem->id.'.jpg';
        if($this->storage->has($target)) {
            $this->error('Image exists');
        }
        $source = $mediaItem->baseUrl.'=w'.self::MAX_WIDTH;
        $this->storage->put($target, file_get_contents($source));
        $this->resizeImage($target);
    }

    protected function resizeImage($source)
    {
        $source = storage_path('photos/').$source;
        $image = Image::make($source);
        $image->resize(null, self::MAX_HEIGHT, function ($constraint) {
            $constraint->aspectRatio();
        });
        $image->resizeCanvas(self::MAX_WIDTH, self::MAX_HEIGHT, 'center', false, '000000');
        $image->save($source, 90);
    }
}
