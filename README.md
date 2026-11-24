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

The homepage links to **Digital signatures**, **OAuth 2.0 login**, and **Digital identity wallets**.
The wallet card defaults to https://eudiw-playground.eideasy.com/.
Set `EID_WALLET_DEMOS_URL=http://localhost:5173/` in `.env` to try the local wallet demos.
Run `ddev artisan config:clear` after changing it.

In the signing playground (`/sign-custom-file`), choose **eID Easy signing page**,
**Embedded signing widget**, **Digest signing**, **Automatic e-Seal**, or **Multiple signers**.
Available output formats depend on the flow. PDF signing accepts one PDF per request.

Use a test file or select **Or try a sample PDF**. The preview runs locally with
self-hosted PDF.js assets. The demo uploads the document to its backend when you continue.

With a working PDF preview, use the dashed safe area to position the signature by clicking,
dragging, using the arrow keys (Shift for ten-point steps), choosing a preset, or entering
coordinates. X and Y are PDF points measured from the bottom-left corner. The UI counts pages
from 1 and sends zero-based page indexes to the API. Positions outside the safe area block
submission. The signing service determines the final size and may adjust the position to
avoid existing signatures.

If the PDF preview fails, choose **Automatic** to clear the coordinates or select **Hide visual
signature**. You can also try a different PDF.

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
