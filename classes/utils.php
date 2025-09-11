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
     * Check if the new programme feature is enabled for a specific course.
     *
     * @param int $courseid The course ID to check.
     * @return bool True if the new programme feature is enabled for the course, false otherwise.
     */
    public static function is_new_programme_enabled(int $courseid): bool {
        $newprogrammeenabled = get_config('local_envasyllabus', 'enablenewprogramme');
        if ($newprogrammeenabled) {
            return true;
        }
        $courselist = get_config('local_envasyllabus', 'enablenewprogrammeforcourse');
        if (empty($courselist)) {
            return false;
        }
        $courselist = explode(',', trim($courselist));
        $courselist = array_map('trim', $courselist);
        $courselist = array_map('intval', $courselist);
        return in_array($courseid, $courselist, true);
    }

    /**
     * Get the custom field of type 'sprogramme' with shortname 'programme' from a list of course custom fields data.
     *
     * @param array $coursecustomfieldsdata An array of course custom fields data controllers.
     * @return \customfield_sprogramme\data_controller|null The found custom field or null if not found.
     */
    public static function get_programme_customfield(array $coursecustomfieldsdata): ?\customfield_sprogramme\data_controller {
        $filtered = array_filter($coursecustomfieldsdata, function($cfdatacontroller) {
            $field = $cfdatacontroller->get_field();
            return $field->get('type') == 'sprogramme'
                && $field->get('shortname') == 'programme';
        });
        if (!empty($filtered)) {
            return array_values($filtered)[0];
        }
        return null;
    }
}