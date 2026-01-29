<?php
// This file is part of Moodle - https://moodle.org/.
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_envasyllabus\output;

use core_course\external\course_summary_exporter;
use core_customfield\data_controller;
use local_competvetsuivi\matrix\matrix;
use local_envasyllabus\local\course_header_data;
use local_envasyllabus\utils;
use local_envasyllabus\visibility;
use moodle_exception;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Course Syllabus renderable implementation
 *
 * @package     local_envasyllabus
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_syllabus implements renderable, templatable {
    /**
     * @var array TEACHER_ROLES_NAME
     */
    const TEACHER_ROLES_NAME = ['editingteacher', 'teacher'];

    /**
     * @var array RESPONSABLE_ROLES_NAME
     */
    const RESPONSABLE_ROLES_NAME = ['responsablecourse'];

    /**
     * @var int $courseid course id
     */
    protected $courseid = 0;

    /**
     * @var string $lang lang display mode
     */
    protected $lang = '';
    /**
     * @var bool $hasnewprogramme has new programme
     */
    private bool $hasnewprogramme;


    /**
     * @var array<data_controller> $cfdata array of data_controller controllers
     */
    private array $cfdata = [];


    /**
     * @var array $customfieldsvalue array of custom field values
     */
    protected array $customfieldsvalue = [];

    /**
     * Constructor
     *
     * @param int $courseid
     * @param string $lang
     */
    public function __construct(int $courseid, string $lang = '') {
        $this->courseid = $courseid;
        $this->lang = $lang;
        // Get custom field info.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $this->cfdata = $handler->get_instance_data($this->courseid, true);
        foreach ($this->cfdata as $cfdatacontroller) {
            $shortname = $cfdatacontroller->get_field()->get('shortname');
            $this->customfieldsvalue[$shortname] = $cfdatacontroller->export_value();
        }
        $this->hasnewprogramme = utils::has_new_programme_data($this->courseid);
    }

    /**
     * Export the course data for template
     *
     * @param renderer_base $output
     * @return array|stdClass|void
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $CFG, $PAGE, $OUTPUT;

        // Initialize the edit field modal JavaScript once.
        $PAGE->requires->js_call_amd('local_envasyllabus/edit_field_modal', 'init');
        $PAGE->requires->js_call_amd('local_envasyllabus/edit_teachers_modal', 'init');

        $currentlang = language_switcher::get_current_langcode();
        $contextdata = new stdClass();
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        $context = \context_course::instance($this->courseid);
        $csexporter = new course_summary_exporter($course, ['context' => $context]);
        $contextdata->coursedata = $csexporter->export($output);

        // Fetch right title.
        $contextdata->coursedata->displayname = $contextdata->coursedata->fullname;
        if (!empty($this->customfieldsvalue['uc_titre_' . $currentlang])) {
            if (!empty($this->customfieldsvalue['uc_titre_' . $currentlang])) {
                $contextdata->coursedata->displayname = html_to_text($this->customfieldsvalue['uc_titre_' . $currentlang]);
            }
        }
        $contextdata->teachers = [];
        $managers = $this->get_teacher_for_course($this->courseid, self::RESPONSABLE_ROLES_NAME);
        $canviewuseridentity = has_capability('moodle/site:viewuseridentity', $context);
        if ($canviewuseridentity) {
            $identityfields = array_flip(explode(',', $CFG->showuseridentity));
        } else {
            $identityfields = [];
        }
        if ($managers) {
            $managernames = [];
            foreach ($managers as $manager) {
                $manageroutput = fullname($manager);

                if (isset($identityfields['email']) && $manager->email) {
                    $email = obfuscate_mailto($manager->email, '');
                    $manageroutput .= " ($email)";
                }
                $managernames[] = $manageroutput;
            }
            $contextdata->managers = join('<span>, </span>', $managernames);
        }
        $headerdata = new syllabus_header($this->courseid);
        $contextdata->headerdata = $headerdata->export_for_template($output);

        $contextdata->teachers = [];
        $teachers = $this->get_teacher_for_course($this->courseid);
        foreach ($teachers as $teacheruser) {
            $teacher = new stdClass();
            $teacher->userpicture = '';
            $teacher->userfullname = fullname($teacheruser);
            $teacher->useremail = '';
            if (isset($identityfields['email']) && !empty($teacheruser->email)) {
                $teacher->useremail = ' ' . obfuscate_mailto($teacheruser->email, '');
            }
            if (user_can_view_profile($teacheruser, $course)) {
                $teacher->userpicture = $OUTPUT->user_picture($teacheruser, ['class' => 'userpicture']);
            }
            $contextdata->teachers[] = $teacher;
        }
        $contextdata->teachereditbutton = $this->get_teacher_edit_button($context, $output);

        $contextdata->summary = $this->get_cf_displayable_info('uc_summary', $output);

        $matrixid = $this->customfieldsvalue['uc_matrix'] ?? get_config('local_envasyllabus', 'defaultmatrixid');

        $graphhtml = '';
        if (!empty($matrixid)) {
            $graphhtml = $this->get_graph_for_course($course->shortname, $matrixid, $output);
        }
        $contextdata->competencies = (object) [
            'graph' => empty($this->customfieldsvalue['uc_nombre']) ? '' : $graphhtml,
            'description' => $this->get_cf_displayable_info('uc_competences', $output),
        ];
        $contextdata->prerequisites = $this->get_cf_displayable_info('uc_prerequis', $output);

        $contextdata->programme = $this->get_cf_displayable_info('uc_programme', $output);

        $contextdata->vaq = $this->get_cf_displayable_info('uc_validation', $output);
        $contextdata->additionalinfos = $this->get_cf_displayable_info('uc_infos_compl', $output);
        return $contextdata;
    }

    /**
     * Get users matching the teacher role.
     *
     * @param int $courseid
     * @param array $rolesname
     * @return array
     */
    protected function get_teacher_for_course(int $courseid, array $rolesname = self::TEACHER_ROLES_NAME): array {
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
     * Get graph for course
     *
     * @param string $uename
     * @param int $matrixid
     * @param \renderer_base $output
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_graph_for_course(string $uename, int $matrixid, \renderer_base $output) {
        $matrix = new matrix($matrixid);
        $matrix->load_data();
        try {
            $ue = $matrix->get_matrix_ue_by_criteria('shortname', $uename);
        } catch (moodle_exception $e) {
            return '';
        }

        $compidparamname = \local_competvetsuivi\renderable\uevscompetency_details::PARAM_COMPID;
        $currentcompid = optional_param($compidparamname, 0, PARAM_INT);
        $currentcomp = null;
        if ($currentcompid) {
            $currentcomp = $matrix->comp[$currentcompid];
        }

        $progressoverview = new \local_competvetsuivi\renderable\uevscompetency_summary(
            $matrix,
            $ue->id,
            $currentcomp
        );
        $text = \html_writer::div($output->render($progressoverview), "uevscompetency-summary");
        return $text;
    }

    /**
     * Get field value
     *
     * @param string $cfname
     * @param \renderer_base $output
     * @return mixed
     */
    protected function get_cf_displayable_info(string $cfname, \renderer_base $output) {
        if (!visibility::is_syllabus_public_field($cfname)) {
            return '';
        }

        // Store original field name for edit button.
        $originalfieldname = $cfname;
        if ($cfname == 'uc_programme' && $this->hasnewprogramme) {
            $cfname = 'programme';
        } else {
            if (!empty($this->lang)) {
                $cfname = "{$cfname}_{$this->lang}";
            } else {
                $cfname = "{$cfname}_fr";
            }
            if (!isset($this->customfieldsvalue[$cfname])) {
                // Fallback to the non-lang specific field.
                $cfname = $originalfieldname;
            }
        }
        $cffieldvalue = $this->customfieldsvalue[$cfname] ?? '';
        // Check if we should add edit button (exclude uc_programme as mentioned).
        $editbutton = $this->get_edit_button_for_field($originalfieldname, $output);
        if (!empty($editbutton)) {
            // Return both content and edit button.
            return (object) [
                'content' => $cffieldvalue,
                'editbutton' => $editbutton,
                'haseditoption' => true,
            ];
        }
        if (html_to_text($cffieldvalue) == '') {
            return '';
        }
        return $cffieldvalue;
    }

    /**
     * Get edit button for a custom field if user has permission
     * @param string $fieldname
     * @param \renderer_base $output
     * @return ?string
     */
    protected function get_edit_button_for_field(string $fieldname, \renderer_base $output): ?string {
        global $PAGE;
        if ($fieldname === 'uc_programme' ||  !$PAGE->user_is_editing()) {
            return null;
        }

        // Check if user can edit course.
        $context = \context_course::instance($this->courseid);
        if (!has_capability('moodle/course:update', $context)) {
            return null;
        }

        // Get the custom field handler and find the field.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $fields = $handler->get_fields();

        $fieldid = null;
        $field = null;
        foreach ($fields as $f) {
            if ($f->get('shortname') === $fieldname) {
                $fieldid = $f->get('id');
                $field = $f;
                break;
            }
        }

        if (!$fieldid) {
            return null;
        }

        // Create modal button with data attributes.
        $editicon = $output->pix_icon('t/edit', get_string('edit'));
        return \html_writer::tag('button', $editicon . get_string('editfield', 'local_envasyllabus'), [
            'class' => 'btn btn-primary',
            'data-action' => 'edit-field',
            'data-courseid' => $this->courseid,
            'data-fieldid' => $fieldid,
            'data-fieldname' => $fieldname,
            'title' => get_string('editfield', 'local_envasyllabus'),
            'type' => 'button',
        ]);
    }

    /**
     * Get teacher edit button
     *
     * @param \context_course $context
     * @param \renderer_base $output
     * @return string
     */
    protected function get_teacher_edit_button(\context_course $context, \renderer_base $output): string {
        global $PAGE;
        // Check if user can edit course.
        if (!has_capability('moodle/course:update', $context)) {
            return '';
        }

        if (!$PAGE->user_is_editing()) {
            return '';
        }

        // Create modal button with data attributes.
        $editicon = $output->pix_icon('t/edit', get_string('edit'));
        return \html_writer::tag('button', $editicon . get_string('editfield', 'local_envasyllabus'), [
            'class' => 'btn btn-primary',
            'data-action' => 'edit-teachers',
            'data-courseid' => $this->courseid,
            'title' => get_string('editfield', 'local_envasyllabus'),
            'type' => 'button',
        ]);
    }
}
