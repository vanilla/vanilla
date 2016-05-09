'use strict';

module.exports = function (grunt) {
  // Load all Grunt tasks matching the `grunt-*` pattern
  require('load-grunt-tasks')(grunt);

  // Time how long tasks take. Can help when optimizing build times
  require('time-grunt')(grunt);

  grunt.file.mkdir('bower_components');

  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),

    watch: {
      js: {
        files: ['js/src/**/*.js']
        , tasks: ['jshint', 'concat']
      }
      , gruntfile: {
        files: ['Gruntfile.js']
      }
      , sass: {
        files: ['scss/**/*.scss']
        , tasks: ['sass_globbing', 'scsslint', 'sass', 'autoprefixer', 'csslint']
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

    copy: {
      main: {
        files: [
          {
            expand: true,
            flatten: true,
            cwd: 'bower_components',
            src: [
            ],
            dest: 'js/vendors'
          },
          {
            expand: true,
            // flatten: true,
            cwd: 'bower_components',
            src: [
              'bootstrap/LICENSE'
              , 'bootstrap/scss/_normalize.scss'
              , 'bootstrap/scss/_utilities.scss'
              , 'bootstrap/scss/_variables.scss'
              , 'bootstrap/scss/mixins/*.scss'
            ],
            dest: 'scss/vendors'
          }
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
        force: true
        , config: 'scss/.scss-lint.yml'
      }
      , all: ['scss/**/*.scss']
    },

    autoprefixer: {
      dist: {
        src: ['design/**/*.css']
      }
    },

    jshint: {
      options: {
        force: true
        , jshintrc: 'js/.jshintrc'
      }
      , all: ['js/src/**/*.js']
    },

    csslint: {
      options: {
        quiet: true
        , csslintrc: 'design/.csslintrc'
      }
      , all: ['design/admin.css']
    },

    concat: {
      dist: {
        src: ([]).concat([
          'js/src/main.js'
        ])
        , dest: 'js/custom.js'
      }
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

    sass_globbing: {
      vendors: {
        files: {
          'scss/maps/_bootstrapVariables.scss': 'scss/vendors/bootstrap/scss/_variables.scss',
          'scss/maps/_bootstrapMixins.scss': 'scss/vendors/bootstrap/scss/mixins/*.scss',
          'scss/maps/_bootstrapSubset.scss': ['scss/vendors/bootstrap/scss/*.scss', '!scss/vendors/bootstrap/scss/_variables.scss']
        },
        options: {
          useSingleQuotes: true,
          signature: false
        }
      }
    }
  });

  grunt.registerTask('default', [
    'copy'
    , 'sass_globbing'
    , 'scsslint'
    , 'sass'
    , 'autoprefixer'
    , 'concat'
    , 'jshint'
    , 'csslint'
    , 'imagemin'
  ]);
};
