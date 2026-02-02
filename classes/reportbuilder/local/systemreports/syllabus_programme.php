<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace local_envasyllabus\reportbuilder\local\systemreports;

use core\exception\coding_exception;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\column;
use core_reportbuilder\system_report;
use customfield_sprogramme\local\persistent\sprogramme_comp;
use customfield_sprogramme\local\persistent\sprogramme_disc;
use customfield_sprogramme\reportbuilder\local\entities\competency;
use customfield_sprogramme\reportbuilder\local\entities\competency_assignment;
use customfield_sprogramme\reportbuilder\local\entities\discipline;
use customfield_sprogramme\reportbuilder\local\entities\discipline_assignment;
use customfield_sprogramme\reportbuilder\local\entities\module;
use local_envasyllabus\local\course_syllabus_helper;

/**
 * System report to display programmes
 *
 * @package   local_envasyllabus
 * @copyright 2025 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_programme extends system_report {
    #[\Override]
    public function get_default_conditions(): array {
        return [];
    }

    #[\Override]
    protected function initialise(): void {
        $programmeentity = new \local_envasyllabus\reportbuilder\local\entities\programmefull();

        $programmealias = $programmeentity->get_table_alias('customfield_sprogramme');
        $this->set_main_table('customfield_sprogramme', $programmealias);
        $this->add_entity($programmeentity);

        // Join the course entity to the badge entity, coalescing courseid with the siteid for site badges.
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $this->add_entity($courseentity
            ->add_join("LEFT JOIN {course} {$coursealias}
                ON {$coursealias}.id = {$programmealias}.uc"));

        $userentity = new user();
        $userentity->set_entity_name('usermodified');
        $userentity->set_entity_title(new \lang_string('usermodified'));
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity
            ->add_join("LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$programmealias}.usermodified"));

        $competencyassignment = new competency_assignment();
        $competencyassignmentalias = $competencyassignment->get_table_alias('customfield_sprogramme_competencies');
        $this->add_entity($competencyassignment->add_join(
            "LEFT JOIN {customfield_sprogramme_competencies} {$competencyassignmentalias} ON " .
            "{$competencyassignmentalias}.pid = {$programmealias}.id"
        ));

        $competency = new competency();
        $competencyalias = $competency->get_table_alias('customfield_sprogramme_complist');
        $competency->add_joins($competencyassignment->get_joins());
        $this->add_entity($competency
            ->add_join(
                "LEFT JOIN {customfield_sprogramme_complist} {$competencyalias} ON " .
                "{$competencyalias}.uniqueid = {$competencyassignmentalias}.cid"
            ));

        $disciplineassignment = new discipline_assignment();
        $disciplineassignmentalias = $disciplineassignment->get_table_alias('customfield_sprogramme_disc');
        $this->add_entity($disciplineassignment->add_join(
            "LEFT JOIN {customfield_sprogramme_disc} {$disciplineassignmentalias} ON " .
            "{$disciplineassignmentalias}.pid = {$programmealias}.id"
        ));

        $discipline = new discipline();
        $disciplinealias = $discipline->get_table_alias('customfield_sprogramme_disclist');
        $discipline->add_joins($disciplineassignment->get_joins());
        $this->add_entity($discipline
            ->add_join(
                "LEFT JOIN {customfield_sprogramme_disclist} {$disciplinealias} ON " .
                "{$disciplinealias}.uniqueid = {$disciplineassignmentalias}.did"
            ));

        $module = new module();
        $modulealias = $module->get_table_alias('customfield_sprogramme_module');
        $this->add_entity($module
            ->add_join("LEFT JOIN {customfield_sprogramme_module} {$modulealias} ON " .
            "{$modulealias}.id = {$programmealias}.moduleid"));

        $responsible = new user();
        $responsible->set_entity_name('responsible');
        $responsible->set_entity_title(new \lang_string('responsible', 'customfield_sprogramme'));
        $responsible->add_joins($courseentity->get_joins());
        $responsiblealias = $responsible->get_table_alias('user');
        [$useralias, $rolassignmentalias, $contextalias] = database::generate_aliases(3);

        $insql = "(SELECT {$useralias}.id
                  FROM {user} {$useralias}
                   JOIN {role_assignments} {$rolassignmentalias} ON {$rolassignmentalias}.userid = {$useralias}.id
                   JOIN {context} {$contextalias} ON {$contextalias}.id = {$rolassignmentalias}.contextid AND
                   {$contextalias}.contextlevel = " .
            CONTEXT_COURSE . "
                   WHERE {$rolassignmentalias}.roleid IN (SELECT r.id FROM {role} r WHERE r.shortname = '"
                    .course_syllabus_helper::RESPONSABLE_ROLE_NAME
                    ."') AND {$contextalias}.instanceid = {$coursealias}.id)";
        $this->add_entity($responsible
            ->add_join("LEFT JOIN {user} {$responsiblealias} ON {$responsiblealias}.id IN $insql"));
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        // Fix titles for some columns.
        $this->get_column('responsible:fullnamewithlink')->set_title(
            new \lang_string('syllabuspage:manager', 'local_envasyllabus')
        );
        $this->get_column('course:coursefullnamewithlink')->set_title(
            new \lang_string('course')
        );

        $displayentityname = function ($entityclass, $value, $index) {
            $records = $entityclass::get_records(['pid' => $value], 'id');
            if (array_key_exists($index - 1, $records)) {
                return $records[$index - 1]->get_name();
            }
            return '';
        };
        $displayentitypercent = function ($entityclass, $value, $index) {
            $records = $entityclass::get_records(['pid' => $value], 'id');
            if (array_key_exists($index - 1, $records)) {
                return $records[$index - 1]->get('percentage');
            }
            return '';
        };
        // Add repeated columns for competencies (max 3).
        $this->add_repeated_columns(
            'competency',
            'name',
            3,
            fn($value, $record, $index) => $displayentityname(sprogramme_comp::class, $value, $index)
        );
        $this->add_repeated_columns(
            'competency',
            'percent',
            3,
            fn($value, $record, $index) => $displayentitypercent(sprogramme_comp::class, $value, $index)
        );

        $this->add_repeated_columns(
            'discipline',
            'name',
            3,
            fn($value, $record, $index) => $displayentityname(sprogramme_disc::class, $value, $index)
        );
        $this->add_repeated_columns(
            'discipline',
            'percent',
            3,
            fn($value, $record, $index) => $displayentitypercent(sprogramme_disc::class, $value, $index)
        );

        // Here we do this intentionally as any button inserted in the page results in a javascript error.
        // This is due to fact that if we insert it in an existing form this will nest the form and this is not allowed.
        $isdownloadable = $this->get_parameter('downloadable', true, PARAM_BOOL);
        $hasfilters = $this->get_parameter('hasfilters', true, PARAM_BOOL);
        $this->set_downloadable($isdownloadable);
        $this->set_filter_form_default($hasfilters);
    }

    #[\Override]
    protected function add_columns(): void {
        $columns = [
            'programmefull:uc_annee',
            'programmefull:uc_semestre',
            'programmefull:uc_nombre',
            'course:coursefullnamewithlink',
            'responsible:fullnamewithlink',
            'programmefull:cct_ept',
            'programmefull:dd_rse',
            'programmefull:type_ae',
            'programmefull:sequence',
            'module:sortorder',
            'module:name',
            'programmefull:intitule_seance',
            'programmefull:cm',
            'programmefull:td',
            'programmefull:tp',
            'programmefull:tpa',
            'programmefull:tc',
            'programmefull:aas',
            'programmefull:fmp',
            'programmefull:perso_av',
            'programmefull:perso_ap',
            'programmefull:consignes',
            'programmefull:supports',
        ];

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('programmefull:uc_nombre', SORT_ASC);
    }

    #[\Override]
    protected function add_filters(): void {
        $filters = [
            'responsible:fullname',
            'programmefull:uc_nombre',
            'programmefull:uc_annee',
            'programmefull:uc_semestre',
            'course:fullname',
            'programmefull:cct_ept',
            'programmefull:dd_rse',
            'programmefull:type_ae',
            'programmefull:sequence',
            'module:sortorder',
            'module:name',
            'programmefull:intitule_seance',
            'programmefull:cm',
            'programmefull:td',
            'programmefull:tp',
            'programmefull:tpa',
            'programmefull:tc',
            'programmefull:aas',
            'programmefull:fmp',
            'programmefull:perso_av',
            'programmefull:perso_ap',
            'programmefull:consignes',
            'programmefull:supports',
        ];
        $this->add_filters_from_entities($filters);
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('moodle/reportbuilder:edit', \context_system::instance());
    }

    /**
     * Add a number of repeated columns to the report, based on the maximum number of linked records.
     *
     * @param string $columtype
     * @param string $fieldtype
     * @param int $repeats
     * @param callable|null $displaycallback
     */
    protected function add_repeated_columns(
        string $columtype,
        string $fieldtype,
        int $repeats,
        ?callable $displaycallback = null,
    ): void {
        $programmeentity = $this->get_entity('programmefull');
        $programmealias = $programmeentity->get_table_alias('customfield_sprogramme');
        for ($index = 1; $index <= $repeats; $index++) {
            $newcolumn = new column(
                "{$columtype}_{$fieldtype}_{$index}",
                new \lang_string("{$columtype}:rep:{$fieldtype}", 'local_envasyllabus', $index),
                $programmeentity->get_entity_name()
            );
            $newcolumn->add_joins($programmeentity->get_joins());
            $newcolumn->add_field("{$programmealias}.id");
            $newcolumn->set_callback($displaycallback, $index);
            $this->add_column($newcolumn);
        }
    }
}
