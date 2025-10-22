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
 * Dynamic form for editing custom fields
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envasyllabus\form;

use context;
use context_course;
use core_form\dynamic_form;
use moodle_exception;
use moodle_url;

/**
 * Dynamic form for editing custom fields with multilingual support
 */
class edit_field_dynamic_form extends dynamic_form {

    /**
     * Get context for dynamic submission
     */
    protected function get_context_for_dynamic_submission(): context {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        return context_course::instance($courseid);
    }

    /**
     * Check access for dynamic submission
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/course:update', $this->get_context_for_dynamic_submission());
    }

    /**
     * Get page URL for dynamic submission
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        return new moodle_url('/local/envasyllabus/syllabuspage.php', ['id' => $courseid]);
    }

    /**
     * Process dynamic submission
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $courseid = $data->courseid;
        $fieldid = $data->fieldid;

        // Get the custom field handler.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');

        // Ensure the course ID is set for the handler.
        $data->id = $courseid;

        // Save the field data (this handles both single and multilingual fields automatically).
        $handler->instance_form_save($data, false);

        // Get field info for success message.
        $fields = $handler->get_fields();
        $field = null;
        foreach ($fields as $f) {
            if ($f->get('id') == $fieldid) {
                $field = $f;
                break;
            }
        }

        $fieldshortname = $field ? $field->get('shortname') : '';
        $multilingual = $this->is_multilingual_field($fieldshortname, $handler);

        if ($multilingual) {
            $message = get_string('fieldupdated', 'local_envasyllabus', get_string('multilanguagefields', 'local_envasyllabus'));
        } else {
            $message = get_string('fieldupdated', 'local_envasyllabus', $field ? $field->get_formatted_name() : '');
        }

        return [
            'result' => true,
            'message' => $message,
        ];
    }

    /**
     * Set data for dynamic submission
     */
    public function set_data_for_dynamic_submission(): void {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $fieldid = $this->optional_param('fieldid', 0, PARAM_INT);

        // Get the custom field handler.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');

        // Prepare form data using the handler's method.
        $currentdata = new \stdClass();
        $currentdata->id = $courseid;
        $currentdata->courseid = $courseid;
        $currentdata->fieldid = $fieldid;

        // Use the handler's method to prepare form data.
        $handler->instance_form_before_set_data($currentdata);

        $this->set_data($currentdata);
    }

    /**
     * Form definition
     */
    protected function definition() {
        $mform = $this->_form;

        // Set vertical layout for modal forms.
        $this->set_display_vertical();

        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $fieldid = $this->optional_param('fieldid', 0, PARAM_INT);

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

        // Check if this is a multilingual field.
        $fieldshortname = $field->get('shortname');
        $multilingual = $this->is_multilingual_field($fieldshortname, $handler);

        if ($multilingual) {
            // Add both language versions.
            $this->add_multilingual_fields($mform, $handler, $courseid, $fieldshortname);
        } else {
            // Add field description if available.
            $description = $field->get('description');
            if (!empty($description)) {
                $mform->addElement('static', 'description', '', format_text($description));
            }

            // Get actual field data for this course instance.
            $instancedata = $handler->get_instance_data($courseid, true);
            $datacontroller = null;

            // Find the data controller for this specific field.
            foreach ($instancedata as $fielddata) {
                if ($fielddata->get_field()->get('id') == $fieldid) {
                    $datacontroller = $fielddata;
                    break;
                }
            }

            // If no data found, create a new one.
            if (!$datacontroller) {
                $datacontroller = \core_customfield\data_controller::create(0, null, $field);
            }

            $datacontroller->instance_form_definition($mform);
        }

        // Hidden fields.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'fieldid', $fieldid);
        $mform->setType('fieldid', PARAM_INT);
    }

    /**
     * List of multilingual field pairs (base field => english field)
     */
    private function get_multilingual_field_pairs() {
        return [
            'uc_competences' => 'uc_competences_en',
            'uc_prerequis' => 'uc_prerequis_en',
            'uc_programme' => 'uc_programme_en',
            'uc_validation' => 'uc_validation_en',
            'uc_infos_compl' => 'uc_infos_compl_en',
        ];
    }

    /**
     * Check if a field is part of a multilingual pair
     */
    private function is_multilingual_field($fieldshortname, $handler) {
        $pairs = $this->get_multilingual_field_pairs();

        // Check if it's a base field that has an _en counterpart.
        if (isset($pairs[$fieldshortname])) {
            // Check if the english version actually exists.
            $fields = $handler->get_fields();
            foreach ($fields as $f) {
                if ($f->get('shortname') === $pairs[$fieldshortname]) {
                    return true;
                }
            }
        }

        // Check if it's an _en field that has a base counterpart.
        $basefield = array_search($fieldshortname, $pairs);
        if ($basefield !== false) {
            // Check if the base version actually exists.
            $fields = $handler->get_fields();
            foreach ($fields as $f) {
                if ($f->get('shortname') === $basefield) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add both language versions of a multilingual field to the form
     */
    private function add_multilingual_fields($mform, $handler, $courseid, $triggerfieldname) {
        $pairs = $this->get_multilingual_field_pairs();

        // Determine which fields to show.
        $basefieldname = null;
        $englishfieldname = null;

        if (isset($pairs[$triggerfieldname])) {
            // Triggered by base field.
            $basefieldname = $triggerfieldname;
            $englishfieldname = $pairs[$triggerfieldname];
        } else {
            // Triggered by _en field, find the base.
            $basefieldname = array_search($triggerfieldname, $pairs);
            $englishfieldname = $triggerfieldname;
        }

        // Get all fields.
        $fields = $handler->get_fields();
        $basefield = null;
        $englishfield = null;

        foreach ($fields as $f) {
            if ($f->get('shortname') === $basefieldname) {
                $basefield = $f;
            } else if ($f->get('shortname') === $englishfieldname) {
                $englishfield = $f;
            }
        }

        // Get instance data.
        $instancedata = $handler->get_instance_data($courseid, true);

        // Add French version.
        if ($basefield) {
            $mform->addElement('static', 'french_label', '',
                '<h4>' . get_string('frenchversion', 'local_envasyllabus', $basefield->get_formatted_name()) . '</h4>');

            $datacontroller = null;
            foreach ($instancedata as $fielddata) {
                if ($fielddata->get_field()->get('id') == $basefield->get('id')) {
                    $datacontroller = $fielddata;
                    break;
                }
            }

            if (!$datacontroller) {
                $datacontroller = \core_customfield\data_controller::create(0, null, $basefield);
            }

            $datacontroller->instance_form_definition($mform);
        }

        // Add English version.
        if ($englishfield) {
            $mform->addElement('static', 'english_label', '',
                '<h4>' . get_string('englishversion', 'local_envasyllabus', $englishfield->get_formatted_name()) . '</h4>');

            $datacontrolleren = null;
            foreach ($instancedata as $fielddata) {
                if ($fielddata->get_field()->get('id') == $englishfield->get('id')) {
                    $datacontrolleren = $fielddata;
                    break;
                }
            }

            if (!$datacontrolleren) {
                $datacontrolleren = \core_customfield\data_controller::create(0, null, $englishfield);
            }

            $datacontrolleren->instance_form_definition($mform);
        }
    }
}
