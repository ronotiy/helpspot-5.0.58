const mix = require('laravel-mix');

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

mix
    .styles([
        "public/static/css/yui/reset-fonts-grids.css",
        "public/static/css/yui/base.css",
        "public/static/js/prototip/css/prototip.css",
        "public/static/js/syntaxhighlighter/styles/shCore.css",
        "public/static/js/syntaxhighlighter/styles/shThemeRDark.css",
        "public/static/js/tingle/tingle.min.css",
        "public/static/css/base.css",
        "public/static/js/datetimepicker/css/mobiscroll.jquery.min.css",
        "public/static/js/popup/css/mobiscroll.jquery.min.css"
    ], "public/static/css/helpspot.css")

    .styles([
        "public/static/css/yui/reset-fonts-grids.css",
        "public/static/css/print.css"
    ], "public/static/css/helpspot-print.css")

    .scripts([
        "public/static/js/jquery.js",
        "public/static/js/jquery.hoverIntent.js",
        "public/static/js/jquery.cookie.js",
        "public/static/js/jquery.localstorage.js",
        "public/static/js/jquery.dropzone.js",
        "public/static/js/jquery.idle-timer.js",
        "public/static/js/jquery.timer.js",
        "public/static/js/jquery.noconflict.js",
        "public/static/js/general.js",
        "public/static/js/scriptaculous/builder.js",
        "public/static/js/scriptaculous/effects.js",
        "public/static/js/scriptaculous/dragdrop.js",
        "public/static/js/scriptaculous/controls.js",
        "public/static/js/scriptaculous/slider.js",
        "public/static/js/prototip/js/prototip/prototip.js",
        "public/static/js/validation.js",
        "public/static/js/livepipe/livepipe.js",
        "public/static/js/livepipe/tabs.js",
        "public/static/js/DynamicOptionList.js",
        "public/static/js/jscolor/jscolor.min.js",
        "public/static/js/syntaxhighlighter/scripts/shCore.js",
        "public/static/js/syntaxhighlighter/scripts/shBrushCss.js",
        "public/static/js/syntaxhighlighter/scripts/shBrushJScript.js",
        "public/static/js/syntaxhighlighter/scripts/shBrushPhp.js",
        "public/static/js/syntaxhighlighter/scripts/shBrushXml.js",
        "public/static/js/tingle/tingle.min.js",
        "public/static/js/datetimepicker/js/mobiscroll.jquery.min.js",
        "public/static/js/popup/js/mobiscroll.jquery.min.js"
    ], "public/static/js/helpspot.js")

    .combine(["public/static/js/prototype.js",'public/static/js/helpspot.js'], 'public/static/js/helpspot.js')

    .scripts([
        "public/static/js/milonic/milonic_src.js",
        "public/static/js/milonic/mmenudom.js"
    ], "public/static/js/helpspot.milonic.js")

    .scripts([
        "public/static/js/scriptaculous/effects.js",
        "public/static/js/DynamicOptionList.js",
    ], "public/static/js/helpspot.portal.js")

    .combine(["public/static/js/prototype.js", 'public/static/js/helpspot.portal.js'], 'public/static/js/helpspot.portal.js')

    // These do not correctly build the plugins
    // .minify("public/static/js/tinymce/plugins/hsstaffautocomplete/plugin.js")
    // .minify("public/static/js/tinymce/plugins/hsresponseautocomplete/plugin.js")
    // .minify("public/static/js/tinymce/plugins/hstagautocomplete/plugin.js")

    .version();
