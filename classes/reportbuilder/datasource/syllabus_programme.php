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
namespace local_envasyllabus\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use customfield_sprogramme\reportbuilder\local\entities\competency;
use customfield_sprogramme\reportbuilder\local\entities\competency_assignment;
use customfield_sprogramme\reportbuilder\local\entities\discipline;
use customfield_sprogramme\reportbuilder\local\entities\discipline_assignment;
use customfield_sprogramme\reportbuilder\local\entities\module;


/**
 * TODO datasource
 *
 * @package   local_envasyllabus
 * @copyright 2025 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_programme extends datasource {
    /**
     * Return user friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report:programme', 'local_envasyllabus');
    }

    /**
     * Return the columns that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'responsible:fullnamewithlink',
            'programmefull:uc_nombre',
            'programmefull:uc_annee',
            'programmefull:uc_semestre',
            'course:coursefullnamewithlink',
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
    }

    /**
     * Return the filters that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
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
    }

    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    /**
     * Initialise report
     */
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
            "LEFT JOIN {customfield_sprogramme_competencies} {$competencyassignmentalias} ON ".
            " {$competencyassignmentalias}.pid = {$programmealias}.id"
        ));

        $competency = new competency();
        $competencyalias = $competency->get_table_alias('customfield_sprogramme_complist');
        $competency->add_joins($competencyassignment->get_joins());
        $this->add_entity($competency
            ->add_join(
                "LEFT JOIN {customfield_sprogramme_complist} {$competencyalias} ON ".
                "{$competencyalias}.id = {$competencyassignmentalias}.cid"
            ));

        $disciplineassignment = new discipline_assignment();
        $disciplineassignmentalias = $disciplineassignment->get_table_alias('customfield_sprogramme_disc');
        $this->add_entity($disciplineassignment->add_join(
            "LEFT JOIN {customfield_sprogramme_disc} {$disciplineassignmentalias} ON ".
            "{$disciplineassignmentalias}.pid = {$programmealias}.id"
        ));

        $discipline = new discipline();
        $disciplinealias = $discipline->get_table_alias('customfield_sprogramme_disclist');
        $discipline->add_joins($disciplineassignment->get_joins());
        $this->add_entity($discipline
            ->add_join(
                "LEFT JOIN {customfield_sprogramme_disclist} {$disciplinealias} ON ".
                "{$disciplinealias}.id = {$disciplineassignmentalias}.did"
            ));

        $module = new module();
        $modulealias = $module->get_table_alias('customfield_sprogramme_module');
        $this->add_entity($module
            ->add_join("LEFT JOIN {customfield_sprogramme_module} {$modulealias} ON ".
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
                   {$contextalias}.contextlevel = ".CONTEXT_COURSE."
                   WHERE {$rolassignmentalias}.roleid IN (SELECT r.id FROM {role} r WHERE r.shortname IN ('responsablecourse'))
                   AND {$contextalias}.instanceid = {$coursealias}.id)";
        $this->add_entity($responsible
            ->add_join("LEFT JOIN {user} {$responsiblealias} ON {$responsiblealias}.id IN $insql"));
        $this->add_all_from_entities();
    }
}
