<?php
// This file is part of Moodle - http://moodle.org/.
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_envasyllabus\local;

use local_envasyllabus\utils;

/**
 * Course header data helper class.
 *
 * @package    local_envasyllabus
 * @copyright  2026 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_header_data {
    /**
     * Cfield definition
     */
    public const CF_HEADER_DEFINITION = [
        [
            'type' => 'cf',
            'fieldname' => 'uc_departement',
        ],
        [
            'type' => 'categorysum',
            'fieldname' => 'student_grand_total_hours',
            'positionafter' => true,
            'fields' => [
                [
                    'type' => 'categorysum',
                    'fieldname' => 'student_total_hours',
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
                    'fieldname' => 'student_total_hours_he',
                    'fields' => [
                        [
                            'type' => 'cfprogramme',
                            'fieldname' => 'uc_heures_he_aas_etudiant',
                            'programmenames' => 'aas',
                        ],
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
            'type' => 'cf',
            'fieldname' => 'uc_ects',
        ],
    ];

    /**
     * @var array $programmetotals programme total
     */
    private array $programmetotals = [];
    /**
     * @var array $customfieldsvalues array of custom field values
     */
    protected array $customfieldsvalues = [];

    /**
     * @var bool $isnewprogrammeenabled is new programme enabled
     */
    private bool $isnewprogrammeenabled = false;

    /**
     * Constructor
     *
     * @param int $courseid Course ID
     * @param array|null $existingcustomtfielddata Existing custom field data to use (for performance)
     */
    public function __construct(
        /**@var int $courseid Course ID */
        protected int $courseid,
        ?array $existingcustomtfielddata = null,
    ) {
        if ($existingcustomtfielddata !== null) {
            $cfdata = $existingcustomtfielddata;
        } else {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $cfdata = $handler->get_instance_data($this->courseid, true);
        }
        foreach ($cfdata as $cfdatacontroller) {
            $shortname = $cfdatacontroller->get_field()->get('shortname');
            $this->customfieldsvalues[$shortname] = $cfdatacontroller->export_value();
        }
        $this->isnewprogrammeenabled = course_syllabus_helper::is_new_programme_enabled($this->courseid);
        if (course_syllabus_helper::is_new_programme_enabled($this->courseid)) {
            $sprogrammefield = course_syllabus_helper::get_programme_customfield($this->courseid, $cfdata);
            $this->programmetotals = $sprogrammefield->get_column_totals();
        }
    }
    /**
     * Get header data for course summary
     *
     * @param array $fieldinfolist
     * @return array
     * @throws \coding_exception
     */
    public function compute_header_data(
        array $fieldinfolist = self::CF_HEADER_DEFINITION
    ): array {
        $headerdata = [];
        foreach ($fieldinfolist as $fieldinfo) {
            if (!empty($fieldinfo['type'])) {
                $value = null;
                $subheaders = [];
                switch ($fieldinfo['type']) {
                    case 'cfprogramme':
                        $sum = self::get_programme_sum($fieldinfo);
                        $value = $sum > 0 ? $sum : null;
                        break;
                    case 'cf':
                        $cfvalue = $this->customfieldsvalues[$fieldinfo['fieldname']];
                        $value = !empty($cfvalue) ? $cfvalue : null;
                        if (is_numeric($value)) {
                            $value = floatval($value);
                        }
                        break;
                    case 'categorysum':
                        $subheaders = $this->compute_header_data($fieldinfo['fields']);
                        $value = $this->compute_header_sum($fieldinfo['fields']);
                }
                if (!empty($fieldinfo['positionafter'])) {
                    $headerdata += $subheaders;
                    $headerdata[$fieldinfo['fieldname']] = $value;
                } else {
                    $headerdata[$fieldinfo['fieldname']] = $value;
                    $headerdata += $subheaders;
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
    protected function compute_header_sum(array $fieldinfolist): float {
        $total = 0;
        foreach ($fieldinfolist as $fieldinfo) {
            if (!empty($fieldinfo['type'])) {
                switch ($fieldinfo['type']) {
                    case 'cfprogramme':
                        $total += $this->get_programme_sum($fieldinfo);
                        break;
                    case 'categorysum':
                        $total += $this->compute_header_sum($fieldinfo['fields']);
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
        if (empty($programmenames) || !$this->isnewprogrammeenabled) {
            return floatval($this->customfieldsvalues[$fieldname]) ?? 0;
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
