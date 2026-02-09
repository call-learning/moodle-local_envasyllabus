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

/**
 * Plugin strings are defined here.
 *
 * @package     local_envasyllabus
 * @category    string
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['active_help'] = 'Percentage of active teaching methods implemented within the UC';
$string['catalog:extended:percentactive'] = '% Active';
$string['catalog:extended:perso'] = 'Perso.';
$string['catalog:extended:total'] = 'Total';
$string['catalog:filter_sort'] = 'Filters and Sorts';
$string['catalog:index'] = 'Catalog';
$string['cf:uc_annee'] = 'Year';
$string['cf:uc_nombre'] = 'Numéro UC';
$string['cf:uc_semestre'] = 'Semester';
$string['charttitle'] = 'Programme hours per type of teaching and semester';
$string['chartview'] = 'Chart view';
$string['competency:rep:name'] = 'Competency ({$a})';
$string['competency:rep:percent'] = 'Competency percentage ({$a})';
$string['confirm_delete_message'] = 'Are you sure you want to delete the file "{$a}"? This action cannot be undone.';
$string['confirm_delete_title'] = 'Confirm File Deletion';
$string['course_no_semester'] = 'Year {$a}';
$string['course_semester'] = 'Year {$a->year}, {$a->semester}';
$string['coursecard:credits'] = '{$a} ECTS credits';
$string['coursecard:hours'] = '{$a} hours';
$string['courses:index'] = 'Cursus at ENVA';
$string['defaultmatrixid'] = 'Default Matrix ID';
$string['defaultmatrixid_desc'] = 'Default Matrix ID';
$string['delete'] = 'Delete';
$string['discipline:rep:name'] = 'Disciplin ({$a})';
$string['discipline:rep:percent'] = 'Discipline percentage ({$a})';
$string['download'] = 'Download';
$string['edit_teachers'] = 'Edit Teaching Team';
$string['edit_teachers_explanation'] = 'To display instructors in the teaching team, assign them the "Teacher" role in the participants list.

Click below to manage roles.';
$string['editfield'] = 'Edit';
$string['editsyllabusfields'] = 'Edit Syllabus fields';
$string['email:spreadsheet:body'] = 'Please find attached the syllabus report generated on {$a->date}.

Filename: {$a->filename}
File size: {$a->filesize}

This is an automated message.';
$string['email:spreadsheet:subject'] = 'Syllabus Report - {$a}';
$string['enableenvasyllabus'] = 'Activate ENVA Syllabus functionalities';
$string['enableenvasyllabus_help'] = 'Activate ENVA Syllabus functionalities (additional menus in course)';
$string['enablenewprogramme'] = 'Activate new programme';
$string['enablenewprogramme_desc'] = 'Activate new programme for all courses';
$string['enablenewprogrammeforcourse'] = 'Activate new programme for specific courses';
$string['enablenewprogrammeforcourse_desc'] = 'Activate new programme for specific courses.
If no course selected, the old programme field will be used.';
$string['englishversion'] = 'English Version: {$a}';
$string['entity:programme_with_customfields'] = 'Programme with custom fields';
$string['exportexcel'] = 'Export Excel';
$string['exportexcel_help'] = 'Export catalog data to Excel format';
$string['extendedmode'] = 'Extended mode';
$string['fieldnotfound'] = 'Custom field not found';
$string['fieldupdated'] = 'Field "{$a}" has been updated successfully';
$string['file_not_found'] = 'The requested file could not be found.';
$string['filedeleted_success'] = 'File "{$a}" has been deleted successfully.';
$string['frenchversion'] = 'French Version: {$a}';
$string['generalsettings'] = 'Enva Syllabus settings';
$string['gridview'] = 'Grid view';
$string['invalidcourse'] = 'Invalid course';
$string['labelhours'] = 'Hours';
$string['listview'] = 'List view';
$string['manage_participants'] = 'Manage Participants';
$string['manage_spreadsheets'] = 'Manage Spreadsheet Files';
$string['multilanguagefields'] = 'Multilingual Fields';
$string['no_files_found'] = 'No spreadsheet files found';
$string['nosemester'] = 'No semester for {$a}';
$string['perso_help'] = 'Minimum estimated personal work time required to acquire the skills targeted by this UC';
$string['pluginname'] = 'ENVA Syllabus';
$string['publicfields'] = 'Public fields';
$string['publicfields_desc'] = 'Course field visible to guest users';
$string['report:competencies'] = 'Syllabus Competencies Report';
$string['report:disciplines'] = 'Syllabus Disciplines Report';
$string['report:historyrfc'] = 'History RFC report';
$string['report:historyrfctotals'] = 'History RFC totals report';
$string['report:programme'] = 'Syllabus Programme report';
$string['reports'] = 'Reports';
$string['rootcategoryid'] = 'Root Category';
$string['rootcategoryid_desc'] = 'Root Category Description';
$string['semesterlabel'] = '{$a->year} - {$a->semester}';
$string['sort'] = 'Tri';
$string['sort:customfield_uc_annee'] = 'Year';
$string['sort:fullname'] = 'Title';
$string['sortorderasc'] = 'Ascendant';
$string['sortorderdesc'] = 'Descendant';
$string['spreadsheet_email_enabled'] = 'Enable spreadsheet emails';
$string['spreadsheet_email_enabled_desc'] = 'Send weekly spreadsheet reports via email';
$string['spreadsheet_email_settings'] = 'Spreadsheet Email Reports';
$string['spreadsheet_email_settings_desc'] = 'Configure automatic spreadsheet email reports';
$string['spreadsheet_extended_mode'] = 'Extended mode';
$string['spreadsheet_extended_mode_desc'] = 'Include programme columns in spreadsheet';
$string['spreadsheet_files'] = 'Spreadsheet Files';
$string['spreadsheet_lang'] = 'Report language';
$string['spreadsheet_lang_desc'] = 'Language for spreadsheet content';
$string['spreadsheet_recipients'] = 'Email recipients';
$string['spreadsheet_recipients_desc'] = 'Email addresses to send reports to (comma separated)';
$string['summary'] = 'Summary';
$string['syllabus:lang:english'] = 'English';
$string['syllabus:lang:label'] = 'Langage';
$string['syllabus:lang:system'] = 'French';
$string['syllabuspage:additionalinfos'] = 'Additional information';
$string['syllabuspage:competencies'] = 'Competencies';
$string['syllabuspage:manager'] = 'Coordinator';
$string['syllabuspage:menu'] = 'See Syllabus';
$string['syllabuspage:prerequisites'] = 'Prerequisites';
$string['syllabuspage:program'] = 'Program';
$string['syllabuspage:student_ects'] = 'ECTS credits';
$string['syllabuspage:student_grand_total_hours'] = 'Total hours';
$string['syllabuspage:student_total_hours'] = 'Total hours on timetable';
$string['syllabuspage:student_total_hours_he'] = 'Out of timetable hours';
$string['syllabuspage:teachers'] = 'Teaching staff';
$string['syllabuspage:title'] = 'Syllabus page for Competency Unit (CU)';
$string['syllabuspage:uc_departement'] = 'Teaching department';
$string['syllabuspage:uc_heures_cm_etudiant'] = 'Lectures (CM)';
$string['syllabuspage:uc_heures_fmp_etudiant'] = 'External practical training (FMP)';
$string['syllabuspage:uc_heures_he_aas_etudiant'] = 'Supervised Self learning (AAS)';
$string['syllabuspage:uc_heures_he_tpers_etudiant'] = 'Personal work';
$string['syllabuspage:uc_heures_tc_etudiant'] = 'Clinical work (TC)';
$string['syllabuspage:uc_heures_td_etudiant'] = 'Tutorials (TD)';
$string['syllabuspage:uc_heures_tp_etudiant'] = 'Practical work (TP)';
$string['syllabuspage:uc_heures_tpa_etudiant'] = 'Practical work on healthy animals (TPa)';
$string['syllabuspage:vaq'] = 'Validation of prior learning';
$string['syllabusreports'] = 'Syllabus Reports';
$string['table_filename'] = 'File name';
$string['table_filesize'] = 'File size';
$string['task:send_spreadsheet'] = 'Send spreadsheet email reports';
$string['task:warm_course_cache'] = 'Warm course cache';
$string['th:acronym'] = 'Acronym';
$string['th:ects'] = 'ECTS';
$string['th:responsible'] = 'Responsible';
$string['th:uc'] = 'UC';
$string['total_help'] = 'Total time allocated to the UC, including the estimation of personal work';
$string['viewcourse'] = 'View course';
