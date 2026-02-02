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

use cache;
use cache_store;
use customfield_sprogramme\data_controller;
use local_envasyllabus\output\language_switcher;
use local_envasyllabus\utils;
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
     * @var array TEACHER_ROLES_NAME
     */
    const TEACHER_ROLES_NAME = ['editingteacher', 'teacher'];

    /**
     * @var string RESPONSABLE_ROLES_NAME
     */
    const RESPONSABLE_ROLE_NAME = 'responsablecourse';

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
     * @param array|null $overviewfiles
     * @return object
     */
    public static function get_course_additional_info(
        object $course,
        array $coursecfs,
        ?array $overviewfiles = null
    ): object {
        $sprogrammefield = self::get_programme_customfield($course->id, $coursecfs);
        $courseinfo = new \stdClass();
        $courseinfo->programmevalues = self::process_programme_values($sprogrammefield);
        $courseinfo->categoryname = self::get_category_name_for_id($course->category);
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

        $responsible = course_syllabus_helper::get_responsible_for_course($course->id);
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
     * @param data_controller $sprogrammefield
     * @return array
     */
    public static function process_programme_values(data_controller $sprogrammefield): array {
        $programmesums = [];
        if ($sprogrammefield && $sprogrammefield->get('id')) {
            $programmesums = $sprogrammefield->get_sum();
        }
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
            'label' => get_string('catalog:extended:perso', 'local_envasyllabus'),
            'sum' => $persosum,
        ];
        $activepercentage = self::get_active_percentage(
            array_column($programmesums, 'sum', 'column')
        );
        $active = [
            'columnid' => 0,
            'column' => 'active',
            'label' => get_string('catalog:extended:percentactive', 'local_envasyllabus'),
            'sum' => $activepercentage,
        ];
        $total = [
            'columnid' => 0,
            'column' => 'total',
            'label' => get_string('catalog:extended:total', 'local_envasyllabus'),
            'sum' => $totalvalue,
        ];
        $programmevalues[] = $perso;
        $programmevalues[] = $active;
        $programmevalues[] = $total;
        return $programmevalues;
    }

    /**
     * Get the %active column value
     * correspond to % of active learning methods used for the course
     * The formula is : « % actif » = (TD + TP + TPa + AAS + TC + FMP) / (CM + TD + TP + TPa + AAS + TC + FMP) * 100
     * @param array $programmevalues an indexed array of programme values with 'column' and 'sum' keys
     * @return float
     */
    public static function get_active_percentage(array $programmevalues): float {
        $sums = array_map(fn($v) => floatval($v ?? 0), $programmevalues);
        // ((TD+TP+TPa+TC+AAS+FMP) / (CM+TD+TP+TPa+TC+AAS+FMP)) * 100
        $total = array_sum(
            array_intersect_key(
                $sums,
                array_flip(['cm', 'td', 'tp', 'tpa', 'tc', 'aas', 'fmp'])
            )
        );
        $active = array_sum(
            array_intersect_key(
                $sums,
                array_flip(['td', 'tp', 'tpa', 'tc', 'aas', 'fmp'])
            )
        );
        if ($total == 0) {
            return 0.0;
        }
        return floor(($active / $total) * 100);
    }

    /**
     * Compute semester totals from filtered course data (aligné sur la logique JS).
     *
     * @param array $filteredcourse
     * @param array $programmecolumns Liste ordonnée des colonnes de programme (avec totaltype)
     * @return array
     */
    public static function compute_semester_totals(array $filteredcourse, array $programmecolumns = []): array {
        $courselist = self::build_course_list($filteredcourse);
        $totals = [];
        $programmecolumnindexed = array_column($programmecolumns, null, 'column');
        foreach ($courselist as $yeardef) {
            foreach ($yeardef['semesters'] as $semesterdef) {
                $total = [
                    'ects' => 0,
                    'programmevalues' => [],
                    'semester' => $semesterdef['semester'],
                    'year' => $semesterdef['year'],
                    'courseidlist' => [],
                ];
                $programmemap = [];
                foreach ($semesterdef['courses'] as $course) {
                    $ects = floatval($course->customfields['uc_ects']['value'] ?? 0);
                    $total['ects'] += $ects;
                    if (!empty($course->programmevalues)) {
                        foreach ($course->programmevalues as $pv) {
                            $col = $pv['column'];
                            $pvvalue = floatval($pv['sum']) ?? 0;
                            $programmemap[$col] = ($programmemap[$col] ?? 0) + $pvvalue;
                        }
                    }
                    $total['courseidlist'][] = ['id' => $course->id];
                }
                foreach ($programmemap as $column => $sum) {
                    $value = $sum;
                    if (isset($programmecolumnindexed[$column])) {
                        $coldef = $programmecolumnindexed[$column];
                        if (!empty($coldef['totaltype']) && $coldef['totaltype'] == 'activepercentages') {
                            $value = self::get_active_percentage($programmemap);
                        }
                    }
                    $total['programmevalues'][] = [
                        'column' => $column,
                        'sum' => $value,
                    ];
                }
                $total['ects'] = round($total['ects'], 2);
                $totals[] = $total;
            }
        }
        return $totals;
    }

    /**
     * Process programme header values to return the correct structure.
     * perso_av and perso_ap are summed up in perso.
     *
     * @param array $columns
     * @return array
     */
    public static function process_programme_header(array $columns): array {
        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'local_envasyllabus', 'filtered_course');
        $currentlang = language_switcher::get_current_langcode();
        if ($cached = $cache->get('programme_headers' . $currentlang)) {
            return $cached;
        }
        $programmecolumns = [];
        foreach ($columns as $column) {
            if ($column['column'] == 'perso_av' || $column['column'] == 'perso_ap') {
                continue; // Skip perso_av and perso_ap, they will be summed up in perso.
            }
            $programmecolumns[] = $column;
        }
        $persocolumn = [
            'columnid' => 0, // This is not a real column id, but we need it to be able to display the column.
            'column' => 'perso',
            'label' => 'Perso',
            'help' => utils::get_string_current_lang('perso_help', 'local_envasyllabus'),
        ];
        $activecolumn = [
            'columnid' => 0, // This is not a real column id, but we need it to be able to display the column.
            'column' => 'active',
            'label' => '%Actif',
            'help' => utils::get_string_current_lang('active_help', 'local_envasyllabus'),
            'totaltype' => 'activepercentages',
        ];
        $totalcolumn = [
            'columnid' => 0, // This is not a real column id, but we need it to be able to display the column.
            'column' => 'total',
            'label' => 'Total',
            'help' => utils::get_string_current_lang('total_help', 'local_envasyllabus'),
        ];

        $programmecolumns[] = $persocolumn;
        $programmecolumns[] = $activecolumn;
        $programmecolumns[] = $totalcolumn;

        $cache->set('programme_headers', $programmecolumns);
        return $programmecolumns;
    }

    /**
     * Build course list sorted by year and semester (like JavaScript buildCourseList)
     *
     * @param array $courses
     * @return array
     */
    private static function build_course_list(array $courses): array {
        $sortedcourses = [];

        foreach ($courses as $course) {
            $yearvalue = self::find_value_for_custom_field($course, 'uc_annee');
            $semestervalue = self::find_value_for_custom_field($course, 'uc_semestre');

            if ($yearvalue) {
                if (!isset($sortedcourses[$yearvalue])) {
                    $sortedcourses[$yearvalue] = [
                        'year' => $yearvalue,
                        'semesters' => [],
                    ];
                }

                if (!isset($sortedcourses[$yearvalue]['semesters'][$semestervalue])) {
                    $sortedcourses[$yearvalue]['semesters'][$semestervalue] = [
                        'semester' => $semestervalue,
                        'year' => $yearvalue,
                        'courses' => [],
                    ];
                }

                $sortedcourses[$yearvalue]['semesters'][$semestervalue]['courses'][] = $course;
            }
        }

        uksort($sortedcourses, fn($a, $b) => $a <=> $b);
        foreach ($sortedcourses as &$yeardef) {
            uksort($yeardef['semesters'], fn($a, $b) => $a <=> $b);
        }

        return array_values($sortedcourses);
    }

    /**
     * Find value for custom field
     *
     * @param \stdClass $course
     * @param string $fieldname
     * @param mixed $defaultvalue
     * @return mixed
     */
    private static function find_value_for_custom_field(\stdClass $course, string $fieldname, $defaultvalue = null) {
        if (!empty($course->customfields)) {
            foreach ($course->customfields as $field) {
                if ($field['shortname'] === $fieldname) {
                    return $field['value'];
                }
            }
        }
        return $defaultvalue;
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
    public static function get_users_with_roles(int $courseid, array $rolesname = self::TEACHER_ROLES_NAME): array {
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
     * Get users matching the responsible role.
     *
     * @param int $courseid
     * @param array $rolesname
     * @return array
     */
    public static function get_responsible_for_course(int $courseid): array {
        return self::get_users_with_roles($courseid, [self::RESPONSABLE_ROLE_NAME]);
    }

    /**
     * Get the role definition for the responsible role.
     *
     * @return \stdClass|null
     */
    public static function get_responsible_role(): ?\stdClass {
        global $DB;
        $role = $DB->get_record('role', ['shortname' => self::RESPONSABLE_ROLE_NAME], '*');
        if ($role) {
            return $role;
        }
        return null;
    }
}
