<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_envasyllabus;

use core\context;
use core\di;
use customfield_sprogramme\data_controller;
use local_envasyllabus\output\language_switcher;

/**
 * Set of utility functions for the local_envasyllabus plugin.
 *
 * @package     local_envasyllabus
 * @category    admin
 * @copyright   2024 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Get the string in the current syllabus language.
     *
     * @param string $identifier The string identifier.
     * @param string $component The component name (default is '').
     * @param mixed $a Optional argument for string placeholders (default is null).
     * @return string The localized string.
     */
    public static function get_string_current_lang(string $identifier, string $component = '', mixed $a = null): string {
        $currentlang = language_switcher::get_current_langcode();
        $stringmanager = get_string_manager();
        return $stringmanager->get_string(
            $identifier,
            $component ?: 'local_envasyllabus',
            $a,
            $currentlang,
        );
    }
}