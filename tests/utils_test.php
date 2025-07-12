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
namespace local_envasyllabus;

/**
 * Tests for the get_filtered_courses class.
 *
 * @package     local_envasyllabus
 * @copyright   2025 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_envasyllabus\utils
 */
class utils_test extends \advanced_testcase {

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
        $this->assertTrue(utils::is_new_programme_enabled($course1->id));
        $this->assertTrue(utils::is_new_programme_enabled($course2->id));
        $this->assertTrue(utils::is_new_programme_enabled($course3->id));
        set_config('enablenewprogramme', 0, 'local_envasyllabus');
        set_config('enablenewprogrammeforcourse', "$course1->id, $course2->id, na", 'local_envasyllabus');
        $this->assertTrue(utils::is_new_programme_enabled($course1->id));
        $this->assertTrue(utils::is_new_programme_enabled($course2->id));
        $this->assertFalse(utils::is_new_programme_enabled($course3->id));
        set_config('enablenewprogrammeforcourse', '', 'local_envasyllabus');
        $this->assertFalse(utils::is_new_programme_enabled($course1->id));
        $this->assertFalse(utils::is_new_programme_enabled($course2->id));
        $this->assertFalse(utils::is_new_programme_enabled($course3->id));
    }
}
