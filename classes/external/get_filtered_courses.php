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

namespace local_envasyllabus\external;

use cache;
use context_course;
use context_system;
use core_course\customfield\course_handler;
use core_course_category;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use customfield_sprogramme\local\programme_manager;
use Exception;
use local_envasyllabus\utils;
use local_envasyllabus\visibility;
use moodle_url;

/**
 * External services
 *
 * @package     local_envasyllabus
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_filtered_courses extends external_api {
    /**
     * Small summary length
     */
    const SMALL_SUMMARY_LENGTH = 120;
    /**
     * Filter type : custom field
     */
    const TYPE_CUSTOM_FIELD = 'customfield';
    /**
     * Filter type : full text
     */
    const FULL_TEXT_SEARCH = 'fulltext';

    /**
     * Get courses
     *
     * @param int $rootcategoryid
     * @param object|null $currentlang current selected language (en only supported for now)
     * @param array|null $filters It contains a list of search filters
     * @param array $sort sort criteria
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function execute($rootcategoryid, $currentlang = 'fr', $filters = null, $sort = []) {
        $paramstocheck = [
            'rootcategoryid' => $rootcategoryid,
            'currentlang' => $currentlang,
        ];
        if ($filters) {
            $paramstocheck['filters'] = $filters;
        }
        if (!empty($sort)) {
            $paramstocheck['sort'] = $sort;
        }
        $params = self::validate_parameters(self::execute_parameters(), $paramstocheck);
        raise_memory_limit(MEMORY_HUGE);
        self::validate_context(context_system::instance());
        $courses = self::get_courses($params['rootcategoryid']);
        // Remove courses that are not visible to the current user.
        foreach ($courses as $id => $course) {
            $context = context_course::instance($course->id);
            $canupdatecourse = has_capability('moodle/course:update', $context);
            $canviewhiddencourses = has_capability('moodle/course:viewhiddencourses', $context);
            // Check if the course is visible in the site for the user.
            if ($course->visible || $canviewhiddencourses || $canupdatecourse) {
                continue;
            }
            // Now, check if we have access to the course, unless it was already checked.
            try {
                self::validate_context($context);
                continue;
            } catch (Exception $e) {
                // User can not access the course, check if they can see the public information about the course and return it.
                if (core_course_category::can_view_course_info($course)) {
                    continue;
                }
            }
            unset($courses[$id]); // We cannot view the course so, let's remove it.
        }
        // Now the filter.
        $currentlang = $params['currentlang'];
        $filteredcourse = self::filter_courses($courses, $params['filters'], $currentlang);

        // Compute small summary and title depending on current lang.
        foreach ($filteredcourse as $course) {
            $course->displayname = $course->fullname;
            if (!empty($course->customfields['uc_titre_' . $currentlang])) {
                if (!empty($course->customfields['uc_titre_' . $currentlang]['value'])) {
                    $course->displayname = html_to_text($course->customfields['uc_titre_' . $currentlang]['value']);
                }
            }
            $course->smallsummarytext = '';

            if (!empty($course->customfields['uc_summary_' . $currentlang])) {
                $course->smallsummarytext = html_to_text($course->customfields['uc_summary_' . $currentlang]['value']);

                if (strlen($course->smallsummarytext) > static::SMALL_SUMMARY_LENGTH) {
                    $course->smallsummarytext =
                        substr($course->smallsummarytext, 0, static::SMALL_SUMMARY_LENGTH)
                        . "...";
                }
                // Sometimes truncation leads to utf8 related issues.
                $course->smallsummarytext = clean_param($course->smallsummarytext, PARAM_RAW);
            }
        }

        self::sort_courses($filteredcourse, $sort);
        $columns = programme_manager::get_numeric_columns();
        return [
            'courses' => $filteredcourse,
            'programmecolumns' => self::process_programme_header($columns),
        ];
        return $filteredcourse;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'rootcategoryid' => new external_value(PARAM_INT, 'root category id'),
                'currentlang' => new external_value(PARAM_ALPHA, 'Current language code', VALUE_DEFAULT, 'fr'),
                'filters' =>
                    new external_multiple_structure(
                        new external_single_structure(
                            [
                                'type' => new external_value(PARAM_ALPHA, 'search type'),
                                'search' => new external_single_structure(
                                    [
                                        'field' => new external_value(PARAM_ALPHANUMEXT, 'field name'),
                                        'value' => new external_value(PARAM_RAW, 'field value'),
                                    ],
                                ),
                            ],
                        ),
                        'Filters',
                        VALUE_DEFAULT,
                        []
                    ),
                'sort' =>
                    new external_single_structure(
                        [
                            'field' => new external_value(PARAM_ALPHANUMEXT, 'field type'),
                            'order' => new external_value(PARAM_ALPHA, 'asc or desc'),
                        ],
                        'Sort',
                        VALUE_OPTIONAL,
                    ),
            ]
        );
    }

    /**
     * Get all courses
     *
     * @param int $rootcategoryid
     * @return array array of courses
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    protected static function get_courses(int $rootcategoryid): array {
        $category = \core_course_category::get($rootcategoryid);
        // First we get all courses id in this category. Normally this is cached by core.
        $categorycourses = $category->get_courses(['recursive' => true, 'coursecontacts' => true]);
        // Filter out course ID = 1.
        if (!empty($categorycourses[SITEID])) {
            unset($categorycourses[SITEID]);
        }

        $courses = [];
        $cache = cache::make('local_envasyllabus', 'courseinfo');
        $courseidstoload = [];

        // First pass: check cache.
        foreach ($categorycourses as $cid => $courselistelement) {
            if ($cached = $cache->get($cid)) {
                $courses[$cid] = $cached;
            } else {
                $courseidstoload[$cid] = $courselistelement;
            }
        }

        if (empty($courseidstoload)) {
            return $courses;
        }

        // Batch load custom fields for all uncached courses.
        $allcustomfields = course_handler::create()->get_instances_data(array_keys($courseidstoload), true);

        // Batch load role users.
        $allresponsible = self::get_roleusers_for_courses(array_keys($courseidstoload), ['responsablecourse']);

        // Second pass: build course objects.
        foreach ($courseidstoload as $cid => $courselistelement) {
            $course = (object) iterator_to_array($courselistelement->getIterator(), true);
            $course->contextid = $courselistelement->get_context()->id;
            $course->categoryid = $course->category;
            unset($course->category);

            // Get custom fields (already loaded in batch).
            $coursecfs = $allcustomfields[$cid] ?? [];
            $sprogrammefield = utils::get_programme_customfield($coursecfs);
            $programmesums = [];
            if ($sprogrammefield && $sprogrammefield->get('id')) {
                $programmesums = $sprogrammefield->get_sum(); // Specific to this custom field.
            }

            $course->programmevalues = self::process_programme_values($programmesums);
            $course->categoryname = self::get_category_name_for_id($course->categoryid);
            $course->courseimageurl = (new moodle_url('/local/envasyllabus/pix/nocourseimage.jpg'))->out();

            // Get responsible users (already loaded in batch).
            $course->responsible = $allresponsible[$cid] ?? [];

            // Handle course image.
            $overviewfiles = $courselistelement->get_course_overviewfiles();
            if ($overviewfiles) {
                $file = array_shift($overviewfiles);
                $course->courseimageurl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
            }

            // Format managers.
            if (!empty($course->managers)) {
                $course->managers = array_map(function ($manager) {
                    return [
                        'id' => $manager->id,
                        'fullname' => fullname($manager),
                    ];
                }, $course->managers);
            } else {
                $course->managers = [];
            }

            // Format responsible users.
            if (!empty($course->responsible)) {
                $course->responsible = array_map(function ($manager) {
                    return [
                        'id' => $manager->id,
                        'fullname' => fullname($manager),
                    ];
                }, $course->responsible);
            } else {
                $course->responsible = [];
            }

            // Process custom fields.
            $course->customfields = [];
            foreach ($coursecfs as $cfdatacontroller) {
                $fieldshortname = $cfdatacontroller->get_field()->get('shortname');
                $ispublicfield = visibility::is_syllabus_public_field($fieldshortname);
                if ($ispublicfield) {
                    $course->customfields[$fieldshortname] = [
                        'type' => $cfdatacontroller->get_field()->get('type'),
                        'value' => $cfdatacontroller->export_value(),
                        'name' => $cfdatacontroller->get_field()->get('name'),
                        'shortname' => $fieldshortname,
                    ];
                }
            }

            $cache->set($cid, $course);
            $courses[$cid] = $course;
        }

        return $courses;
    }

    /**
     * Process programme header values to return the correct structure.
     * perso_av and perso_ap are summed up in perso.
     * @param array $columns
     * @return array
     */
    public static function process_programme_header(array $columns): array {
        static $cachedheaders = null;

        if ($cachedheaders !== null) {
            return $cachedheaders;
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
            'help' => get_string('perso_help', 'local_envasyllabus'),
        ];
        $activecolumn = [
            'columnid' => 0, // This is not a real column id, but we need it to be able to display the column.
            'column' => 'active',
            'label' => '%Actif',
            'help' => get_string('active_help', 'local_envasyllabus'),
        ];
        $totalcolumn = [
            'columnid' => 0, // This is not a real column id, but we need it to be able to display the column.
            'column' => 'total',
            'label' => 'Total',
            'help' => get_string('total_help', 'local_envasyllabus'),
        ];

        $programmecolumns[] = $persocolumn;
        $programmecolumns[] = $activecolumn;
        $programmecolumns[] = $totalcolumn;

        $cachedheaders = $programmecolumns;
        return $programmecolumns;
    }

    /**
     * Process programme values to return the correct structure.
     * perso_av and perso_ap are summed up in perso.
     *
     * @param array $programmesums
     * @return array
     */
    private static function process_programme_values(array $programmesums): array {
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
     * Map custom fields
     *
     * @param array $courses
     * @return void
     */
    protected static function map_customfiedls(array &$courses): void {
        $allcustomfields = course_handler::create()->get_instances_data(array_keys($courses), true);
        foreach ($courses as $cid => &$course) {
            $coursecfs = $allcustomfields[$cid] ?? [];
            $course->customfields = [];
            foreach ($coursecfs as $cfdatacontroller) {
                $fieldshortname = $cfdatacontroller->get_field()->get('shortname');
                $ispublicfield = visibility::is_syllabus_public_field($fieldshortname);
                if ($ispublicfield) {
                    $course->customfields[$fieldshortname] = [
                        'type' => $cfdatacontroller->get_field()->get('type'),
                        'value' => $cfdatacontroller->export_value(),
                        'name' => $cfdatacontroller->get_field()->get('name'),
                        'shortname' => $fieldshortname,
                    ];
                }
            }
        }
    }

    /**
     * Filter courses through custom fields
     *
     * @param array $courses
     * @param array $filters
     * @param string $currentlang
     * @return array
     * @throws \coding_exception
     */
    protected static function filter_courses(array $courses, array $filters, string $currentlang): array {
        $filteredcourses = [];
        foreach ($courses as $cobject) {
            $addcourse = true;
            $coursecustomfieldsmatcher = [];
            foreach ($cobject->customfields as $cf) {
                $coursecustomfieldsmatcher[$cf['shortname']] = $cf['value'];
            }
            if (!empty($filters)) {
                foreach ($filters as $criterion) {
                    switch ($criterion['type']) {
                        case static::TYPE_CUSTOM_FIELD:
                            $search = $criterion['search'];
                            $searchfield = $search['field'];
                            if (!empty($search['value'])) {
                                if (empty($coursecustomfieldsmatcher[$searchfield])) {
                                    $addcourse = false;
                                }
                                $addcourse = $addcourse && ($coursecustomfieldsmatcher[$searchfield] == $search['value']);
                            }
                            break;
                        case static::FULL_TEXT_SEARCH:
                            // To Do: implement full text search.
                            break;
                    }
                }
            }
            if ($addcourse) {
                $filteredcourses[] = $cobject;
            }
        }
        return $filteredcourses;
    }

    /**
     * Sort courses list by fields
     *
     * @param array $courses passed by reference
     * @param array $sort
     * @return void
     */
    protected static function sort_courses(&$courses, $sort): void {
        if (!empty($sort)) {
            uasort($courses, function ($c1, $c2) use ($sort) {
                if (strpos($sort['field'], 'customfield_') === 0) {
                    $sortfieldname = str_replace('customfield_', '', $sort['field']);
                    $c1value = $c1->customfields[$sortfieldname]["value"] ?? '';
                    $c2value = $c2->customfields[$sortfieldname]["value"] ?? '';
                } else {
                    $c1value = $c1->{$sort['field']} ?? '';
                    $c2value = $c2->{$sort['field']} ?? '';
                }
                $sortfactor = $sort['order'] == 'asc' ? 1 : -1;
                if (is_string($c1value) && is_string($c2value)) {
                    return strcmp($c1value, $c2value) * $sortfactor;
                }
                if (is_int($c1value) && is_int($c2value)) {
                    return ($c1value < $c2value) ? -$sortfactor : $sortfactor;
                }
                return 0;
            });
        }
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

        $sql = "SELECT ctx.instanceid as courseid, ra.id as raid, u.id, u.username {$userfields}
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
            $result[$courseid][] = $record;
        }

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description|external_multiple_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'id' => new external_value(PARAM_INT, 'course id'),
                        'fullname' => new external_value(PARAM_RAW, 'course full name'),
                        'displayname' => new external_value(PARAM_RAW, 'course display name'),
                        'visible' => new external_value(PARAM_BOOL, 'is course visible', VALUE_OPTIONAL, false),
                        'shortname' => new external_value(PARAM_RAW, 'course short name'),
                        'categoryid' => new external_value(PARAM_INT, 'category id'),
                        'categoryname' => new external_value(PARAM_RAW, 'category name'),
                        'sortorder' => new external_value(PARAM_INT, 'Sort order in the category', VALUE_OPTIONAL),
                        'smallsummarytext' => new external_value(PARAM_RAW, 'smallsummarytext'),
                        'managers' => new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'id' => new external_value(PARAM_INT, 'contact user id'),
                                    'fullname' => new external_value(PARAM_NOTAGS, 'contact user fullname'),
                                ]
                            ),
                            'contact users'
                        ),
                        'programmevalues' => new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'columnid' => new external_value(PARAM_INT, 'The id of the custom field'),
                                    'column' => new external_value(PARAM_RAW, 'The name of the custom field'),
                                    'label' => new external_value(PARAM_RAW, 'The shortname of the custom field'),
                                    'sum' => new external_value(PARAM_FLOAT, 'The value of the custom field'),
                                ]
                            ),
                            'Custom fields',
                            VALUE_OPTIONAL
                        ),
                        'customfields' => new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'name' => new external_value(PARAM_RAW, 'The name of the custom field'),
                                    'shortname' => new external_value(
                                        PARAM_RAW,
                                        'The shortname of the custom field - to be able to build the field class in the code'
                                    ),
                                    'type' => new external_value(
                                        PARAM_ALPHANUMEXT,
                                        'The type of the custom field - text field, checkbox...'
                                    ),
                                    'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                                ]
                            ),
                            'Custom fields',
                            VALUE_OPTIONAL
                        ),
                        'courseimageurl' => new external_value(PARAM_URL, 'image url', VALUE_OPTIONAL),
                    ]
                )
            ),
            'programmecolumns' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'columnid' => new external_value(PARAM_INT, 'The id of the custom field'),
                        'column' => new external_value(PARAM_RAW, 'The name of the custom field'),
                        'label' => new external_value(PARAM_RAW, 'The shortname of the custom field '),
                        'help' => new external_value(PARAM_RAW, 'The help text for the custom field', VALUE_OPTIONAL, ''),
                    ]
                )
            ),
        ]);
    }
}
