/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

module.exports = function (grunt) {
    grunt.initConfig({
        shell: {
            tests: {
                command: [
                    'clear',
                    'php bin/phpunit'
                ].join('&&'),
                options: {
                    stdout: true
                }
            }
        },
        watch: {
            tests: {
                files: ['{src,tests}/**/*.php'],
                tasks: ['shell:tests']
            }
        }
    });

    // plugins
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-shell');

    // tasks
    grunt.registerTask('tests', ['shell:tests', 'watch:tests']);
};
