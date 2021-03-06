## Usage guide 

First go to the https://id.eideasy.com to register website to get client_id and secret values. For testing against sandbox find the credentials from https://eideasy.com/developer-documentation/sandbox/ .

## Installing
This Sample app is based on the Laravel framework. See more at https://laravel.com/docs/7.x/installation
For simplicitly the app is designed to work without database. Session is used to store temporary metadata.

For running locally

- copy .env.example to .env file and change values under Configuration chapter
- run "php artisan key:generate"
- run "composer install"
- run "npm install"
- run "npm run dev" to create public/js/app.js and public/css/style.css 
- start the app with "php artisan serve" or install it to server

## Configuration

3 environment variables are required in .env file

- EID_API_URL=https://id.eideasy.com
- EID_CLIENT_ID= get from id.eideasy.com after signing up
- EID_SECRET= get from id.eideasy.com after signing up

## Notes

For signing with hwcrypto.js it is required the app to run over https. Also make sure that your browser has ID card software installed.
