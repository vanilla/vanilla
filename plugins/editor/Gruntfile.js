/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const Fiber = require("fibers");
const sass = require("sass");

module.exports = function(grunt) {
    // Load all Grunt tasks matching the `grunt-*` pattern
    require("load-grunt-tasks")(grunt);

    // Time how long tasks take. Can help when optimizing build times
    require("time-grunt")(grunt);

    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),

        watch: {
            gruntfile: {
                files: ["Gruntfile.js"],
            },
            sass: {
                files: ["scss/**/*.scss"],
                tasks: ["sass", "autoprefixer"],
            },
            livereload: {
                options: {
                    livereload: true,
                },
                files: ["design/**/*.css", "design/images/**/*", "js/**/*.js", "views/**/*.tpl"],
            },
        },

        sass: {
            dist: {
                options: {
                    implementation: sass,
                    fiber: Fiber,
                    sourceMap: true,
                },
                files: [
                    {
                        expand: true,
                        cwd: "scss/",
                        src: ["*.scss", "!_*.scss"],
                        dest: "design/",
                        ext: ".css",
                    },
                ],
            },
        },

        autoprefixer: {
            dist: {
                src: ["design/**/*.css"],
            },
        },

        imagemin: {
            dist: {
                files: [
                    {
                        expand: true,
                        cwd: "design/images",
                        src: "**/*.{gif,jpeg,jpg,png}",
                        dest: "design/images",
                    },
                ],
            },
        },
    });

    grunt.registerTask("default", ["sass", "autoprefixer", "imagemin"]);
};
