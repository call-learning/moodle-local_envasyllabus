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
use local_competvetsuivi\matrix\matrix;
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
     * Cfield definition
     */
    const CF_HEADER_DEFINITION = [
        ['type' => 'cf', 'fieldname' => 'uc_departement', 'class' => 'highlighted-top'],
        ['type' => 'categorysum', 'languagestring' => 'syllabuspage:student_grand_total_hours', 'class' => 'highlighted-top',
            'positionafter' => true,
            'fields' => [
                ['type' => 'categorysum', 'languagestring' => 'syllabuspage:student_total_hours', 'class' => 'highlighted-top',
                    'fields' => [
                        ['type' => 'cf', 'fieldname' => 'uc_heures_cm_etudiant', 'programmenames' => 'cm'],
                        ['type' => 'cf', 'fieldname' => 'uc_heures_td_etudiant', 'programmenames' => 'td'],
                        ['type' => 'cf', 'fieldname' => 'uc_heures_tp_etudiant', 'programmenames' => 'tp'],
                        ['type' => 'cf', 'fieldname' => 'uc_heures_tpa_etudiant', 'programmenames' => 'tpa'],
                        ['type' => 'cf', 'fieldname' => 'uc_heures_tc_etudiant', 'programmenames' => 'tc'],
                        ['type' => 'cf', 'fieldname' => 'uc_heures_fmp_etudiant', 'programmenames' => 'fmp'],
                    ],
                ],
                ['type' => 'categorysum', 'languagestring' => 'syllabuspage:student_total_hours_he', 'class' => 'highlighted-top',
                    'fields' => [
                        ['type' => 'cf', 'fieldname' => 'uc_heures_he_aas_etudiant', 'programmenames' => 'aas'],
                        ['type' => 'cf', 'fieldname' => 'uc_heures_he_tpers_etudiant', 'programmenames' => 'perso_av, perso_ap'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'cf',
            'fieldname' => 'uc_ects',
            'languagestring' => 'syllabuspage:student_ects',
            'icon' => 'ects',
            'class' => 'highlighted-top',
        ],
    ];

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
     * @var array $programmetotals programme total
     */
    private array $programmetotals = [];

    /**
     * Constructor
     *
     * @param int $courseid
     * @param string $lang
     */
    public function __construct(int $courseid, string $lang = '') {
        $this->courseid = $courseid;
        $this->lang = $lang;
    }

    /**
     * Export the course data for template
     *
     * @param renderer_base $output
     * @return array|stdClass|void
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $CFG, $PAGE;

        // Initialize the edit field modal JavaScript once.
        $PAGE->requires->js_call_amd('local_envasyllabus/edit_field_modal', 'init');
        $PAGE->requires->js_call_amd('local_envasyllabus/edit_teachers_modal', 'init');

        $currentlang = current_language();
        $contextdata = new stdClass();
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        $context = \context_course::instance($this->courseid);
        $csexporter = new course_summary_exporter($course, ['context' => $context]);
        $contextdata->coursedata = $csexporter->export($output);

        // Get custom field info.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $cfdata = $handler->get_instance_data($this->courseid, true);
        foreach ($cfdata as $cfdatacontroller) {
            $shortname = $cfdatacontroller->get_field()->get('shortname');
            $customfields[$shortname] = $cfdatacontroller->export_value();
        }

        // Check for the first sprogramme field and set the total for further use.
        $this->set_programme_and_totals($cfdata);

        // Fetch right title.
        $contextdata->coursedata->displayname = $contextdata->coursedata->fullname;
        if (!empty($customfields['uc_titre_' . $currentlang])) {
            if (!empty($customfields['uc_titre_' . $currentlang])) {
                $contextdata->coursedata->displayname = html_to_text($customfields['uc_titre_' . $currentlang]);
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
        $contextdata->headerdata = $this->get_header_data(self::CF_HEADER_DEFINITION, $customfields);

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
                $teacher->userpicture = $output->user_picture($teacheruser, ['class' => 'userpicture']);
            }
            $contextdata->teachers[] = $teacher;
        }
        $contextdata->teachereditbutton = $this->get_teacher_edit_button($context, $output);

        $contextdata->summary = $this->get_cf_displayable_info('uc_summary', $customfields, $output);

        $matrixid = $customfields['uc_matrix'] ?? get_config('local_envasyllabus', 'defaultmatrixid');

        $graphhtml = '';
        if (!empty($matrixid)) {
            $graphhtml = $this->get_graph_for_course($course->shortname, $matrixid, $output);
        }
        $contextdata->competencies = (object) [
            'graph' => empty($customfields['uc_nombre']) ? '' : $graphhtml,
            'description' => $this->get_cf_displayable_info('uc_competences', $customfields, $output),
        ];
        $contextdata->prerequisites = $this->get_cf_displayable_info('uc_prerequis', $customfields, $output);

        $contextdata->programme = $this->get_cf_displayable_info('uc_programme', $customfields, $output);

        $contextdata->vaq = $this->get_cf_displayable_info('uc_validation', $customfields, $output);
        $contextdata->additionalinfos = $this->get_cf_displayable_info('uc_infos_compl', $customfields, $output);
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
     * Get header data for course summary
     *
     * @param array $fieldinfolist
     * @param array $customfields
     * @return array
     * @throws \coding_exception
     */
    protected function get_header_data(array $fieldinfolist, array $customfields): array {
        $headerdata = [];
        foreach ($fieldinfolist as $fieldinfo) {
            if (!empty($fieldinfo['type'])) {
                if (empty($fieldinfo['languagestring'])) {
                    $fielddesc = get_string('syllabuspage:' . $fieldinfo['fieldname'], 'local_envasyllabus');
                } else {
                    $fielddesc = get_string($fieldinfo['languagestring'], 'local_envasyllabus');
                }
                $headerinfo = $this->create_header_data(
                    $fieldinfo['class'] ?? '',
                    $fielddesc,
                    $fieldinfo['icon'] ?? ''
                );
                switch ($fieldinfo['type']) {
                    case 'cf':
                        $sum = $this->get_programme_sum($fieldinfo, $customfields);
                        $headerinfo->value = $sum > 0 ? (string)$sum : '-';
                        ;
                        $headerdata[] = $headerinfo;
                        break;
                    case 'categorysum':
                        $subheaders = $this->get_header_data($fieldinfo['fields'], $customfields);
                        $headerinfo->value = $this->get_header_sum($fieldinfo['fields'], $customfields);
                        if (!empty($fieldinfo['positionafter'])) {
                            array_push($headerdata, ...$subheaders);
                            $headerdata[] = $headerinfo;
                        } else {
                            $headerdata[] = $headerinfo;
                            array_push($headerdata, ...$subheaders);
                        }
                }
            }
        }
        return $headerdata;
    }

    /**
     * Get programme sum
     *
     * @param array $fieldinfo
     * @param array $customfields
     * @return float
     */
    protected function get_programme_sum(array $fieldinfo, array $customfields): float {
        $fieldname = $fieldinfo['fieldname'];
        $programmenames = $fieldinfo['programmenames'] ?? '';
        if (empty($fieldname)) {
            return 0;
        }
        if (empty($programmenames) || !$this->hasnewprogramme) {
            return intval($customfields[$fieldname]) ?? 0;
        }
        $programmmenames = explode(',', $programmenames);
        $programmmenames = array_map('trim', $programmmenames);
        $sum = 0;
        $totalswithkeys = array_column($this->programmetotals, 'sum', 'column');
        foreach ($programmmenames as $programmename) {
            if (isset($totalswithkeys[$programmename])) {
                $sum += $totalswithkeys[$programmename];
            }
        }
        return $sum;
    }


    /**
     * Set programme and totals
     *
     * @param array $cfdata
     * @return void
     */
    protected function set_programme_and_totals(array $cfdata): void {
        $sprogrammefield = utils::get_programme_customfield($cfdata);
        $this->hasnewprogramme = $sprogrammefield
            && $sprogrammefield->get_value() // Ensure the programme is enabled on this course.
            && utils::is_new_programme_enabled($this->courseid);
        if ($this->hasnewprogramme) {
            $this->programmetotals = $sprogrammefield->get_column_totals();
        }
    }

    /**
     * Create a header data object
     *
     * @param string $class
     * @param string $title
     * @param string $icon
     * @return stdClass
     */
    protected function create_header_data(string $class, string $title, string $icon): stdClass {
        $headerinfo = new stdClass();
        $headerinfo->class = $class;
        $headerinfo->title = $title;
        $headerinfo->value = '';
        $headerinfo->icon = $icon;
        return $headerinfo;
    }

    /**
     * Get header data for course summary
     *
     * @param array $fieldinfolist
     * @param array $customfields
     * @return float
     * @throws \coding_exception
     */
    protected function get_header_sum(array $fieldinfolist, array $customfields): float {
        $total = 0;
        foreach ($fieldinfolist as $fieldinfo) {
            if (!empty($fieldinfo['type'])) {
                switch ($fieldinfo['type']) {
                    case 'cf':
                        $total += $this->get_programme_sum($fieldinfo, $customfields);
                        break;
                    case 'categorysum':
                        $total += $this->get_header_sum($fieldinfo['fields'], $customfields);
                        break;
                }
            }
        }
        return $total;
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
     * @param array $customfields
     * @param \renderer_base $output
     * @return mixed
     */
    protected function get_cf_displayable_info(string $cfname, array $customfields, \renderer_base $output) {
        global $PAGE;
        if (!visibility::is_syllabus_public_field($cfname)) {
            return '';
        }

        // Store original field name for edit button.
        $originalfieldname = $cfname;

        if ($cfname == 'uc_summary_en') {
            // Summary field is handled differently.
            $cfname = 'uc_summary';
            $originalfieldname = 'uc_summary_fr';
        }
        if ($cfname == 'uc_summary') {
            // Summary field is handled differently.
            $cfname = 'uc_summary_fr';
            $originalfieldname = 'uc_summary_fr';
        }

        if (!empty($this->lang)) {
            $cfname = "{$cfname}_{$this->lang}";
        }
        if ($cfname == 'uc_programme' && $this->hasnewprogramme) {
            $cfname = 'programme';
        }

        $cffieldvalue = $customfields[$cfname] ?? '';
        if (html_to_text($cffieldvalue) == '') {
            return '';
        }

        // Check if we should add edit button (exclude uc_programme as mentioned).
        if ($originalfieldname !== 'uc_programme') {
            $editbutton = $this->get_edit_button_for_field($originalfieldname, $output);
            if (!empty($editbutton) && $PAGE->user_is_editing()) {
                // Return both content and edit button.
                return (object) [
                    'content' => $cffieldvalue,
                    'editbutton' => $editbutton,
                    'haseditoption' => true,
                ];
            }
        }

        return $cffieldvalue;
    }

    /**
     * Get edit button for a custom field if user has permission
     * @param string $fieldname
     * @param \renderer_base $output
     * @return string
     */
    protected function get_edit_button_for_field(string $fieldname, \renderer_base $output): string {
        global $PAGE;

        // Check if user can edit course.
        $context = \context_course::instance($this->courseid);
        if (!has_capability('moodle/course:update', $context)) {
            return '';
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
            return '';
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
