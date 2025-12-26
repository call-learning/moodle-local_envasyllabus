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
 * Download exported spreadsheet file
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envasyllabus\output\excel_exporter;
use local_envasyllabus\output\language_switcher;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/envasyllabus:exportcatalog', $context);

// Get configuration.
$categoryid = get_config('local_envasyllabus', 'rootcategoryid');
if (!$categoryid) {
    $categoryid = 1; // Default to category 1.
}

// Generate unique token for this download.
$token = md5(uniqid(rand(), true));
language_switcher::set_lang();
try {
    $lang = optional_param('lang', 'en', PARAM_LANG);
    $extended = optional_param('extended', 0, PARAM_BOOL);
    // Create the Excel file.
    $exporter = new excel_exporter($categoryid, $extended, $lang);
    $spreadsheet = $exporter->create_spreadsheet();

    // Save to temp directory with token.
    $tempdir = make_temp_directory('envasyllabus/exports');
    $filename = 'syllabus_export_' . ($extended ? 'extended_' : 'basic_') .
        $lang . '_' . date('Y-m-d_H-i-s') . '.xlsx';
    $filepath = $tempdir . '/' . $token . '_' . $filename;

    $writer = new Xlsx($spreadsheet);
    $writer->save($filepath);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: max-age=0');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    // Output file contents.
    readfile($filepath);

    // Delete the file after sending.
    @unlink($filepath);
} finally {
    language_switcher::reset_lang();
}
