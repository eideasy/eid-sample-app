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

## Demo playground

The homepage explains three demo journeys: digital signatures, hosted identification and
digital identity wallets. The wallet card defaults to https://eudiw-playground.eideasy.com/.
Set `EID_WALLET_DEMOS_URL=http://localhost:5173/` in `.env` to test against the local
wallet demo services. Run `ddev artisan config:clear` after changing it. `/sign-custom-file` keeps the existing
hosted, API, digest, sandbox e-Seal and multiple-signer flows, with PDF, combined
ASiC-E, separate ASiC-E and enveloped XAdES output choices.

Choose your own file or try the fictional sample PDF. PDF preview runs locally with
self-hosted PDF.js assets; the document is submitted to this backend only when you
continue. Placement supports click/drag, keyboard arrows (Shift for ten-point steps),
presets and manual coordinates. Coordinates are PDF points from the bottom-left;
page numbers in the UI start at 1 and map to zero-based API indexes. The marker is
an approximate guide: final visual size and collision avoidance belong to the signing
service. Automatic placement leaves X/Y unset. Unsupported or encrypted PDFs fall
back to manual coordinates or automatic placement. Non-PDF formats omit PDF controls.
PDF signing requires one PDF; it does not silently discard additional files.

Build and verify with the project's DDEV runtime:

```sh
ddev composer install
ddev npm ci
ddev npm run production
ddev npm run test:playground
ddev exec vendor/bin/phpunit
```

The production build copies PDF.js, its matching worker, CMaps/fonts and Lucide into
`public/js/vendor`. Webpack transpiles the existing signing-widget dependency for
Webpack 4 compatibility; the widget's version and UI are unchanged. The separate
`/login-widget` page retains its existing layout and styles. Analytics initializes
only when enabled and an Amplitude API key is configured.
