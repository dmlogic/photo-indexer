<?php
/**
 * This is the main command to index your photos and is intended
 * to run on a CRON schedule
 */

namespace DmLogic\GooglePhotoIndex\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Google\Photos\Types\MediaItem;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use DmLogic\GooglePhotoIndex\Models\Album;
use DmLogic\GooglePhotoIndex\Models\Photo;
use Google\Photos\Types\Album as GoogleAlbum;
use Illuminate\Contracts\Filesystem\Filesystem;
use DmLogic\GooglePhotoIndex\CreatesPhotoService;

class IndexPhotos extends Command
{
    use CreatesPhotoService;

    public const MAX_WIDTH = 3840;
    public const MAX_HEIGHT = 2160;

    protected $signature = 'photos:index {--forced}';

    protected $description = 'Create local copies of photos inside Google Albums';
    protected array $localAlbums;
    protected Filesystem $storage;

    public function __construct()
    {
        parent::__construct();
        $this->storage = Storage::disk('photos');
    }

    public function handle(): void
    {
        $this->localAlbums = Album::get()->keyBy('google_id')->all();
        $pagedResponse = $this->photoservice()->listAlbums(['pageSize' => 50]);
        foreach ($pagedResponse->getIterator() as $googleAlbum) {
            $this->processAlbum($googleAlbum);
        }
        $this->comment('Finished album index');
    }

    protected function processAlbum(GoogleAlbum $googleAlbum): void
    {
        $albumModel = null;
        if (!array_key_exists($googleAlbum->getId(), $this->localAlbums)) {
            $albumModel = Album::create([
                'google_id' => $googleAlbum->getId(),
                'title' => $googleAlbum->getTitle(),
            ]);
            $this->comment('Created album ' . $albumModel->title);
        } elseif ($this->option('forced')) {
            $albumModel = $this->localAlbums[$googleAlbum->getId()];
            $this->info('Indexing album ' . $albumModel->title);
        }
        if (!$albumModel) {
            return;
        }
        $this->indexAlbum($albumModel);
    }

    protected function indexAlbum(Album $album): void
    {
        $alreadyGot = $album->photos->keyBy('google_id')->all();
        $response = $this->photoservice()->searchMediaItems(['albumId' => $album->google_id, 'pageSize' => 50]);
        foreach ($response->iterateAllElements() as $item) {
            // @see https://developers.google.com/photos/library/guides/list#listing-album-contents
            $this->processMediaItem($item, $album->id, $alreadyGot);
        }
        $album->indexed_at = Carbon::now();
        $album->save();
    }

    protected function processMediaItem(MediaItem $mediaItem, string $albumId, array $alreadyGot): void
    {
        if (strpos($mediaItem->getMimeType(), 'video') !== false) {
            $this->comment('Skip video ' . $mediaItem->getDescription());
            return;
        }
        if (!array_key_exists($mediaItem->getId(), $alreadyGot)) {
            $photo = Photo::create([
                'album_id' => $albumId,
                'google_id' => $mediaItem->getId(),
                'title' => $mediaItem->getDescription(),
                'created_at' => Carbon::createFromTimestamp(
                    $mediaItem->getMediaMetadata()->getCreationTime()->getSeconds()
                ),
            ]);
            $this->comment('Created image ' . $mediaItem->getDescription());
        } else {
            $photo = $alreadyGot[$mediaItem->getId()];
        }
        $this->downloadImage($mediaItem, $photo);
    }

    /**
     * @see https://developers.google.com/photos/library/guides/access-media-items#image-base-urls
     */
    protected function downloadImage(MediaItem $mediaItem, Photo $model): void
    {
        $target = $model->album_id . '/' . $mediaItem->getId() . '.jpg';
        if ($this->storage->has($target)) {
            $this->error('Image exists');
            return;
        }
        $source = $mediaItem->getBaseUrl() . '=w' . self::MAX_WIDTH;
        $this->storage->put($target, file_get_contents($source));
        $this->resizeImage($target);
    }

    /**
     * Resizes our downloaded image so it will fit
     * nicely in our photo frame app
     */
    protected function resizeImage(string $pathToImage): void
    {
        $pathToImage = '/' . trim(config('photos.storage_path'), '/') . '/' . $pathToImage;
        $image = Image::make($pathToImage);
        $image->resize(null, self::MAX_HEIGHT, function ($constraint) {
            $constraint->aspectRatio();
        });
        $image->resizeCanvas(self::MAX_WIDTH, self::MAX_HEIGHT, 'center', false, '000000');
        $image->save($pathToImage, 90);
    }
}
