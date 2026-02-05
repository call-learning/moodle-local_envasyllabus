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

use core_course\customfield\course_handler;
use local_envasyllabus\tests\test_helper;

/**
 * Unit tests for course header data computation.
 *
 * @package    local_envasyllabus
 * @copyright  2024 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_envasyllabus\local\course_header_data
 */
final class course_header_data_test extends \advanced_testcase {
    use test_helper;

    /**
     * Test compute header data
     */
    public function test_compute_header_data(): void {
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category();
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', 'sample-course.json'));
        $coursedef = json_decode($json, true);
        $coursedef['category'] = $category->id;
        $course = $this->create_course_from_def($coursedef);
        $coursecfs = course_handler::create()->get_instance_data($course->id, true);
        $courseheaderdataprocessor = new course_header_data($course->id, $coursecfs);
        // On ne garde que les colonnes et les sommes pour la comparaison.
        $expected = [
            'uc_departement' => 'DSBP',
            'student_total_hours' => 66.0,
            'uc_heures_cm_etudiant' => 18.0,
            'uc_heures_td_etudiant' => 26.0,
            'uc_heures_tp_etudiant' => 2.0,
            'uc_heures_tpa_etudiant' => 20.0,
            'uc_heures_tc_etudiant' => null,
            'uc_heures_fmp_etudiant' => null,
            'student_total_hours_he' => 106.5,
            'uc_heures_he_aas_etudiant' => 11.0,
            'uc_heures_he_tpers_etudiant' => 95.5,
            'student_grand_total_hours' => 172.5,
            'uc_ects' => 6.0,
        ];
        $this->assertEquals(
            $expected,
            $courseheaderdataprocessor->compute_header_data()
        );

        // Now check that if we don't provide the custom fields, it still works.
        $courseheaderdataprocessorwc = new course_header_data($course->id);
        $this->assertEquals(
            $expected,
            $courseheaderdataprocessorwc->compute_header_data()
        );
    }


    /**
     * Test compute header data with old syllabus (without new programme feature)
     */
    public function test_compute_header_data_old_syllabus(): void {
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category();
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', 'sample-course-old.json'));
        $coursedef = json_decode($json, true);
        $coursedef['category'] = $category->id;
        $course = $this->create_course_from_def($coursedef);
        $coursecfs = course_handler::create()->get_instance_data($course->id, true);
        // Disable new syllabus feature for this course.
        set_config('enablenewprogramme', 0, 'local_envasyllabus');
        $courseheaderdataprocessor = new course_header_data($course->id, $coursecfs);
        // On ne garde que les colonnes et les sommes pour la comparaison.
        $expected = [
            'uc_departement' => 'DSBP',
            'student_total_hours' => 66.0,
            'uc_heures_cm_etudiant' => 18.0,
            'uc_heures_td_etudiant' => 26.0,
            'uc_heures_tp_etudiant' => 2.0,
            'uc_heures_tpa_etudiant' => 20.0,
            'uc_heures_tc_etudiant' => null,
            'uc_heures_fmp_etudiant' => null,
            'student_total_hours_he' => 106.5,
            'uc_heures_he_aas_etudiant' => 11.0,
            'uc_heures_he_tpers_etudiant' => 95.5,
            'student_grand_total_hours' => 172.5,
            'uc_ects' => 6.0,
        ];
        $this->assertEquals(
            $expected,
            $courseheaderdataprocessor->compute_header_data()
        );
    }
}
