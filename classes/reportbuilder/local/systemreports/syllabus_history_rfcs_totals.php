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

use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use customfield_sprogramme\reportbuilder\local\entities\rfc_totals;

/**
 * RFCs datasource
 *
 * @package   local_envasyllabus
 * @copyright 2025 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_history_rfcs_totals extends system_report {
    /**
     * Add columns to the report.
     *
     * @return void
     */
    protected function add_columns(): void {
        $columns = [
            'usercreated:fullnamewithlink',
            'validator:fullnamewithlink',
            'rfc_totals:type',
            'rfc_totals:timecreated',
            'rfc_totals:timemodified',
            'course:coursefullnamewithlink',
            'rfc_totals:cm',
            'rfc_totals:td',
            'rfc_totals:tp',
            'rfc_totals:tpa',
            'rfc_totals:tc',
            'rfc_totals:aas',
            'rfc_totals:fmp',
            'rfc_totals:perso_ap',
            'rfc_totals:perso_av',
        ];
        $this->add_columns_from_entities($columns);
    }

    /**
     * Add filters to the report.
     *
     * @return void
     */
    protected function add_filters(): void {
        $filters = [
            'rfc_totals:cm',
            'rfc_totals:td',
            'rfc_totals:tp',
            'rfc_totals:tpa',
            'rfc_totals:tc',
            'rfc_totals:aas',
            'rfc_totals:fmp',
            'rfc_totals:perso_ap',
            'rfc_totals:perso_av',
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Get the default conditions for the report.
     *
     * @return array
     */
    public function get_default_conditions(): array {
        return [];
    }

    #[\Override]
    protected function initialise(): void {
        $rfc = new rfc_totals();

        $rfcalias = $rfc->get_table_alias('rfc_totals');
        $this->set_main_table(rfc_totals::RFC_TEMP_TABLE_NAME, $rfcalias);
        $this->add_entity($rfc);

        // Join the course entity to the badge entity, coalescing courseid with the siteid for site badges.
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $this->add_entity($courseentity
            ->add_join("LEFT JOIN {course} {$coursealias}
                ON {$coursealias}.id = {$rfcalias}.uc"));

        $userentity = new user();
        $userentity->set_entity_name('usercreated');
        $userentity->set_entity_title(new \lang_string('rfc:user', 'customfield_sprogramme'));
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity
            ->add_join("LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$rfcalias}.usercreated"));

        $validatorentity = new user();
        $validatorentity->set_entity_name('validator');
        $validatorentity->set_entity_title(new \lang_string('rfc:validator', 'customfield_sprogramme'));
        $validatoralias = $validatorentity->get_table_alias('user');
        $this->add_entity($validatorentity
            ->add_join("LEFT JOIN {user} {$validatoralias} ON {$validatoralias}.id = {$rfcalias}.adminid"));

        $this->add_columns();
        $this->add_filters();

        // Change column titles.
        $this->get_column(
            'usercreated:fullnamewithlink'
        )->set_title(
            new \lang_string('rfc:user', 'customfield_sprogramme')
        );
        $this->get_column(
            'validator:fullnamewithlink'
        )->set_title(
            new \lang_string('rfc:validator', 'customfield_sprogramme')
        );
        // Here we do this intentionally as any button inserted in the page results in a javascript error.
        // This is due to fact that if we insert it in an existing form this will nest the form and this is not allowed.
        $isdownloadable = $this->get_parameter('downloadable', true, PARAM_BOOL);
        $hasfilters = $this->get_parameter('hasfilters', true, PARAM_BOOL);
        $this->set_downloadable($isdownloadable);
        $this->set_filter_form_default($hasfilters);
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('moodle/reportbuilder:view', \context_system::instance());
    }
}
