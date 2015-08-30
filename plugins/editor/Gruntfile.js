'use strict';

module.exports = function(grunt) {
    // Load all Grunt tasks matching the `grunt-*` pattern
    require('load-grunt-tasks')(grunt);

    // Time how long tasks take. Can help when optimizing build times
    require('time-grunt')(grunt);

    // Load Bower dependencies
    var dependencies = require('wiredep')();

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        watch: {
            bower: {
                files: ['bower.json']
                , tasks: ['wiredep']
            }
            , gruntfile: {
                files: ['Gruntfile.js']
            }
            , sass: {
                files: ['scss/**/*.scss']
                , tasks: ['sass', 'autoprefixer']
            }
            , livereload: {
                options: {
                    livereload: true
                }
                , files: [
                    'design/**/*.css'
                    , 'design/images/**/*'
                    , 'js/**/*.js'
                    , 'views/**/*.tpl'
                ]
            }
        },

        sass: {
            dist: {
                options: {
                    sourceComments: 'map'
                }
                , files: [{
                    expand: true
                    , cwd: 'scss/'
                    , src: [
                        '*.scss'
                        , '!_*.scss'
                    ]
                    , dest: 'design/'
                    , ext: '.css'
                }]
            }
        },

        autoprefixer: {
            dist: {
                src: ['design/**/*.css']
            }
        },

        imagemin: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'design/images',
                    src: '**/*.{gif,jpeg,jpg,png}',
                    dest: 'design/images'
                }]
            }
        },

        wiredep: {
            dist: {
                src: ['scss/**/*.scss']
            }
        }

    });

    grunt.registerTask('default', [
        'wiredep'
        , 'sass'
        , 'autoprefixer'
        , 'imagemin'
    ]);
};
