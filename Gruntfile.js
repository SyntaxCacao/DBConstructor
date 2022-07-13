module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    clean: {
      css: {
        src: 'dist/assets/build-*.min.css'
      },
      fonts: {
        src: 'dist/assets/fonts/*'
      },
      js: {
        src: 'dist/assets/build-*.min.js'
      },
      php: {
        src: 'dist/php/*'
      },
      'php-vendor': {
        src: 'dist/php-vendor/*'
      },
      sql: {
        src: 'dist/sql/*'
      }
    },

    copy: {
      config: {
        src: 'src/misc/config.default.php',
        dest: 'dist/tmp/config.default.php'
      },
      favicon: {
        src: 'src/misc/favicon.svg',
        dest: 'dist/assets/favicon.svg'
      },
      'fonts-inter': {
        expand: true,
        cwd: 'src/fonts',
        src: '**',
        dest: 'dist/assets/fonts'
      },
      'fonts-icons': {
        expand: true,
        cwd: 'node_modules/bootstrap-icons/font/fonts',
        src: '**',
        dest: 'dist/assets/fonts'
      },
      htaccess: {
        src: 'src/misc/htaccess',
        dest: 'dist/.htaccess'
      },
      license: {
        src: 'LICENSE',
        dest: 'dist/LICENSE'
      },
      php: {
        expand: true,
        cwd: 'src/php',
        src: '**',
        dest: 'dist/php'
      },
      'php-vendor': {
        expand: true,
        cwd: 'src/php-vendor',
        src: '**',
        dest: 'dist/php-vendor'
      },
      readme: {
        src: 'README.md',
        dest: 'dist/README.md'
      },
      sql: {
        expand: true,
        cwd: 'src/sql',
        src: '**',
        dest: 'dist/sql'
      }
    },

    'dart-sass': {
      build: {
        options: {
          outputStyle: 'compressed'
        },
        files: {
          'dist/assets/build.min.css': 'src/css/index.scss'
        }
      }
    },

    uglify: {
      options: {
        toplevel: true
      },
      build: {
        files: {
          'dist/assets/build.min.js': [
            'src/js/forms.js',
            'src/js/links.js',
            'src/js/form-lists.js',
            'src/js/modals.js',
            'src/js/rowfilter.js',
            'src/js/selector.js',
            'src/js/tabnav.js',
            'src/js/validation.js'
          ]
        }
      }
    },

    watch: {
      css: {
        files: 'src/css/**',
        tasks: 'css'
      },
      js: {
        files: 'src/js/**',
        tasks: 'js'
      },
      php: {
        files: 'src/php/**',
        tasks: 'php'
      },
      sql: {
        files: 'src/sql/**',
        tasks: 'sql'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-dart-sass');

  grunt.registerTask('css', ['clean:css', 'dart-sass', 'version:css']);
  grunt.registerTask('fonts', ['clean:fonts', 'copy:fonts-inter', 'copy:fonts-icons']);
  grunt.registerTask('js', ['clean:js', 'uglify', 'version:js']);
  grunt.registerTask('misc', ['copy:config', 'copy:favicon', 'copy:htaccess', 'copy:license', 'copy:readme', 'version:txt']);
  grunt.registerTask('php', ['clean:php', 'copy:php']);
  grunt.registerTask('php-vendor', ['clean:php-vendor', 'copy:php-vendor']);
  grunt.registerTask('sql', ['clean:sql', 'copy:sql']);

  grunt.registerTask('version', 'Writes version number to version.txt and versionizes files', function(task) {
    const version = grunt.config.get('pkg.version');

    function versionize(file, versionized) {
      if (grunt.file.exists(file)) {
        grunt.file.copy(file, versionized);
        grunt.file.delete(file);
        grunt.log.ok('Moved ' + file + ' to ' + versionized);
      } else {
        grunt.log.warn(versionized + ' does not exist');
      }
    }

    if (task === 'txt') {
      grunt.file.write('dist/version.txt', version);
      grunt.log.ok('Wrote ' + 'version.txt');
    } else if (task === 'css') {
      versionize('dist/assets/build.min.css', 'dist/assets/build-' + version + '.min.css');
    } else if (task === 'js') {
      versionize('dist/assets/build.min.js', 'dist/assets/build-' + version + '.min.js');
    } else {
      grunt.fail.warn('Unknown task "' + task + '".');
    }
  });

  grunt.registerTask('default', ['css', 'fonts', 'js', 'misc', 'php', 'php-vendor', 'sql']);
}
