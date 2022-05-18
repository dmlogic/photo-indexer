#!/usr/bin/env bash
set -ex

# Run this from the parent dir where you'd like the project to be
# ./build.sh name-of-project

composer create-project --no-install --no-scripts laravel/laravel $1

cd $1

composer config repositories.dmlogic/photo-indexer path ../
composer install
composer require dmlogic/photo-indexer
composer run-script post-root-package-install
composer run-script post-create-project-cmd

rm database/migrations/*.php
touch database/database.sqlite
php artisan migrate
