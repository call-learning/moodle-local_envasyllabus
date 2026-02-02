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

namespace local_envasyllabus\output;

use local_envasyllabus\local\course_syllabus_helper;
use local_envasyllabus\tests\test_helper;

/**
 * Tests for the course syllabus output class.
 *
 * @package     local_envasyllabus
 * @copyright   2025 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_envasyllabus\output\course_syllabus
 */
final class course_syllabus_test extends \advanced_testcase {
    use test_helper;

    /**
     * Test the export_for_template function.
     *
     * @covers ::export_for_template
     */
    public function test_export_for_template(): void {
        global $PAGE;
        $this->resetAfterTest();
        // Create a course to test with.
        $category = $this->getDataGenerator()->create_category();
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', 'sample-course.json'));
        $coursedef = json_decode($json, true);
        $coursedef['category'] = $category->id;
        $course = $this->create_course_from_def($coursedef);
        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user(['firstname' => 'Teacher', 'lastname' => 'One']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
        $responsable = $generator->create_user(['firstname' => 'Responsible', 'lastname' => 'One']);
        $this->getDataGenerator()->create_role(['shortname' => course_syllabus_helper::RESPONSABLE_ROLE_NAME, 'archetype' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($responsable->id, $course->id, course_syllabus_helper::RESPONSABLE_ROLE_NAME);
        $syllabus = new course_syllabus($course->id);
        $data = $syllabus->export_for_template($PAGE->get_renderer('local_envasyllabus'));
        $this->assertEquals('Responsible One', $data->managers);
        $this->assertEquals('Teacher One', $data->teachers[0]->userfullname);
        $this->assertEquals("UC0101 - Fundamentals of Veterinary Medicine", $data->coursedata->displayname);
        $expectedstats = [
            'Teaching department' => 'DSBP',
            'Total hours on timetable' => '66',
            'Lectures (CM)' => '18',
            'Tutorials (TD)' => '26',
            'Practical work (TP)' => '2',
            'Practical work on healthy animals (TPa)' => '20',
            'Clinical work (TC)' => '-',
            'External practical training (FMP)' => '-',
            'Out of timetable hours' => "106.5",
            'Supervised Self learning (AAS)' => '11',
            'Personal work' => "95.5",
            'Total hours' => "172.5",
            'ECTS credits' => '6',
        ];

        $actualstats = array_column($data->headerdata, 'value', 'title');
        $this->assertEquals($expectedstats, $actualstats);
    }
}
