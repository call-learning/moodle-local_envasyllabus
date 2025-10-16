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

        // Check if this is a multilingual field
        $fieldshortname = $field->get('shortname');
        $multilingual = $this->is_multilingual_field($fieldshortname, $handler);

        if ($multilingual) {
            // Add form heading for multilingual
            // Add both language versions
            $this->add_multilingual_fields($mform, $handler, $courseid, $fieldshortname);
        } else {
            // Add form heading for single field
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
        }

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
     * List of multilingual field pairs (base field => english field)
     */
    private function get_multilingual_field_pairs() {
        return [
            'uc_competences' => 'uc_competences_en',
            'uc_prerequis' => 'uc_prerequis_en',
            'uc_programme' => 'uc_programme_en',
            'uc_validation' => 'uc_validation_en',
            'uc_infos_compl' => 'uc_infos_compl_en'
        ];
    }

    /**
     * Check if a field is part of a multilingual pair
     */
    private function is_multilingual_field($fieldshortname, $handler) {
        $pairs = $this->get_multilingual_field_pairs();

        // Check if it's a base field that has an _en counterpart
        if (isset($pairs[$fieldshortname])) {
            // Check if the english version actually exists
            $fields = $handler->get_fields();
            foreach ($fields as $f) {
                if ($f->get('shortname') === $pairs[$fieldshortname]) {
                    return true;
                }
            }
        }

        // Check if it's an _en field that has a base counterpart
        $baseField = array_search($fieldshortname, $pairs);
        if ($baseField !== false) {
            // Check if the base version actually exists
            $fields = $handler->get_fields();
            foreach ($fields as $f) {
                if ($f->get('shortname') === $baseField) {
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

        // Determine which fields to show
        $baseFieldName = null;
        $englishFieldName = null;

        if (isset($pairs[$triggerfieldname])) {
            // Triggered by base field
            $baseFieldName = $triggerfieldname;
            $englishFieldName = $pairs[$triggerfieldname];
        } else {
            // Triggered by _en field, find the base
            $baseFieldName = array_search($triggerfieldname, $pairs);
            $englishFieldName = $triggerfieldname;
        }

        // Get all fields
        $fields = $handler->get_fields();
        $baseField = null;
        $englishField = null;

        foreach ($fields as $f) {
            if ($f->get('shortname') === $baseFieldName) {
                $baseField = $f;
            } elseif ($f->get('shortname') === $englishFieldName) {
                $englishField = $f;
            }
        }

        // Get instance data
        $instancedata = $handler->get_instance_data($courseid, true);

        // Add French version
        if ($baseField) {
            $mform->addElement('static', 'french_label', '',
                '<h4>' . get_string('frenchversion', 'local_envasyllabus', $baseField->get_formatted_name()) . '</h4>');

            $datacontroller = null;
            foreach ($instancedata as $fielddata) {
                if ($fielddata->get_field()->get('id') == $baseField->get('id')) {
                    $datacontroller = $fielddata;
                    break;
                }
            }

            if (!$datacontroller) {
                $datacontroller = \core_customfield\data_controller::create(0, null, $baseField);
            }

            $datacontroller->instance_form_definition($mform);
        }

        // Add English version
        if ($englishField) {
            $mform->addElement('static', 'english_label', '',
                '<h4>' . get_string('englishversion', 'local_envasyllabus', $englishField->get_formatted_name()) . '</h4>');

            $datacontroller_en = null;
            foreach ($instancedata as $fielddata) {
                if ($fielddata->get_field()->get('id') == $englishField->get('id')) {
                    $datacontroller_en = $fielddata;
                    break;
                }
            }

            if (!$datacontroller_en) {
                $datacontroller_en = \core_customfield\data_controller::create(0, null, $englishField);
            }

            $datacontroller_en->instance_form_definition($mform);
        }
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

        // Check if this is a multilingual field
        $fieldshortname = $field->get('shortname');
        $multilingual = $this->is_multilingual_field($fieldshortname, $handler);

        if ($multilingual) {
            // Validate both language versions
            $errors = array_merge($errors, $this->validate_multilingual_fields($data, $files, $handler, $courseid, $fieldshortname));
        } else {
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
        }

        return $errors;
    }

    /**
     * Validate multilingual field pairs
     */
    private function validate_multilingual_fields($data, $files, $handler, $courseid, $triggerfieldname) {
        $errors = [];
        $pairs = $this->get_multilingual_field_pairs();

        // Determine which fields to validate
        $baseFieldName = null;
        $englishFieldName = null;

        if (isset($pairs[$triggerfieldname])) {
            $baseFieldName = $triggerfieldname;
            $englishFieldName = $pairs[$triggerfieldname];
        } else {
            $baseFieldName = array_search($triggerfieldname, $pairs);
            $englishFieldName = $triggerfieldname;
        }

        // Get fields and instance data
        $fields = $handler->get_fields();
        $instancedata = $handler->get_instance_data($courseid, true);

        // Validate both fields
        foreach ($fields as $f) {
            if ($f->get('shortname') === $baseFieldName || $f->get('shortname') === $englishFieldName) {
                $datacontroller = null;
                foreach ($instancedata as $fielddata) {
                    if ($fielddata->get_field()->get('id') == $f->get('id')) {
                        $datacontroller = $fielddata;
                        break;
                    }
                }

                if (!$datacontroller) {
                    $datacontroller = \core_customfield\data_controller::create(0, null, $f);
                }

                $fielderrors = $datacontroller->instance_form_validation($data, $files);
                $errors = array_merge($errors, $fielderrors);
            }
        }

        return $errors;
    }
}
