module.exports = function (grunt) {
    grunt.initConfig({
        shell: {
            tests: {
                command: [
                    'clear',
                    'php bin/phpunit --group=now'
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
