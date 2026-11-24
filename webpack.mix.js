const mix = require('laravel-mix');

// The shipped widget contains modern syntax that Webpack 4 cannot parse directly.
// Transpile this dependency without changing its version or widget behavior.
mix.webpackConfig({ module: { rules: [{
    test: /[\\/]@eid-easy[\\/]eideasy-widget[\\/].*\.js$/,
    use: { loader: 'babel-loader', options: {
        presets: [['@babel/preset-env', { targets: { chrome: '79' } }]],
    } },
}] } });

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js/app.js').version();
mix.js('resources/js/analytics/analytics-instrumentation.js', 'public/js').version().sourceMaps();
mix.sass('resources/sass/app.scss', 'public/css/style.css').version();

// Serve PDF.js as native modules; keep its matching worker and fonts on this origin.
mix.js('resources/js/signing-playground.js', 'public/js/signing-playground.js').version();
mix.copy('node_modules/pdfjs-dist/build/pdf.min.mjs', 'public/js/vendor/pdfjs/pdf.js');
mix.copy('node_modules/pdfjs-dist/build/pdf.worker.min.mjs', 'public/js/vendor/pdfjs/pdf.worker.js');
mix.copyDirectory('node_modules/pdfjs-dist/cmaps', 'public/js/vendor/pdfjs/cmaps');
mix.copyDirectory('node_modules/pdfjs-dist/standard_fonts', 'public/js/vendor/pdfjs/standard_fonts');
mix.copy('node_modules/pdfjs-dist/LICENSE', 'public/js/vendor/pdfjs/LICENSE');
mix.copy('node_modules/lucide/dist/umd/lucide.min.js', 'public/js/vendor/lucide.min.js');
mix.copy('node_modules/lucide/LICENSE', 'public/js/vendor/lucide.LICENSE');
