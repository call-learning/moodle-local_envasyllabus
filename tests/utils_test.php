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

use core\di;
use local_envasyllabus\output\language_switcher;

/**
 * Tests for the get_filtered_courses class.
 *
 * @package     local_envasyllabus
 * @copyright   2025 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_envasyllabus\utils
 */
final class utils_test extends \advanced_testcase {
    /**
     * Test get_string_current_lang function.
     *
     * @covers ::get_string_current_lang
     */
    public function test_get_string_current_lang(): void {
        $this->resetAfterTest();
        di::set(\core_string_manager::class, testable_string_manager::class);
        // Test default language (fr).
        language_switcher::set_lang('fr');
        $str1 = utils::get_string_current_lang('cf:uc_annee', 'local_envasyllabus');
        $this->assertEquals('Année', $str1);
        // Test English language.
        language_switcher::set_lang('en');
        $str2 = utils::get_string_current_lang('cf:uc_annee', 'local_envasyllabus', null);
        $this->assertEquals('Year', $str2);
    }
}

/**
 * Testable string manager class.
 */
class testable_string_manager extends \core_string_manager_standard {
    #[\Override]
    public function get_list_of_translations($returnall = false) {
        return ['en' => 'English', 'fr' => 'French'];
    }
};