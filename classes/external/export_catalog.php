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

namespace local_envasyllabus\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_envasyllabus\output\excel_exporter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * External service for catalog export
 *
 * @package     local_envasyllabus
 * @copyright   2025 Bas Brands
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_catalog extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'lang' => new external_value(PARAM_LANG, 'Language code', VALUE_DEFAULT, 'en'),
            'extended' => new external_value(PARAM_BOOL, 'Extended mode', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute export
     *
     * @param string $lang Language code
     * @param bool $extended Extended mode
     * @return array
     */
    public static function execute($lang = 'en', $extended = false) {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'lang' => $lang,
            'extended' => $extended,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/envasyllabus:exportcatalog', $context);

        // Get configuration.
        $categoryid = get_config('local_envasyllabus', 'rootcategoryid');
        if (!$categoryid) {
            $categoryid = 1; // Default to category 1.
        }

        // Generate unique token for this download.
        $token = md5(uniqid(rand(), true));

        // Create the Excel file.
        $exporter = new excel_exporter($categoryid, $params['extended'], $params['lang']);
        $spreadsheet = $exporter->create_spreadsheet();

        // Save to temp directory with token.
        $tempdir = make_temp_directory('envasyllabus/exports');
        $filename = 'syllabus_export_' . ($params['extended'] ? 'extended_' : 'basic_') .
                    $params['lang'] . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = $tempdir . '/' . $token . '_' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        // Return the download URL with token.
        $downloadurl = new \moodle_url('/local/envasyllabus/download.php', [
            'token' => $token,
            'filename' => $filename,
        ]);

        return [
            'success' => true,
            'downloadurl' => $downloadurl->out(false),
            'filename' => $filename,
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'downloadurl' => new external_value(PARAM_URL, 'Download URL'),
            'filename' => new external_value(PARAM_TEXT, 'Filename'),
        ]);
    }
}
