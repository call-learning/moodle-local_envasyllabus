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
     * Cfield definition
     */
    public const CF_HEADER_DISPLAY_DEFINITION = [
        'uc_departement' => [
            'class' => 'highlighted-top',
        ],
        'student_grand_total_hours' => [
            'class' => 'highlighted-top',
        ],
        'student_total_hours' => [
            'class' => 'highlighted-top',
        ],
        'student_total_hours_he' => [
            'class' => 'highlighted-top',
        ],
        'uc_ects' => [
            'languagestring' => 'syllabuspage:student_ects',
            'icon' => 'ects',
            'class' => 'highlighted-top',
        ],
    ];
    /**
     * Constructor
     *
     * @param int $courseid Course ID
     */
    public function __construct(
        /**@var int $courseid Course ID */
        protected int $courseid,
    ) {
    }

    /**
     * Export the course data for template
     *
     * @param renderer_base $output
     * @return array|stdClass|void
     */
    public function export_for_template(renderer_base $output) {
        $computeheader = new course_header_data($this->courseid);
        $headerdata = $computeheader->compute_header_data();
        // Now add the layout and other values.
        $headervalues = [];
        foreach ($headerdata as $fieldname => $value) {
            $layoutinfo = self::CF_HEADER_DISPLAY_DEFINITION[$fieldname] ?? [];
            $fielddesc = !empty($layoutinfo['languagestring'])
                ? get_string($layoutinfo['languagestring'], 'local_envasyllabus')
                : get_string('syllabuspage:' . $fieldname, 'local_envasyllabus');
            $headerinfo = [
                'class' => $layoutinfo['class'] ?? '',
                'title' => $fielddesc,
                'value' => $value ? (string) $value : '-',
                'icon' => $layoutinfo['icon'] ?? '',
            ];
            $headervalues[$fieldname] = (object) $headerinfo;
        }
        return array_values($headervalues);
    }
}
