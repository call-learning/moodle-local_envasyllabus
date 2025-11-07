/* eslint-env node */
/* jshint node: true */
/* jshint esversion: 6 */

module.exports = grunt => {
    const path = require('path');
    const moodleRoot = path.resolve(__dirname, '../../');
    const componentPath = '/local/envasyllabus/';
    // Always load the sass task before configuring/merging.
    grunt.loadNpmTasks('grunt-sass');

    // One place to keep Sass options consistent.
    const sassOptions = {
        implementation: require('sass'),
        includePaths: [path.join(moodleRoot, componentPath, '/scss/')],
        outputStyle: 'expanded', // Pretty output.
    };
    process.chdir(moodleRoot);
    try {
        const rootGruntfile = path.join(moodleRoot, 'Gruntfile.js');
        if (grunt.file.exists(rootGruntfile)) {
            require(rootGruntfile)(grunt);
        }
        // Extend/override with your project-specific target using absolute paths.
        grunt.config.merge({
            sass: {
                envasyllabus: {
                    files: {
                        [path.join(moodleRoot, componentPath, '/styles.css')]:
                            path.join(moodleRoot, componentPath, '/styles.scss')
                    },
                    options: sassOptions
                }
            },
            stylelint: {
                envasyllabus: {
                    options: {
                        fix: true,
                    },
                    src: [path.join(moodleRoot, componentPath, '/styles.css')]
                }
            }
        });
        // Default task available in both success/failure paths.
        grunt.registerTask('envasyllabus_sass', ['sass:envasyllabus', 'stylelint:envasyllabus']);
        grunt.registerTask('default', ['envasyllabus_sass']);
    } finally {
        // Always return to the original working directory.
        process.env.PWD = moodleRoot; // Optional, helps code that prefers PWD.
    }
};