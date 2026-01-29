<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify.
// it under the terms of the GNU General Public License as published by.
// the Free Software Foundation, either version 3 of the License, or.
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,.
// but WITHOUT ANY WARRANTY; without even the implied warranty of.
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the.
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_envasyllabus\output;

use core_course\external\course_summary_exporter;
use local_competvetsuivi\matrix\matrix;
use local_envasyllabus\local\course_header_data;
use local_envasyllabus\utils;
use local_envasyllabus\visibility;
use moodle_exception;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Course Syllabus header data renderer class
 *
 * @package     local_envasyllabus
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_header implements renderable {
    /**
     * @var array $programmetotals programme total
     */
    private array $programmetotals = [];

    /**
     * Cfield definition
     */
    public const CF_HEADER_DEFINITION = [
        ['type' => 'cf', 'fieldname' => 'uc_departement', 'class' => 'highlighted-top'],
        [
            'type' => 'categorysum',
            'languagestring' => 'syllabuspage:student_grand_total_hours',
            'class' => 'highlighted-top',
            'positionafter' => true,
            'fields' => [
                [
                    'type' => 'categorysum',
                    'languagestring' => 'syllabuspage:student_total_hours',
                    'class' => 'highlighted-top',
                    'fields' => [
                        ['type' => 'cfprogramme', 'fieldname' => 'uc_heures_cm_etudiant', 'programmenames' => 'cm'],
                        ['type' => 'cfprogramme', 'fieldname' => 'uc_heures_td_etudiant', 'programmenames' => 'td'],
                        ['type' => 'cfprogramme', 'fieldname' => 'uc_heures_tp_etudiant', 'programmenames' => 'tp'],
                        ['type' => 'cfprogramme', 'fieldname' => 'uc_heures_tpa_etudiant', 'programmenames' => 'tpa'],
                        ['type' => 'cfprogramme', 'fieldname' => 'uc_heures_tc_etudiant', 'programmenames' => 'tc'],
                        ['type' => 'cfprogramme', 'fieldname' => 'uc_heures_fmp_etudiant', 'programmenames' => 'fmp'],
                    ],
                ],
                [
                    'type' => 'categorysum',
                    'languagestring' => 'syllabuspage:student_total_hours_he',
                    'class' => 'highlighted-top',
                    'fields' => [
                        ['type' => 'cfprogramme', 'fieldname' => 'uc_heures_he_aas_etudiant', 'programmenames' => 'aas'],
                        [
                            'type' => 'cfprogramme',
                            'fieldname' => 'uc_heures_he_tpers_etudiant',
                            'programmenames' => 'perso_av, perso_ap',
                        ],
                    ],
                ],
            ],
        ],
        [
            'type' => 'cfprogramme',
            'fieldname' => 'uc_ects',
            'languagestring' => 'syllabuspage:student_ects',
            'icon' => 'ects',
            'class' => 'highlighted-top',
        ],
    ];

    /**
     * @var array $customfieldsvalue array of custom field values
     */
    protected array $customfieldsvalue = [];

    /**
     * Constructor
     *
     * @param int $courseid Course ID
     */
    public function __construct(
        /**@var int $courseid Course ID */
        protected int $courseid,
    ) {
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $cfdata = $handler->get_instance_data($this->courseid, true);
        foreach ($cfdata as $cfdatacontroller) {
            $shortname = $cfdatacontroller->get_field()->get('shortname');
            $this->customfieldsvalue[$shortname] = $cfdatacontroller->export_value();
        }
    }

    /**
     * Export the course data for template
     *
     * @param renderer_base $output
     * @return array|stdClass|void
     */
    public function export_for_template(renderer_base $output) {
        if (utils::is_new_programme_enabled($this->courseid)) {
            $sprogrammefield = utils::get_programme_customfield($this->courseid);
            $this->programmetotals = $sprogrammefield->get_column_totals();
        }

        return $this->get_header_data(self::CF_HEADER_DEFINITION);
    }

    /**
     * Create a header data object
     *
     * @param string $class
     * @param string $title
     * @param string $icon
     * @return stdClass
     */
    protected function create_header_data(string $class, string $title, string $icon): stdClass {
        $headerinfo = new stdClass();
        $headerinfo->class = $class;
        $headerinfo->title = $title;
        $headerinfo->value = '';
        $headerinfo->icon = $icon;
        return $headerinfo;
    }

    /**
     * Get header data for course summary
     *
     * @param array $fieldinfolist
     * @return array
     * @throws \coding_exception
     */
    protected function get_header_data(array $fieldinfolist): array {
        $headerdata = [];
        foreach ($fieldinfolist as $fieldinfo) {
            if (!empty($fieldinfo['type'])) {
                if (empty($fieldinfo['languagestring'])) {
                    $fielddesc = get_string('syllabuspage:' . $fieldinfo['fieldname'], 'local_envasyllabus');
                } else {
                    $fielddesc = get_string($fieldinfo['languagestring'], 'local_envasyllabus');
                }
                $headerinfo = $this->create_header_data(
                    $fieldinfo['class'] ?? '',
                    $fielddesc,
                    $fieldinfo['icon'] ?? ''
                );
                switch ($fieldinfo['type']) {
                    case 'cfprogramme':
                        $sum = $this->get_programme_sum($fieldinfo);
                        $headerinfo->value = $sum > 0 ? (string)$sum : '-';
                        $headerdata[] = $headerinfo;
                        break;
                    case 'cf':
                        $cfvalue = $this->customfieldsvalue[$fieldinfo['fieldname']];
                        $headerinfo->value = !empty($cfvalue) ? $cfvalue : '-';
                        $headerdata[] = $headerinfo;
                        break;
                    case 'categorysum':
                        $subheaders = $this->get_header_data($fieldinfo['fields']);
                        $headerinfo->value = $this->get_header_sum($fieldinfo['fields']);
                        if (!empty($fieldinfo['positionafter'])) {
                            array_push($headerdata, ...$subheaders);
                            $headerdata[] = $headerinfo;
                        } else {
                            $headerdata[] = $headerinfo;
                            array_push($headerdata, ...$subheaders);
                        }
                }
            }
        }
        return $headerdata;
    }

    /**
     * Get header data for course summary
     *
     * @param array $fieldinfolist
     * @return float
     * @throws \coding_exception
     */
    protected function get_header_sum(array $fieldinfolist): float {
        $total = 0;
        foreach ($fieldinfolist as $fieldinfo) {
            if (!empty($fieldinfo['type'])) {
                switch ($fieldinfo['type']) {
                    case 'cfprogramme':
                        $total += $this->get_programme_sum($fieldinfo);
                        break;
                    case 'categorysum':
                        $total += $this->get_header_sum($fieldinfo['fields']);
                        break;
                }
            }
        }
        return $total;
    }


    /**
     * Get programme sum
     *
     * @param array $fieldinfo
     * @return float
     */
    protected function get_programme_sum(array $fieldinfo): float {
        $fieldname = $fieldinfo['fieldname'];
        $programmenames = $fieldinfo['programmenames'] ?? '';
        if (empty($fieldname)) {
            return 0;
        }
        if (empty($programmenames) || !utils::is_new_programme_enabled($this->courseid)) {
            return intval($this->customfieldsvalue[$fieldname]) ?? 0;
        }
        $programmmenames = explode(',', $programmenames);
        $programmmenames = array_map('trim', $programmmenames);
        $sum = 0;
        $totalswithkeys = array_column($this->programmetotals, 'sum', 'column');
        foreach ($programmmenames as $programmename) {
            if (isset($totalswithkeys[$programmename])) {
                $sum += $totalswithkeys[$programmename];
            }
        }
        return $sum;
    }
}