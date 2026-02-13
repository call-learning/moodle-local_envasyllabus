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

/**
 * Common lib
 *
 * @package     local_envasyllabus
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add navigation for course
 *
 * @param navigation_node $node An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 */
function local_envasyllabus_extend_navigation_course($node, $course, $module) {
    global $CFG;
    if (!$CFG->enableenvasyllabus) {
        return;
    }
    $coursecontext = \context_course::instance($course->id);
    $url = new moodle_url('/local/envasyllabus/syllabuspage.php', ['id' => $course->id]);
    $newnode = new navigation_node(
        [
            'text' => get_string('syllabuspage:menu', 'local_envasyllabus'),
            'action' => $url,
            'type' => navigation_node::TYPE_SETTING,
            'icon' => new pix_icon('t/viewdetails', ''),
            'key' => 'envasyllabus',
        ]
    );
    $node->add_node($newnode);
    // Now add the index.
    $url = new moodle_url('/local/envasyllabus/index.php');
    $newnode = new navigation_node(
        [
            'text' => get_string('catalog:index', 'local_envasyllabus'),
            'action' => $url,
            'type' => navigation_node::TYPE_SETTING,
            'icon' => new pix_icon('t/viewdetails', ''),
            'key' => 'catalogindex',
        ]
    );
    $node->add_node($newnode);

    // Add the report node for report to the user node.

    if (has_capability('moodle/reportbuilder:view', $coursecontext)) {
        // Add the reports link.
        $url = new moodle_url('/local/envasyllabus/reports.php');
        $newnode = new navigation_node(
            [
                'text' => get_string('syllabusreports', 'local_envasyllabus'),
                'action' => $url,
                'type' => navigation_node::TYPE_CUSTOM,
                'icon' => new pix_icon('t/reports', ''),
                'key' => 'envasyllabusreports',
            ]
        );
        $reportnode = $node->get('coursereports', navigation_node::TYPE_CONTAINER);
        if ($reportnode) {
            $reportnode->add_node($newnode);
        }
    }
}

/**
 * Add navigation for user
 *
 * @param navigation_node $usernode
 * @param stdClass $user
 * @param \core\context\user $usercontext
 * @param stdClass $course
 * @param \core\context $coursecontext
 * @throws coding_exception
 * @throws dml_exception
 */
function local_envasyllabus_extend_navigation_user(
    navigation_node $usernode,
    stdClass $user,
    \core\context\user $usercontext,
    stdClass $course,
    \core\context $coursecontext,
) {
    if (has_capability('moodle/reportbuilder:edit', \context_system::instance(), $user)) {
        // Add the reports link.
        $url = new moodle_url('/local/envasyllabus/reports.php');
        $newnode = new navigation_node(
            [
                'text' => get_string('reports', 'local_envasyllabus'),
                'action' => $url,
                'type' => navigation_node::TYPE_CUSTOM,
                'icon' => new pix_icon('t/reports', ''),
                'key' => 'envasyllabusreports',
            ]
        );
        $usernode->add_node($newnode);
    }
}
/**
 * Insert "View Syllabus" Button in course header
 *
 * @return void
 */
function local_envasyllabus_before_standard_top_of_body_html() {
    global $PAGE;
    $context = $PAGE->context;
    if ($context->contextlevel == CONTEXT_COURSE && $context->instanceid != SITEID) {
        if (strpos(trim(strtolower($PAGE->course->shortname)), 'uc') === 0) {
            $canedit = has_capability('customfield/sprogramme:edit', $context);
            $PAGE->requires->js_call_amd('local_envasyllabus/syllabus_button', 'init', [$PAGE->course->id]);
        }
    }
}

/**
 * Specific icons for the module
 * @return string[]
 */
function local_envasyllabus_get_fontawesome_icon_map() {
    return [
        'local_envasyllabus:i/languages' => 'fa-language',
        'local_envasyllabus:i/arrowview' => 'fa-arrow-circle-o-right',
    ];
}

/**
 * Serves files from the local_envasyllabus file areas
 *
 * @package     local_envasyllabus
 * @category    files
 * @param stdClass $course course object
 * @param stdClass $cm course module object (not used for local plugins)
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function local_envasyllabus_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $CFG;

    // Check if user is logged in and has permission to download files.
    require_login();

    // For system context files, check if user has admin capabilities or is specifically allowed.
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        // Only allow users with capability to configure the plugin or site admins.
        if (!has_capability('moodle/site:config', $context)) {
            return false;
        }
    }

    // Check filearea.
    if ($filearea !== 'spreadsheet_exports') {
        return false;
    }

    // Get the file.
    $fs = get_file_storage();

    // The relative path is built from the args.
    $itemid = array_shift($args); // Should be 0 for our files.
    $filename = array_pop($args); // Get the filename.
    $filepath = '/' . implode('/', $args) . '/';
    if ($filepath == '//') {
        $filepath = '/';
    }

    // Try to get the file.
    $file = $fs->get_file($context->id, 'local_envasyllabus', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    // Send the file.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
