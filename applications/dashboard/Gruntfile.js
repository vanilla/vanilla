/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const Fiber = require('fibers');
const sass = require('sass');

module.exports = function(grunt) {
    // Load all Grunt tasks matching the `grunt-*` pattern
    require("load-grunt-tasks")(grunt);

    // Time how long tasks take. Can help when optimizing build times
    require("time-grunt")(grunt);

    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),

        watch: {
            js: {
                files: ["js/src/**/*.js"],
                tasks: ["concat:dist"],
            },
            gruntfile: {
                files: ["Gruntfile.js"],
            },
            sass: {
                files: ["scss/**/*.scss", "../../**/scss/**/*.scss"],
                tasks: ["sass_globbing", "sass", "autoprefixer"],
            },
            livereload: {
                options: {
                    livereload: true,
                },
                files: ["design/**/*.css", "design/images/**/*", "js/**/*.js", "views/**/*.tpl"],
            },
        },

        kss: {
            options: {
                verbose: true,
                template: "template",
                homepage: "styleguide.md",
            },
            dist: {
                src: ["scss/src"],
                dest: "styleguide",
            },
        },

        copy: {
            styleguide: {
                files: [
                    {
                        flatten: true,
                        src: ["design/admin.css"],
                        dest: "template/public/admin.css",
                    },
                ],
            },
            styleguidefonts: {
                files: [
                    {
                        flatten: true,
                        src: ["../../resources/fonts/*"],
                        dest: "template/public/resources/fonts",
                    },
                ],
            },
        },

        sass: {
            options: {
                implementation: sass,
                fiber: Fiber,
                sourceMap: true,
            },
            dist: {
                files: [
                    {
                        expand: true,
                        cwd: "scss",
                        src: ["*.scss", "!_*.scss"],
                        dest: "design/",
                        ext: ".css",
                    },
                ],
            },
        },

        autoprefixer: {
            dist: {
                src: ["design/admin.css", "design/style.css", "design/style-compat.css"],
                options: {
                    map: true,
                    browsers: ["ie > 8", "last 6 iOS versions", "last 10 versions"],
                },
            },
        },

        concat: {
            dist: {
                src: [].concat([
                    "js/src/lithe.js",
                    "js/src/lithe.drawer.js",
                    "js/src/modal.dashboard.js",
                    "js/spoiler.js",
                    "js/src/main.js",
                ]),
                dest: "js/dashboard.js",
            },
            styleguide: {
                src: [].concat([
                    "js/vendors/tether.js",
                    "js/vendors/jquery.checkall.min.js",
                    "js/vendors/icheck.min.js",
                    "js/vendors/clipboard.min.js",
                    "js/vendors/drop.min.js",
                    "js/vendors/bootstrap/*.js",
                    "js/vendors/prettify/*.js",
                    "js/vendors/ace/*.js",
                    "js/colorpicker.js",
                    "js/cropimage.js",
                    "js/buttonGroup.js",
                    "js/jquery.tablejenga.js",
                    "../../js/library/jquery.expander.js",
                    "../../js/library/jquery.gardencheckboxgrid.js",
                    "../../js/library/jquery.form.js",
                    "js/spoiler.js",
                    "js/dashboard.js",
                ]),
                dest: "template/public/dashboard.js",
            },
        },

        sass_globbing: {
            vendors: {
                files: {
                    "scss/maps/_vanillicon.scss": "scss/vanillicon/*.scss",
                    "scss/maps/_extensions.scss": "scss/extensions/*.scss",
                    "scss/maps/_bootstrapVariables.scss": "scss/vendors/bootstrap/scss/_variables.scss",
                    "scss/maps/_bootstrapMixins.scss": "scss/vendors/bootstrap/scss/mixins/*.scss",
                    "scss/maps/_bootstrapAnimation.scss": "scss/vendors/bootstrap/scss/_animation.scss",
                    "scss/maps/_vendorSubset.scss": [
                        "scss/vendors/bootstrap/scss/_normalize.scss",
                        "scss/vendors/bootstrap/scss/_utilities.scss",
                        "scss/vendors/bootstrap/scss/_nav.scss",
                        "scss/vendors/bootstrap/scss/_tooltip.scss",
                        "scss/vendors/bootstrap/scss/_dropdown.scss",
                        "scss/vendors/bootstrap/scss/_list-group.scss",
                        "scss/vendors/bootstrap/scss/_forms.scss",
                        "scss/vendors/bootstrap/scss/_grid.scss",
                        "scss/vendors/bootstrap/scss/_reboot.scss",
                    ],
                    "scss/maps/_dashboard.scss": [
                        "scss/src/*.scss",
                        "!scss/src/_variables.scss",
                        "!scss/src/_icons.scss",
                        "!scss/src/_svgs.scss",
                        "!scss/src/_helpers.scss",
                    ],
                },
                options: {
                    useSingleQuotes: true,
                    signature: false,
                },
            },
        },
    });

    grunt.registerTask("styleguide", ["concat:styleguide", "copy:styleguide", "kss"]);

    grunt.registerTask("default", ["sass_globbing", "sass", "autoprefixer", "concat:dist"]);
};
