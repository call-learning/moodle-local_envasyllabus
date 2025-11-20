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
 * Edit a single custom field for a course
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use local_envasyllabus\form\edit_field_form;

// Get parameters.
$courseid = required_param('courseid', PARAM_INT);
$fieldid = required_param('fieldid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_URL);

// Set up the page.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/course:update', $context);

$PAGE->set_url('/local/envasyllabus/editfield.php', [
    'courseid' => $courseid,
    'fieldid' => $fieldid,
    'returnurl' => $returnurl,
]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('editfield', 'local_envasyllabus'));
$PAGE->set_heading($course->fullname);

// Set return URL.
if (empty($returnurl)) {
    $returnurl = new moodle_url('/local/envasyllabus/syllabuspage.php', ['id' => $courseid]);
} else {
    $returnurl = new moodle_url($returnurl);
}

// Get the custom field handler and field.
$handler = \core_customfield\handler::get_handler('core_course', 'course');
$fields = $handler->get_fields();

$field = null;
foreach ($fields as $f) {
    if ($f->get('id') == $fieldid) {
        $field = $f;
        break;
    }
}

if (!$field) {
    throw new moodle_exception('fieldnotfound', 'local_envasyllabus');
}

// Create the form.
$formdata = [
    'courseid' => $courseid,
    'fieldid' => $fieldid,
    'returnurl' => $returnurl->out(false),
    'field' => $field,
    'handler' => $handler,
];

$mform = new edit_field_form(null, $formdata);

// Get current field data and set it properly.
$currentdata = new stdClass();
$currentdata->id = $courseid;  // This is crucial - the handler needs the instance ID.
$currentdata->courseid = $courseid;
$currentdata->fieldid = $fieldid;

// Use the handler's method to prepare form data.
$handler->instance_form_before_set_data($currentdata);

$mform->set_data($currentdata);

// Process form submission.
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    // Ensure the course ID is set for the handler.
    $formdata->id = $courseid;

    // Check if this is a multilingual field.
    $fieldshortname = $field->get('shortname');
    $multilingualpairs = [
        'uc_competences' => 'uc_competences_en',
        'uc_prerequis' => 'uc_prerequis_en',
        'uc_programme' => 'uc_programme_en',
        'uc_validation' => 'uc_validation_en',
        'uc_infos_compl' => 'uc_infos_compl_en',
    ];

    $ismultilingual = isset($multilingualpairs[$fieldshortname]) || in_array($fieldshortname, $multilingualpairs);

    // Save the field data.
    $handler->instance_form_save($formdata, false);

    // Redirect back to syllabus page.
    if ($ismultilingual) {
        $message = get_string('fieldupdated', 'local_envasyllabus', get_string('multilanguagefields', 'local_envasyllabus'));
    } else {
        $message = get_string('fieldupdated', 'local_envasyllabus', $field->get_formatted_name());
    }
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Output the page.
echo $OUTPUT->header();

// Check if this is a multilingual field for the heading.
$fieldshortname = $field->get('shortname');
$multilingualpairs = [
    'uc_competences' => 'uc_competences_en',
    'uc_prerequis' => 'uc_prerequis_en',
    'uc_programme' => 'uc_programme_en',
    'uc_validation' => 'uc_validation_en',
    'uc_infos_compl' => 'uc_infos_compl_en',
];

$ismultilingual = isset($multilingualpairs[$fieldshortname]) || in_array($fieldshortname, $multilingualpairs);

if ($ismultilingual) {
    echo $OUTPUT->heading(get_string('editsyllabusfields', 'local_envasyllabus'));
} else {
    echo $OUTPUT->heading(get_string('editfield', 'local_envasyllabus') . ': ' . $field->get_formatted_name());
}

$mform->display();

echo $OUTPUT->footer();
