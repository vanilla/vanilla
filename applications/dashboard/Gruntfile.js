'use strict';

module.exports = function (grunt) {
    // Load all Grunt tasks matching the `grunt-*` pattern
    require('load-grunt-tasks')(grunt);

    // Time how long tasks take. Can help when optimizing build times
    require('time-grunt')(grunt);

    grunt.file.mkdir('bower_components');

    // Load Bower dependencies
    var dependencies = require('wiredep')();

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        watch: {
            bower: {
                files: ['bower.json']
                , tasks: ['wiredep']
            }
            , js: {
                files: ['js/src/**/*.js']
                , tasks: ['jshint']
            }
            , gruntfile: {
                files: ['Gruntfile.js']
            }
            , sass: {
                files: ['scss/**/*.scss']
                , tasks: ['scsslint', 'sass', 'autoprefixer', 'csslint']
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
            options: {
                sourceMap: true,
                outputStyle: "expanded"
            },
            dist: {
                files: [{
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

        scsslint: {
            options: {
                config: 'scss/.scss-lint.yml',
                maxBuffer: 3000 * 1024,
                colorizeOutput: true
            }
            , all: ['scss/**/*.scss']
        },

        autoprefixer: {
            options: {
                map: true,
                cascade: false
            },
            dist: {
                src: ['design/admin.css'
                     ,'design/style.css' ]
            }
        },

        jshint: {
            options: {
                jshintrc: 'js/.jshintrc'
            }
            , all: ['js/src/**/*.js']
        },

        csslint: {
            options: {
                csslintrc: 'design/.csslintrc'
            }
            , all: ['design/admin.css'
                   ,'design/style.css']
        },

        imagemin: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'design/images',
                    src: '**/*.{gif,jpeg,jpg,png,svg}',
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
        , 'scsslint'
        , 'sass'
        , 'autoprefixer'
        , 'jshint'
        , 'csslint'
        , 'imagemin'
    ]);
};
