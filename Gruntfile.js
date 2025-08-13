/* eslint-env node */
/* jshint node: true */
/* jshint esversion: 6 */

module.exports = grunt => {
    const path = require('path');
    const originalCwd = process.cwd();
    const moodleRoot = path.resolve(__dirname, '../../../');

    try {
        // Se déplacer vers la racine de Moodle
        process.chdir(moodleRoot);

        // Load grunt-sass manually since the root Gruntfile doesn't include it
        grunt.loadNpmTasks('grunt-sass');

        // Charger le Gruntfile racine depuis le contexte de la racine
        const rootGruntfile = path.join(moodleRoot, 'Gruntfile.js');
        if (grunt.file.exists(rootGruntfile)) {
            require(rootGruntfile)(grunt);
        }

        // Étendre la configuration existante avec des chemins absolus
        grunt.config.merge({
            sass: {
                envasyllabus: {
                    files: {
                        [path.join(originalCwd, "styles.css")]: path.join(originalCwd, "scss/styles.scss")
                    },
                    options: {
                        implementation: require('sass'),
                        includePaths: [path.join(originalCwd, "scss/")],
                        indentWidth: 4,
                        outputStyle: 'expanded'
                    }
                }
            }
        });

        // Créer une tâche qui utilise directement la configuration sass
        grunt.registerTask('build:envasyllabus', ['sass:envasyllabus']);

    } catch (error) {
        grunt.log.error('Erreur lors du chargement du Gruntfile racine:', error.message);

        // Revenir au répertoire original pour la configuration de base
        process.chdir(originalCwd);

        grunt.loadNpmTasks('grunt-sass');
        grunt.initConfig({
            sass: {
                envasyllabus: {
                    files: {
                        "styles.css": "scss/styles.scss"
                    },
                    options: {
                        implementation: require('sass'),
                        includePaths: ["scss/"],
                        indentWidth: 4,
                        outputStyle: 'expanded'
                    }
                }
            }
        });

        grunt.registerTask('default', ['sass:envasyllabus']);
    }
};
