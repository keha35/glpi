/*
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

const CleanWebpackPlugin = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const path = require('path');

const libOutputPath = 'public/lib';

var config = {
    entry: {
        glpi: './js/main.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'public/build')
    },
};

/*
 * External libraries files build configuration.
 */
var libsConfig = {
    entry: function () {
        // Create an entry per *.js file in lib/bundle directory.
        // Entry name will be name of the file (without ext).
        var entries = {};

        let files = glob.sync(path.resolve(__dirname, 'lib/bundles') + '/*.js');
        files.forEach(function (file) {
            entries[path.basename(file, '.js')] = file;
        });

        return entries;
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, libOutputPath),
    },
    module: {
        rules: [
            {
                // Load scripts with no compilation for packages that are directly providing "dist" files.
                // This prevents useless compilation pass and can also 
                // prevents incompatibility issues with the webpack require feature.
                test: /\.js$/,
                include: [
                    path.resolve(__dirname, 'node_modules/@fullcalendar'),
                    path.resolve(__dirname, 'node_modules/codemirror'),
                    path.resolve(__dirname, 'node_modules/gridstack'),
                    path.resolve(__dirname, 'node_modules/jstree'),
                    path.resolve(__dirname, 'node_modules/spectrum-colorpicker'),
                ],
                use: ['script-loader'],
            },
            {
                // Build styles
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
                // Copy images and fonts
                test: /\.((gif|png|jp(e?)g)|(eot|ttf|svg|woff2?))$/,
                use: {
                    loader: 'file-loader',
                    options: {
                        name: function (filename) {
                            // Keep only relative path
                            var sanitizedPath = path.relative(__dirname, filename);

                            // Sanitize name
                            sanitizedPath = sanitizedPath.replace(/[^\\/\w-.]/, '');

                            // Remove the first directory (lib, node_modules, ...) and empty parts
                            // and replace directory separator by '/' (windows case)
                            sanitizedPath = sanitizedPath.split(path.sep)
                                .filter(function (part, index) {
                                    return '' != part && index != 0;
                                }).join('/');

                            return sanitizedPath;
                        },
                    },
                },
            },
        ],
    },
    plugins: [
        new CleanWebpackPlugin([libOutputPath]), // Clean lib dir content
    ]
};

var libs = {
    '@fullcalendar': [
        {
            context: 'core',
            from: 'locales/*.js',
        }
    ],
    'fuzzy': [
        {
            context: 'lib',
            from: 'fuzzy.js',
        }
    ],
    'gridstack': [
        {
            context: 'dist',
            from: '{gridstack{,-extra}.css,gridstack{,.jQueryUI}.js}',
        }
    ],
    'jquery': [
        {
            context: 'dist',
            from: 'jquery.js',
        }
    ],
    'jquery-mousewheel': [
        {
            from: 'jquery.mousewheel.js',
        }
    ],
    'jquery-prettytextdiff': [
        {
            from: 'jquery.pretty-text-diff.js',
        }
    ],
    'jquery-ui': [
        {
            context: 'ui',
            from: 'i18n/*.js',
        }
    ],
    'jquery-ui-dist': [
        {
            from: '{images/*,jquery-ui.css,jquery-ui.js}',
        }
    ],
    'jquery-ui-timepicker-addon': [
        {
            context: 'dist',
            from: '{jquery-ui-timepicker-addon.css,jquery-ui-timepicker-addon.js,i18n/jquery-ui-timepicker-*.js}',
            ignore: ['i18n/jquery-ui-timepicker-addon-i18n{,.min}.js'],
        }
    ],
    'select2': [
        {
            context: 'dist',
            from: '{css/select2.css,js/select2.full.js,js/i18n/*.js}',
        }
    ],
    'spectrum-colorpicker': [
        {
            from: '{spectrum.css,spectrum.js}',
        }
    ],
    'spin.js': [
        {
            from: 'spin.js',
        }
    ],
    'tinymce': [
        {
            from: '{tinymce.js,plugins/**/*,themes/**/*}',
            ignore: ['*min.css', '*min.js'],
        }
    ],
    'tinymce-i18n': [
        {
            from: 'langs/*.js',
        }
    ],
    'unorm': [
        {
            context: 'lib',
            from: 'unorm.js',
        }
    ],
};

for (let packageName in libs) {
    let libPackage = libs[packageName];
    let to = libOutputPath + '/' + packageName.replace(/^@/, ''); // remove leading @ in case of prefixed package

    for (let e = 0; e < libPackage.length; e++) {
        let packageEntry = libPackage[e];

        let context = 'node_modules/' + packageName;
        if (packageEntry.hasOwnProperty('context')) {
            context += '/' + packageEntry.context;
        }

        let copyParams = {
            context: path.resolve(__dirname, context),
            from:    packageEntry.from,
            to:      path.resolve(__dirname, to),
            toType:  'dir',
        };

        if (packageEntry.hasOwnProperty('ignore')) {
            copyParams.ignore = packageEntry.ignore;
        }

        config.plugins.push(new CopyWebpackPlugin([copyParams]));
    }
}

module.exports = (env, argv) => {
    if (argv.mode === 'development') {
        config.devtool = 'source-map';
    }

    return config;
};
