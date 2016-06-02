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
        , tasks: ['sass_globbing', 'scsslint', 'sass', 'autoprefixer', 'kss']
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

    kss: {
      options: {
        verbose: true,
        template: 'template'
      },
      dist: {
        src: ['scss'],
        dest: 'styleguide'
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
              'bootstrap/js/dist/dropdown.js'
              , 'bootstrap/js/dist/util.js'
            ],
            dest: 'js/vendors'
          },
          {
            expand: true,
            // flatten: true,
            cwd: 'bower_components',
            src: [
              'bootstrap/LICENSE'
              // , 'bootstrap/scss/_normalize.scss'
              // , 'bootstrap/scss/_utilities.scss'
              // , 'bootstrap/scss/_variables.scss'
              , 'bootstrap/scss/*.scss'
              , 'bootstrap/scss/mixins/*.scss'
            ],
            dest: 'scss/vendors'
          }
        ]
      },
      styleguide: {
        files: [
          {
            flatten: true,
            src: [
              'design/admin.css'
            ],
            dest: 'template/public/admin.css'
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
          'scss/maps/_vanillicon.scss': 'scss/vanillicon/*.scss',
          'scss/maps/_extensions.scss': 'scss/extensions/*.scss',
          'scss/maps/_bootstrapVariables.scss': 'scss/vendors/bootstrap/scss/_variables.scss',
          'scss/maps/_bootstrapMixins.scss': 'scss/vendors/bootstrap/scss/mixins/*.scss',
          'scss/maps/_bootstrapSubset.scss': [
            'scss/vendors/bootstrap/scss/_normalize.scss',
            'scss/vendors/bootstrap/scss/_utilities.scss',
            'scss/vendors/bootstrap/scss/_nav.scss',
            'scss/vendors/bootstrap/scss/_card.scss',
            'scss/vendors/bootstrap/scss/_navbar.scss',
            'scss/vendors/bootstrap/scss/_button-group.scss',
            'scss/vendors/bootstrap/scss/_tables.scss',
            'scss/vendors/bootstrap/scss/_media.scss',
            'scss/vendors/bootstrap/scss/_dropdown.scss',
            'scss/vendors/bootstrap/scss/_modal.scss',
            'scss/vendors/bootstrap/scss/_forms.scss',
            'scss/vendors/bootstrap/scss/_custom-forms.scss',
            'scss/vendors/bootstrap/scss/_nav.scss',
            'scss/vendors/bootstrap/scss/_grid.scss',
            'scss/vendors/bootstrap/scss/_reboot.scss'
          ],
          'scss/maps/_dashboard.scss': ['scss/*.scss', '!scss/admin.scss', '!scss/_variables.scss', '!scss/_global.scss']
        },
        options: {
          useSingleQuotes: true,
          signature: false
        }
      }
    }
  });

  grunt.registerTask('styleguide', [
      'copy:styleguide',
      'kss'
  ]);

  grunt.registerTask('default', [
    'copy:main'
    , 'sass_globbing'
    , 'scsslint'
    , 'sass'
    , 'autoprefixer'
    , 'concat'
    , 'jshint'
    // , 'csslint'
    , 'imagemin'
    , 'styleguide'
  ]);
};
