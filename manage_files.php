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
 * Manage spreadsheet files page
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Check permissions.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Set up page.
$PAGE->set_url('/local/envasyllabus/manage_files.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_spreadsheets', 'local_envasyllabus'));
$PAGE->set_heading(get_string('manage_spreadsheets', 'local_envasyllabus'));
$PAGE->set_pagelayout('admin');

// Handle file deletion.
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($delete && confirm_sesskey()) {
    $fs = get_file_storage();
    $file = $fs->get_file_by_id($delete);

    if ($file && $file->get_component() === 'local_envasyllabus' && $file->get_filearea() === 'spreadsheet_exports') {
        if ($confirm) {
            // Delete confirmed.
            $filename = $file->get_filename();
            $file->delete();
            redirect(
                $PAGE->url,
                get_string('filedeleted_success', 'local_envasyllabus', $filename),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            // Show confirmation page.
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('confirm_delete_title', 'local_envasyllabus'));

            $filename = $file->get_filename();
            echo $OUTPUT->confirm(
                get_string('confirm_delete_message', 'local_envasyllabus', $filename),
                new moodle_url($PAGE->url, ['delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()]),
                $PAGE->url
            );

            echo $OUTPUT->footer();
            exit;
        }
    } else {
        redirect($PAGE->url, get_string('file_not_found', 'local_envasyllabus'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Get all spreadsheet files.
$fs = get_file_storage();
$files = $fs->get_area_files(
    $context->id,
    'local_envasyllabus',
    'spreadsheet_exports',
    false,
    'timecreated DESC',
    false
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('spreadsheet_files', 'local_envasyllabus'));

if (empty($files)) {
    echo $OUTPUT->notification(get_string('no_files_found', 'local_envasyllabus'));
} else {
    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('table_filename', 'local_envasyllabus'),
        get_string('timecreated', 'moodle'),
        get_string('table_filesize', 'local_envasyllabus'),
        get_string('actions', 'moodle'),
    ];

    foreach ($files as $file) {
        $downloadurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            true
        );

        $deleteurl = new moodle_url($PAGE->url, [
            'delete' => $file->get_id(),
            'sesskey' => sesskey(),
        ]);

        $actions = [];
        $actions[] = html_writer::link($downloadurl, get_string('download', 'local_envasyllabus'));
        $actions[] = html_writer::link(
            $deleteurl,
            get_string('delete', 'local_envasyllabus'),
            ['class' => 'text-danger']
        );

        $table->data[] = [
            $file->get_filename(),
            userdate($file->get_timecreated()),
            display_size($file->get_filesize()),
            implode(' | ', $actions),
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
