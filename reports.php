<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Report page for customfield_sprogramme and local_envasyllabus reports
 *
 * @package   local_envasyllabus
 * @copyright 2025 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $PAGE, $DB, $OUTPUT, $USER;

$context = context_system::instance();
require_login(null, false);

$userid = optional_param('userid', $USER->id, PARAM_INT);
$reportname = optional_param('reportname', 'competencies', PARAM_ALPHANUM);
$currenturl = new moodle_url('/local/envasyllabus/reports.php', ['userid' => $userid, 'reportname' => $reportname]);
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$pagetitle = get_string('report:' . $reportname, 'local_envasyllabus');
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('report');
$returnto = optional_param('returnurl', null, PARAM_URL);

if ($returnto) {
    $PAGE->set_button($OUTPUT->single_button(new moodle_url($returnto), get_string('back')));
}
$singlebutton = new single_select(
    new moodle_url('/local/envasyllabus/reports.php', ['userid' => $userid]),
    'reportname',
    [
        'competencies' => get_string('report:competencies', 'customfield_sprogramme'),
        'disciplines' => get_string('report:disciplines', 'customfield_sprogramme'),
        'programme' => get_string('report:programme', 'local_envasyllabus'),
        'historyrfc' => get_string('report:historyrfc', 'local_envasyllabus'),
        'historyrfctotals' => get_string('report:historyrfctotals', 'local_envasyllabus'),
    ],
    $reportname,
    null,
    'selectreport'
);
$singlebutton->set_label(get_string('select') . ': ');
$PAGE->set_button($PAGE->button . $OUTPUT->render($singlebutton));
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
$report = null;
switch ($reportname) {
    case 'competencies':
        $report = \core_reportbuilder\system_report_factory::create(
            \customfield_sprogramme\reportbuilder\local\systemreports\competencies::class,
            $context,
        );
        break;
    case 'disciplines':
        $report = \core_reportbuilder\system_report_factory::create(
            \customfield_sprogramme\reportbuilder\local\systemreports\disciplines::class,
            $context,
        );
        break;
    case 'programme':
        $report = \core_reportbuilder\system_report_factory::create(
            \local_envasyllabus\reportbuilder\local\systemreports\syllabus_programme::class,
            $context,
        );
        break;
    case 'historyrfc':
        $report = \core_reportbuilder\system_report_factory::create(
            \local_envasyllabus\reportbuilder\local\systemreports\syllabus_history_rfc::class,
            $context,
        );
        break;
    case 'historyrfctotals':
        $report = \core_reportbuilder\system_report_factory::create(
            \local_envasyllabus\reportbuilder\local\systemreports\syllabus_history_rfcs_totals::class,
            $context,
        );
        break;
    default:
        break;
}
if (!empty($report)) {
    $report->require_can_view();
    echo $report->output();
}
echo $OUTPUT->footer();
