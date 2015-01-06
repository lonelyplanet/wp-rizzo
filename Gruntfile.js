module.exports = function( grunt ) {
    "use strict";

    var jsFiles = [ "Gruntfile.js", "./assets/js/**/*.js", "!./assets/js/**/*.min.js", "!./assets/js/vendor/*.js" ],
        config = {

            // pkg: grunt.file.readJSON("package.json"),

            // https://github.com/gruntjs/grunt-contrib-sass
            sass: {
                css: {
                    options: {
                        style: "compressed"
                    },
                    files: [ {
                        expand: true,
                        cwd: "./assets/sass",
                        src: [ "*.scss", "*.sass" ],
                        dest: "./assets/css",
                        ext: ".css"
                    } ]
                }
            },

            // https://github.com/nDmitry/grunt-autoprefixer
            autoprefixer: {
                options: {
                    browsers: [ "last 3 versions", "ie 8", "ie 9" ]
                },
                css: {
                    expand: true,
                    flatten: true,
                    map: true,
                    src: "./assets/css/*.css",
                    dest: "./assets/css/"
                },
            },

            // https://github.com/gruntjs/grunt-contrib-jshint
            jshint: {
                src: jsFiles,
                options: {
                    jshintrc: "./.jshintrc"
                }
            },

            // https://github.com/jscs-dev/grunt-jscs
            jscs: {
                src: jsFiles,
                options: {
                    config: "./.jscs.json"
                }
            },

            // https://github.com/gruntjs/grunt-contrib-uglify
            uglify: {

                options: {
                    compress: {
                        drop_console: true
                    },
                    sourceMap: true,
                    preserveComments: "some"
                },

                wprizzo: {
                    files: {
                        "./assets/js/wp-rizzo.min.js": [ "./assets/js/wp-rizzo.js" ]
                    }
                }
            },

            // https://github.com/gruntjs/grunt-contrib-watch
            watch: {
                sass: {
                    files: [ "./assets/sass/**/*.{scss,sass}" ],
                    tasks: [ "style" ]
                },

                js: {
                    files: [ "./assets/js/**/*.js", "!./assets/js/**/*.min.js", "!./assets/js/**/*.map"  ],
                    tasks: [ "js" ]
                },

                livereload: {
                    options: {
                        livereload: {
                            port: 12345,
                            // key: grunt.file.read("livereload/localhost.key"),
                            // cert: grunt.file.read("livereload/localhost.cert")
                        }
                    },
                    files: [
                        "./assets/**/*"
                    ]
                }
            }

        };

    grunt.config.init( config );

    // https://github.com/sindresorhus/load-grunt-tasks
    require("load-grunt-tasks")(grunt);

    grunt.registerTask(
        "default",
        [ "assets", "watch" ]
    );

    grunt.registerTask(
        "assets",
        [ "style", "js" ]
    );

    grunt.registerTask(
        "style",
        [ "sass", "autoprefixer" ]
    );

    grunt.registerTask(
        "js",
        [ "jshint", "jscs", "uglify" ]
    );

};
