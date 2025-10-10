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
 * Form for editing a single custom field
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envasyllabus\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing a single custom field
 */
class edit_field_form extends \moodleform {

    /**
     * Define form elements
     */
    public function definition() {
        $mform = $this->_form;
        $data = $this->_customdata;

        $courseid = $data['courseid'];
        $fieldid = $data['fieldid'];
        $field = $data['field'];
        $handler = $data['handler'];

        // Add form heading
        $mform->addElement('header', 'editfield', get_string('editfield', 'local_envasyllabus'));

        // Add field description if available
        $description = $field->get('description');
        if (!empty($description)) {
            $mform->addElement('static', 'description', '', format_text($description));
        }

        // Get actual field data for this course instance
        $instancedata = $handler->get_instance_data($courseid, true);
        $datacontroller = null;

        // Find the data controller for this specific field
        foreach ($instancedata as $fielddata) {
            if ($fielddata->get_field()->get('id') == $fieldid) {
                $datacontroller = $fielddata;
                break;
            }
        }

        // If no data found, create a new one
        if (!$datacontroller) {
            $datacontroller = \core_customfield\data_controller::create(0, null, $field);
        }

        $datacontroller->instance_form_definition($mform);

        // Hidden fields
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'fieldid', $fieldid);
        $mform->setType('fieldid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl', $data['returnurl']);
        $mform->setType('returnurl', PARAM_URL);

        // Action buttons
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validate form data
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Get field and handler from custom data
        $customdata = $this->_customdata;
        $field = $customdata['field'];
        $handler = $customdata['handler'];
        $courseid = $customdata['courseid'];
        $fieldid = $customdata['fieldid'];

        // Get actual field data for this course instance
        $instancedata = $handler->get_instance_data($courseid, true);
        $datacontroller = null;

        // Find the data controller for this specific field
        foreach ($instancedata as $datainstance) {
            if ($datainstance->get_field()->get('id') == $fieldid) {
                $datacontroller = $datainstance;
                break;
            }
        }

        // If no data found, create a new one
        if (!$datacontroller) {
            $datacontroller = \core_customfield\data_controller::create(0, null, $field);
        }

        // Validate the field data
        $fielderrors = $datacontroller->instance_form_validation($data, $files);
        $errors = array_merge($errors, $fielderrors);

        return $errors;
    }
}
