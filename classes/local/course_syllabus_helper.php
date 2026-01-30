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

use customfield_sprogramme\data_controller;
use local_envasyllabus\visibility;
use moodle_url;

/**
 * Course header data helper class.
 *
 * @package    local_envasyllabus
 * @copyright  2026 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_syllabus_helper {
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
     * @param array|null $coursecustomfieldsdata An array of course custom fields data controllers.
     * @return data_controller|null The found custom field or null if not found.
     * @throws \moodle_exception
     */
    public static function get_programme_customfield(
        int $courseid,
        ?array $coursecustomfieldsdata = null
    ): ?\customfield_sprogramme\data_controller {
        if ($coursecustomfieldsdata === null) {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $coursecustomfieldsdata = $handler->get_instance_data($courseid, true);
        }

        $filtered = array_filter($coursecustomfieldsdata, function ($cfdatacontroller) {
            $field = $cfdatacontroller->get_field();
            return $field->get('type') == 'sprogramme'
                && $field->get('shortname') == 'programme';
        });
        if (!empty($filtered)) {
            return array_values($filtered)[0];
        }
        return null;
    }

    /**
     * Check if a course has the new programme custom field defined.
     *
     * @param int $courseid The course ID to check.
     * @return bool True if the course has the new programme custom field, false otherwise.
     */
    public static function has_new_programme_data(int $courseid): bool {
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $cfdata = $handler->get_instance_data($courseid, true);
        $sprogrammefield = self::get_programme_customfield($courseid, $cfdata);
        return $sprogrammefield
            && $sprogrammefield->get_value() // Ensure the programme is enabled on this course.
            && self::is_new_programme_enabled($courseid);
    }


    /**
     * Add course info to a course object.
     *
     * @param object $course
     * @param array $coursecfs
     * @param array $overviewfiles
     * @return object
     */
    public static function get_course_additional_info(
        object $course,
        array $coursecfs,
        ?array $overviewfiles = null
    ): object {
        $sprogrammefield = self::get_programme_customfield($course->id, $coursecfs);
        $programmesums = [];
        if ($sprogrammefield && $sprogrammefield->get('id')) {
            $programmesums = $sprogrammefield->get_sum();
        }
        $courseinfo = new \stdClass();
        $courseinfo->programmevalues = self::process_programme_values($programmesums);
        $courseinfo->categoryname = self::get_category_name_for_id($course->categoryid);
        $courseinfo->courseimageurl = (new moodle_url('/local/envasyllabus/pix/nocourseimage.jpg'))->out();
        if ($overviewfiles && !empty($overviewfiles)) {
            $file = array_shift($overviewfiles);
            if ($file->is_valid_image()) {
                $courseinfo->courseimageurl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
            }
        }

        $courseinfo->managers = !empty($course->managers)
            ? array_map(fn($manager) => ['id' => $manager->id, 'fullname' => fullname($manager)], $course->managers)
            : [];

        $responsible = self::get_roleusers_for_course($course->id, ['responsablecourse']);
        $courseinfo->responsible = !empty($responsible)
            ? array_map(fn($manager) => ['id' => $manager->id, 'fullname' => fullname($manager)], $responsible)
            : [];

        $courseinfo->customfields = [];
        foreach ($coursecfs as $cfdatacontroller) {
            $fieldshortname = $cfdatacontroller->get_field()->get('shortname');
            if (visibility::is_syllabus_public_field($fieldshortname)) {
                $courseinfo->customfields[$fieldshortname] = [
                    'type' => $cfdatacontroller->get_field()->get('type'),
                    'value' => $cfdatacontroller->export_value(),
                    'name' => $cfdatacontroller->get_field()->get('name'),
                    'shortname' => $fieldshortname,
                ];
            }
        }
        return $courseinfo;
    }


    /**
     * Process programme values to return the correct structure.
     * perso_av and perso_ap are summed up in perso.
     *
     * @param array $programmesums
     * @return array
     */
    public static function process_programme_values(array $programmesums): array {
        $programmevalues = [];
        $persosum = 0;
        $totalvalue = 0;
        foreach ($programmesums as $column) {
            if ($column['column'] == 'perso_av' || $column['column'] == 'perso_ap') {
                $persosum += floatval($column['sum']);
            } else {
                $programmevalues[] = $column;
            }
            $totalvalue += floatval($column['sum']);
        }
        $perso = [
            'columnid' => 0,
            'column' => 'perso',
            'label' => 'Perso',
            'sum' => $persosum,
        ];
        $activepercentage = self::get_active_percentage($programmesums);
        $active = [
            'columnid' => 0,
            'column' => 'active',
            'label' => '% Actif',
            'sum' => $activepercentage,
        ];
        $total = [
            'columnid' => 0,
            'column' => 'total',
            'label' => 'Total',
            'sum' => $totalvalue,
        ];
        $programmevalues[] = $perso;
        $programmevalues[] = $active;
        $programmevalues[] = $total;
        return $programmevalues;
    }

    /**
     * get the %active column value
     * correspond to % of active learning methods used for the course
     * The formula is : « % actif » = (TD + TP + TPa + AAS + TC + FMP) / (CM + TD + TP + TPa + AAS + TC + FMP)
     * @param array $programmesums
     * @return float
     */
    public static function get_active_percentage(array $programmesums): float {
        $sumarray = [];
        foreach ($programmesums as $entry) {
            if (isset($entry['column'])) {
                $key = strtolower($entry['column']);
                $sumarray[$key] = $entry;
            }
        }

        $keys = ['cm', 'td', 'tp', 'tpa', 'aas', 'tc', 'fmp'];

        foreach ($keys as $key) {
            $$key = floatval($sumarray[$key]['sum'] ?? 0);
        }
        $active = $td + $tp + $tpa + $aas + $tc + $fmp;
        $total = $cm + $td + $tp + $tpa + $aas + $tc + $fmp;
        if ($total == 0) {
            return 0.0; // Avoid division by zero.
        }
        $activepercentage = ($active / $total) * 100;
        return floor($activepercentage);
    }

    /**
     * Get category name for the given id
     *
     * @param int $categoryid
     * @return mixed|string
     * @throws \moodle_exception
     */
    protected static function get_category_name_for_id(int $categoryid) {
        static $categories = [];
        if (empty($categories[$categoryid])) {
            $category = \core_course_category::get($categoryid);
            $categories[$categoryid] = $category->get_formatted_name();
        }
        return $categories[$categoryid];
    }

    /**
     * Get users matching the teacher role.
     *
     * @param int $courseid
     * @param array $rolesname
     * @return array
     */
    protected static function get_roleusers_for_course(int $courseid, array $rolesname): array {
        global $DB;
        [$where, $params] = $DB->get_in_or_equal($rolesname);
        $teacherroles = $DB->get_fieldset_select('role', 'id', 'shortname ' . $where, $params);
        if (!empty($teacherroles)) {
            $userfieldsapi = \core_user\fields::for_userpic()->including('username', 'deleted');
            $userfields = 'ra.id, u.id, u.username' . $userfieldsapi->get_sql('u')->selects;
            return get_role_users($teacherroles, \context_course::instance($courseid), true, $userfields);
        } else {
            return [];
        }
    }

    /**
     * Get all role users for multiple courses at once.
     *
     * @param array $courseids
     * @param array $rolesname
     * @return array Keyed by courseid
     */
    protected static function get_roleusers_for_courses(array $courseids, array $rolesname): array {
        global $DB;

        if (empty($courseids)) {
            return [];
        }

        [$roleswhere, $rolesparams] = $DB->get_in_or_equal($rolesname);
        $teacherroles = $DB->get_fieldset_select('role', 'id', 'shortname ' . $roleswhere, $rolesparams);

        if (empty($teacherroles)) {
            return [];
        }

        [$roleswhere, $rolesparams] = $DB->get_in_or_equal($teacherroles);
        [$courseswhere, $coursesparams] = $DB->get_in_or_equal($courseids);

        $params = array_merge($rolesparams, $coursesparams);

        $userfieldsapi = \core_user\fields::for_userpic()->including('username', 'deleted');
        $userfields = $userfieldsapi->get_sql('u')->selects;

        $sql = "SELECT CONCAT(ctx.instanceid,'_',ra.id, '_', u.id) as id,
                    ctx.instanceid as courseid,
                    ra.id as raid,
                    u.id as userid,
                    u.username {$userfields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = " . CONTEXT_COURSE . "
                  JOIN {user} u ON u.id = ra.userid
                 WHERE ra.roleid {$roleswhere}
                   AND ctx.instanceid {$courseswhere}
                   AND u.deleted = 0
              ORDER BY ctx.instanceid, u.lastname, u.firstname";

        $records = $DB->get_records_sql($sql, $params);

        // Group by course.
        $result = [];
        foreach ($records as $record) {
            $courseid = $record->courseid;
            if (!isset($result[$courseid])) {
                $result[$courseid] = [];
            }
            $record->id = $record->userid; // For compatibility with get_role_users().
            $result[$courseid][$record->userid] = $record;
        }

        return $result;
    }

}
