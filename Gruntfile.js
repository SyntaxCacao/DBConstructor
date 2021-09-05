module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    clean: {
      fonts: {
        src: 'dist/assets/fonts/*'
      },
      php: {
        src: 'dist/php/*'
      },
      sql: {
        src: 'dist/sql/*'
      }
    },

    copy: {
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
      php: {
        expand: true,
        cwd: 'src/php',
        src: '**',
        dest: 'dist/php'
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
      build: {
        files: {
          'dist/assets/build.min.js': [
            'src/js/forms.js',
            'src/js/links.js'
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

  grunt.registerTask('css', ['dart-sass']);
  grunt.registerTask('fonts', ['clean:fonts', 'copy:fonts-inter', 'copy:fonts-icons']);
  grunt.registerTask('js', ['uglify']);
  grunt.registerTask('misc', ['copy:favicon', 'copy:htaccess']);
  grunt.registerTask('php', ['clean:php', 'copy:php']);
  grunt.registerTask('sql', ['clean:sql', 'copy:sql']);

  grunt.registerTask('default', ['css', 'fonts', 'js', 'misc', 'php', 'sql']);
}
