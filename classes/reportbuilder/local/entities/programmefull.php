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

namespace local_envasyllabus\reportbuilder\local\entities;

use core_customfield\data_controller;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\{column, filter};
use customfield_sprogramme\reportbuilder\local\entities\programme;
use lang_string;

/**
 * Class programme
 *
 * @package    local_envasyllabus
 * @copyright  2025 Laurent David - CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class programmefull extends programme {

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity:programme_with_customfields', 'local_envasyllabus');
    }

    #[\Override]
    protected function get_all_columns(): array {
        $columns = parent::get_all_columns();
        // Add custom fields columns.
        $columns[] = $this->get_column_for_customfield('uc_nombre');
        $columns[] = $this->get_column_for_customfield('uc_annee');
        $columns[] = $this->get_column_for_customfield('uc_semestre');
        return $columns;
    }

    #[\Override]
    protected function get_all_filters(): array {
        $filters = parent::get_all_filters();
        // Add custom fields filters.
        $filters[] = $this->get_filter_for_customfield('uc_nombre');
        $filters[] = $this->get_filter_for_customfield('uc_annee');
        $filters[] = $this->get_filter_for_customfield('uc_semestre');
        return $filters;
    }

    /**
     * Get a column definition for a given custom field
     *
     * @param string $customfieldshortname
     * @return column
     */
    protected function get_column_for_customfield(string $customfieldshortname): column {
        global $DB;
        $programmealias = $this->get_table_alias('customfield_sprogramme');

        $fieldid = $DB->get_field(
            'customfield_field',
            'id',
            ['shortname' => $customfieldshortname],
            MUST_EXIST
        );
        $fieldparam = database::generate_param_name();
        $cfdataalias = database::generate_alias();

        $sql = "(SELECT {$cfdataalias}.id FROM {customfield_data} {$cfdataalias}
                  WHERE {$cfdataalias}.instanceid = {$programmealias}.uc
                    AND {$cfdataalias}.fieldid = :{$fieldparam})";
        return (new column(
            $customfieldshortname,
            new lang_string("cf:{$customfieldshortname}", 'local_envasyllabus'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field($sql, $customfieldshortname, [$fieldparam => $fieldid])
            ->set_is_sortable(true)
            ->set_callback(function($value) {
                if (empty($value)) {
                    return '';
                }
                $datafield = data_controller::create(intval($value));
                return $datafield->export_value();
            });
    }

    /**
     * Get a column definition for a given custom field
     *
     * @param string $customfieldshortname
     * @return filter
     */
    protected function get_filter_for_customfield(string $customfieldshortname): filter {
        global $DB;
        $programmealias = $this->get_table_alias('customfield_sprogramme');

        $fieldid = $DB->get_field(
            'customfield_field',
            'id',
            ['shortname' => $customfieldshortname],
            MUST_EXIST
        );
        // Create a data controller to access the field definition.
        $datafield = \core_customfield\data_controller::create(0, (object) ['fieldid' => $fieldid]);
        $type = $datafield->datafield();
        $filterclass = text::class;
        switch ($type) {
            case 'intvalue':
            case 'decvalue':
                $filterclass = number::class;
                break;
            case 'shortcharvalue':
            case 'charvalue':
                $filterclass = text::class;
                break;
        }
        $fieldparam = database::generate_param_name();
        $cfdataalias = database::generate_alias();

        $sql = "(SELECT {$cfdataalias}.value FROM {customfield_data} {$cfdataalias}
                  WHERE {$cfdataalias}.instanceid = {$programmealias}.uc
                    AND {$cfdataalias}.fieldid = :{$fieldparam})";
        $filter = (new filter(
            $filterclass,
            $customfieldshortname,
            new lang_string("cf:{$customfieldshortname}", 'local_envasyllabus'),
            $this->get_entity_name(),
            "{$programmealias}.intitule_seance"
        ))->add_joins($this->get_joins())
        ->set_field_sql($sql, [$fieldparam => $fieldid]);
        return $filter;
    }
}
