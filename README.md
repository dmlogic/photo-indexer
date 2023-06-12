# Google Photos Indexer

A Laravel package providing console commands to maintain a local copy of a Google Photos account.
Also has endpoints for setting up OAuth access via a couple of localhost routes.

This can serve as both a backup and, by using the [Slideshow](https://github.com/dmlogic/photo-slideshow) tool, a rolling randomised display on your TV or photo frame.

The indexer is only interested in photos you have placed in an album. The intention is that you have to denote the image as "special" enough to copy down. That way random phone shots that get sync'd don't make the cut.

## Installation

As a package, this requires a host Laravel app. I _really_ hate dealing with [laravel/laravel](https://github.com/laravel/laravel) as a container for my code, so there is a build script to quickly consume this package from a functioning App based on the latest available Laravel skeleton. This keeps the codebase clean and makes life massively easier at upgrade time.

1. Create suitable host hardware and OS. A Raspberry PI with a large storage card is perfect
2. Clone this repo
3. Copy `.env.template` to `.env` and adjust as required - particularly the full path to photo storage (which should really be totally separate from this code)
4. Setup OAuth access to your App and download credentials to `credentials.json` in this folder
5. Run `./build.sh`
6. `cd` into `build` and run `php artisan serve`
7. In your browser visit `http://127.0.0.1/oauth/start` and complete the oauth process
8. Setup a CRON command to index daily. e.g. `0 1 * * * cd /full/path/to/project/build && php artisan photos:index`

## Upgrade and maintenance

You can pretty much follow the above providing you don't trash your data. Recommended process:

* Copy `database/database.sqlite` somewhere safe
* If your photo storage is not outside of the built app, copy it somewhere safe
* Delete the built app
* Update the package files to the latest version
* Complete the installation steps
* Copy back `database/database.sqlite` and your photos if necessary

## License

This code is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
