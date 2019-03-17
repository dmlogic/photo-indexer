<?php

namespace App\Console\Commands;

use Google_Client;
use Google_Service_PhotosLibrary;
use Illuminate\Console\Command;

class IndexPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index photos inside Google Albums';

    protected $photoservice;

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
        $albums = $this->photoservice->albums;
        dump($albums->listAlbums());
    }
}
