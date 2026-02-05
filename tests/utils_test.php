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

/**
 * Test for utils class.
 *
 * @package     local_envasyllabus
 * @copyright   2025 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_envasyllabus;
defined('MOODLE_INTERNAL') || die();
use local_envasyllabus\output\language_switcher;

/**
 * Tests for the get_filtered_courses class.
 *
 * @package     local_envasyllabus
 * @copyright   2025 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_envasyllabus\utils
 */
final class utils_test extends \advanced_testcase {
    /**
     * Test get_string_current_lang function.
     */
    public function test_get_string_current_lang(): void {
        $this->resetAfterTest();
        testable_string_manager_for_current_language_tests::set_fake_list_of_installed_languages(
            ['en' => 'English', 'fr' => 'French']
        );
        $this->markTestSkipped();
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
 * Test helper class for test which need Moodle to think there are other languages installed.
 *
 * @copyright 2022 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_envasyllabus
 */
class testable_string_manager_for_current_language_tests extends \core_string_manager_standard {
    /** @var array $installedlanguages list of languages which we want to pretend are installed. */
    protected $installedlanguages;

    /**
     * Start pretending that the list of installed languages is other than what it is.
     *
     * You need to pass in an array like ['en' => 'English', 'fr' => 'French'].
     *
     * @param array $installedlanguages the list of languages to assume are installed.
     */
    public static function set_fake_list_of_installed_languages(array $installedlanguages): void {
        global $CFG;

        // Re-create the custom string-manager instance using this class, and force the thing we are overriding.
        $oldsetting = $CFG->config_php_settings['customstringmanager'] ?? null;
        $CFG->config_php_settings['customstringmanager'] = self::class;
        get_string_manager(true)->installedlanguages = $installedlanguages;

        // Reset the setting we overrode.
        unset($CFG->config_php_settings['customstringmanager']);
        if ($oldsetting) {
            $CFG->config_php_settings['customstringmanager'] = $oldsetting;
        }
    }

    /**
     * Must be called at the end of any test which called set_fake_list_of_installed_languages to reset things.
     */
    public static function reset_installed_languages_override(): void {
        get_string_manager(true);
    }

    /**
     * Get the list of installed languages.
     *
     * @param bool $returnall not used, just here to match the signature of the parent method.
     * @return array the list of installed languages.
     */
    public function get_list_of_translations($returnall = false) {
        return $this->installedlanguages;
    }
}
