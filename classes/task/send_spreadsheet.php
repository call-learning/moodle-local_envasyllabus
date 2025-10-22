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

namespace local_envasyllabus\task;

use local_envasyllabus\output\excel_exporter;
use core_user;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Scheduled task for sending syllabus spreadsheet reports
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_spreadsheet extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string Task name shown in admin screens.
     */
    public function get_name() {
        return get_string('task:send_spreadsheet', 'local_envasyllabus');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG;

        // Check if task is enabled.
        if (!get_config('local_envasyllabus', 'spreadsheet_email_enabled')) {
            mtrace('Spreadsheet email task is disabled.');
            return;
        }

        // Get configuration.
        $categoryid = get_config('local_envasyllabus', 'rootcategoryid');
        $recipients = $this->get_recipients();
        $extendedmode = get_config('local_envasyllabus', 'spreadsheet_extended_mode');
        $lang = get_config('local_envasyllabus', 'spreadsheet_lang') ?: 'en';

        // Default to category 1 if not configured.
        if (!$categoryid) {
            $categoryid = 1;
            mtrace('No root category configured, defaulting to category 1.');
        }

        mtrace("Configuration: categoryid={$categoryid}, extended=" . ($extendedmode ? 'yes' : 'no') . ", lang={$lang}");

        if (empty($recipients)) {
            mtrace('No recipients configured for spreadsheet emails.');
            return;
        }

        // Verify category exists.
        global $DB;
        if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
            mtrace("Category {$categoryid} does not exist. Trying category 1.");
            $categoryid = 1;
            if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
                mtrace('No valid category found.');
                return;
            }
        }

        mtrace('Starting spreadsheet generation and email task...');

        try {
            // Set admin user context for CLI operations.
            $adminuser = get_admin();
            if ($adminuser) {
                \core\session\manager::set_user($adminuser);
                mtrace('Set admin user context for CLI operations.');
            }

            // Create exporter and generate spreadsheet.
            $exporter = new excel_exporter($categoryid, $extendedmode, $lang);
            $spreadsheet = $exporter->create_spreadsheet();

            // Create temporary file in Moodle's temp directory (required for email_to_user attachments).
            $tempfile = make_temp_directory('envasyllabus') . '/syllabus_export_' . uniqid() . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempfile);

            // Store file in Moodle files API.
            $storedfile = $this->store_file($tempfile, $lang, $extendedmode);

            // Send emails.
            $this->send_emails($recipients, $tempfile, $storedfile);

            // Clean up temporary file.
            unlink($tempfile);

            mtrace('Spreadsheet email task completed successfully.');
        } catch (\Exception $e) {
            mtrace('Error in spreadsheet email task: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get configured email recipients
     *
     * @return array Array of email addresses
     */
    private function get_recipients(): array {
        $recipients = get_config('local_envasyllabus', 'spreadsheet_recipients');
        if (empty(trim($recipients))) {
            return [];
        }

        // Split by comma and clean up.
        $emails = explode(',', $recipients);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails); // Remove empty values.

        // Validate email addresses.
        $validemails = [];
        foreach ($emails as $email) {
            if (validate_email($email)) {
                $validemails[] = $email;
            } else {
                mtrace("Invalid email address skipped: {$email}");
            }
        }

        return $validemails;
    }

    /**
     * Store the spreadsheet file in Moodle's file system
     *
     * @param string $tempfile Path to temporary file
     * @param string $lang Language used
     * @param bool $extendedmode Whether extended mode was used
     * @return \stored_file The stored file
     */
    private function store_file(string $tempfile, string $lang, bool $extendedmode): \stored_file {
        $fs = get_file_storage();
        $context = \context_system::instance();

        // Generate filename with timestamp.
        $timestamp = date('Y-m-d_H-i-s');
        $mode = $extendedmode ? 'extended' : 'basic';
        $filename = "syllabus_export_{$mode}_{$lang}_{$timestamp}.xlsx";

        // File record.
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'local_envasyllabus',
            'filearea' => 'spreadsheet_exports',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => get_admin()->id,
        ];

        // Delete any existing file with the same name (shouldn't happen with timestamps).
        $existingfile = $fs->get_file(
            $filerecord['contextid'],
            $filerecord['component'],
            $filerecord['filearea'],
            $filerecord['itemid'],
            $filerecord['filepath'],
            $filerecord['filename']
        );
        if ($existingfile) {
            $existingfile->delete();
        }

        // Store the file.
        $storedfile = $fs->create_file_from_pathname($filerecord, $tempfile);
        mtrace("File stored: {$filename}");

        return $storedfile;
    }

    /**
     * Send emails to recipients with spreadsheet attachment
     *
     * @param array $recipients Email addresses
     * @param string $tempfile Path to temporary file
     * @param \stored_file $storedfile The stored file
     */
    private function send_emails(array $recipients, string $tempfile, \stored_file $storedfile) {
        $noreplyuser = core_user::get_noreply_user();
        $subject = get_string('email:spreadsheet:subject', 'local_envasyllabus', date('Y-m-d'));

        // Verify the temporary file exists.
        if (!file_exists($tempfile)) {
            mtrace("Error: Temporary file does not exist: {$tempfile}");
            return;
        }

        mtrace("Sending emails with attachment: {$tempfile} (" . filesize($tempfile) . " bytes)");

        // Email body.
        $context = [
            'date' => userdate(time()),
            'filename' => $storedfile->get_filename(),
            'filesize' => display_size($storedfile->get_filesize()),
        ];
        $body = get_string('email:spreadsheet:body', 'local_envasyllabus', (object) $context);

        foreach ($recipients as $email) {
            // Create a proper user object for recipient.
            $user = new \stdClass();
            $user->email = $email;
            $user->firstname = 'Recipient';
            $user->lastname = '';
            $user->firstnamephonetic = '';
            $user->lastnamephonetic = '';
            $user->middlename = '';
            $user->alternatename = '';
            $user->id = -1;
            $user->username = $email;
            $user->maildisplay = 1;
            $user->mailformat = 1; // HTML format.
            $user->lang = 'en';
            $user->timezone = '99';

            try {
                mtrace("Attempting to send email to: {$email} with attachment: " . $storedfile->get_filename());

                // Send email with attachment.
                $success = email_to_user(
                    $user,
                    $noreplyuser,
                    $subject,
                    $body,
                    '', // HTML body (empty for plain text).
                    $tempfile, // Full path to attachment file.
                    $storedfile->get_filename() // Filename for attachment.
                );

                if ($success) {
                    mtrace("Email sent successfully to: {$email}");
                } else {
                    mtrace("Failed to send email to: {$email}");
                }
            } catch (\Exception $e) {
                mtrace("Exception sending email to {$email}: " . $e->getMessage());
            }
        }
    }
}
