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

require_once(__DIR__ . '/../../config.php');

require_login();

try {
    $token = required_param('token', PARAM_ALPHANUM);
    $filename = required_param('filename', PARAM_FILE);

    $context = context_system::instance();
    require_capability('local/envasyllabus:exportcatalog', $context);
} catch (Exception $e) {
    debugging('Error in download.php params: ' . $e->getMessage(), DEBUG_DEVELOPER);
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}

// Validate token and get file.
// Use make_temp_directory to ensure we get the same path as the external service.
$tempdir = make_temp_directory('envasyllabus/exports');
$filepath = $tempdir . '/' . $token . '_' . $filename;

// Debug logging.
debugging('Looking for file: ' . $filepath, DEBUG_DEVELOPER);
debugging('File exists: ' . (file_exists($filepath) ? 'yes' : 'no'), DEBUG_DEVELOPER);

if (!file_exists($filepath)) {
    // Try to list files in the directory for debugging.
    if (is_dir($tempdir)) {
        $files = scandir($tempdir);
        debugging('Files in directory: ' . implode(', ', $files), DEBUG_DEVELOPER);
    }
    http_response_code(404);
    die('File not found: ' . $filepath);
}

// Send the file with appropriate headers.
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
exit;
