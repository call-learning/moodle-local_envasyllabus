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

namespace local;

use core_course\customfield\course_handler;
use local_envasyllabus\external\get_filtered_courses;
use local_envasyllabus\local\course_syllabus_helper;
use local_envasyllabus\tests\test_helper;

/**
 * Unit tests for course syllabus helper
 *
 * @package    local_envasyllabus
 * @copyright  2024 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_syllabus_helper_test extends \advanced_testcase {
    use test_helper;

    /**
     * Test the is_new_programme_enabled function.
     *
     * @covers ::is_new_programme_enabled
     */
    public function test_is_new_programme_enabled(): void {
        $this->resetAfterTest();
        // Create a course to test with.
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Test Course']);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Test Course2']);
        $course3 = $this->getDataGenerator()->create_course(['fullname' => 'Test Course3']);
        set_config('enablenewprogramme', 1, 'local_envasyllabus');
        set_config('enablenewprogrammeforcourse', '', 'local_envasyllabus');
        $this->assertTrue(course_syllabus_helper::is_new_programme_enabled($course1->id));
        $this->assertTrue(course_syllabus_helper::is_new_programme_enabled($course2->id));
        $this->assertTrue(course_syllabus_helper::is_new_programme_enabled($course3->id));
        set_config('enablenewprogramme', 0, 'local_envasyllabus');
        set_config('enablenewprogrammeforcourse', "$course1->id, $course2->id, na", 'local_envasyllabus');
        $this->assertTrue(course_syllabus_helper::is_new_programme_enabled($course1->id));
        $this->assertTrue(course_syllabus_helper::is_new_programme_enabled($course2->id));
        $this->assertFalse(course_syllabus_helper::is_new_programme_enabled($course3->id));
        set_config('enablenewprogrammeforcourse', '', 'local_envasyllabus');
        $this->assertFalse(course_syllabus_helper::is_new_programme_enabled($course1->id));
        $this->assertFalse(course_syllabus_helper::is_new_programme_enabled($course2->id));
        $this->assertFalse(course_syllabus_helper::is_new_programme_enabled($course3->id));
    }

    /**
     * Test the programme sum calculation
     */
    public function test_programme_sum(): void {
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category();
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', 'sample-course.json'));
        $coursedef = json_decode($json, true);
        $coursedef['category'] = $category->id;
        $course = $this->create_course_from_def($coursedef);
        $coursecfs = course_handler::create()->get_instance_data($course->id, true);
        $sprogrammefield = course_syllabus_helper::get_programme_customfield($course->id, $coursecfs);
        $programmesums = [];
        if ($sprogrammefield && $sprogrammefield->get('id')) {
            $programmesums = $sprogrammefield->get_sum();
        }
        $programmesumsforcourse = course_syllabus_helper::process_programme_values(
            $programmesums
        );
        // On ne garde que les colonnes et les sommes pour la comparaison.
        $expected = [
            'cm' => 18,
            'td' => 26,
            'tp' => 2,
            'tpa' => 20,
            'tc' => 0,
            'aas' => 11,
            'fmp' => 0,
            'perso' => 95.5,
            'active' => 76,
            'total' => 172.5,
        ];
        $this->assertEquals(
            $expected,
            array_column(
                $programmesumsforcourse,
                'sum',
                'column'
            )
        );
    }

    /**
     * Test get course additional info
     */
    public function test_get_course_additional_info(): void {
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category(['idnumber' => 'CAT1']);
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', 'sample-course.json'));
        $coursedef = json_decode($json, true);
        $coursedef['category'] = 'CAT1';
        $course = $this->create_course_from_def($coursedef);
        $category = \core_course_category::get($category->id);
        $categorycourses = $category->get_courses(['recursive' => true, 'coursecontacts' => true]);
        $courselistelement = null;
        foreach ($categorycourses as $c) {
            // Load custom fields for all courses in the category.
            if ($c->id === $course->id) {
                $courselistelement = $c;
            }
        }

        $course = (object) iterator_to_array($courselistelement->getIterator(), true);
        $course->contextid = $courselistelement->get_context()->id;
        $course->categoryid = $course->category;
        $customfields = course_handler::create()->get_instance_data($course->id, true);
        $courseinfo = course_syllabus_helper::get_course_additional_info(
            $course,
            $customfields,
            $courselistelement->get_course_overviewfiles()
        );
        $expected = [
            'cm' => 18.0,
            'td' => 26.0,
            'tp' => 2.0,
            'tpa' => 20.0,
            'tc' => 0.0,
            'aas' => 11.0,
            'fmp' => 0.0,
            'perso' => 95.5,
            'active' => 76.0,
            'total' => 172.5,
        ];
        $progremmevalues = array_column($courseinfo->programmevalues, 'sum', 'column');
        $this->assertEquals(
            $expected,
            $progremmevalues
        );
    }
}
