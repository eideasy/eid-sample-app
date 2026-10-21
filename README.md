## Usage guide 

First go to the https://id.eideasy.com to register website to get client_id and secret values. For testing against sandbox find the credentials from https://eideasy.com/developer-documentation/sandbox/ .

## Installing
This Sample app requires PHP 8.5 and is based on Laravel 12. See more at https://laravel.com/docs/12.x/installation
For simplicitly the app is designed to work without database. Session is used to store temporary metadata.

For running locally

- use PHP 8.5 with the fileinfo, iconv, JSON, OpenSSL, and ZIP extensions and Composer 2
- copy .env.example to .env file and change values under Configuration chapter
- run "php artisan key:generate"
- run "composer install"
- run "npm install"
- run "npm run dev" to create public/js/app.js and public/css/style.css 
- start the app with "php artisan serve" or install it to server

When using DDEV, run `ddev start` (or `ddev restart` after changing PHP versions),
then use `ddev composer install` and `ddev artisan test` to install dependencies and run tests on PHP 8.5.

## Configuration

3 environment variables are required in .env file

- EID_API_URL=https://id.eideasy.com
- EID_CLIENT_ID= get from id.eideasy.com after signing up
- EID_SECRET= get from id.eideasy.com after signing up

## Notes

For signing with hwcrypto.js it is required the app to run over https. Also make sure that your browser has ID card software installed.
