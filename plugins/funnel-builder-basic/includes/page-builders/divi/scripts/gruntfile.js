const webpackConfig = require('./webpack.config.js');
module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        webpack: {
            myConfig: webpackConfig,
        },
        watch: {
            scripts: {
                files: ['*.js', '!gruntfile.js', '!webpack.config.js','!loader.js'],
                tasks: ['webpack']
            }
        }
    });


    grunt.loadNpmTasks('grunt-webpack');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default', ['webpack', 'watch']);
};