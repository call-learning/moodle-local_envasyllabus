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

use local_envasyllabus\external\get_filtered_courses;

/**
 * Scheduled task for warming the course cache
 *
 * This task pre-loads course data into the cache to improve performance
 * of the catalog view for end users.
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class warm_course_cache extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string Task name shown in admin screens.
     */
    public function get_name() {
        return get_string('task:warm_course_cache', 'local_envasyllabus');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Set up admin user context for the task.
        \core\cron::setup_user();

        // Get the root category ID from config.
        $categoryid = get_config('local_envasyllabus', 'rootcategoryid');

        if (!$categoryid) {
            $categoryid = 1;
            mtrace('No root category configured, defaulting to category 1.');
        }

        // Verify category exists.
        if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
            mtrace("Category {$categoryid} does not exist. Trying category 1.");
            $categoryid = 1;
            if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
                mtrace('No valid category found. Exiting.');
                return;
            }
        }

        mtrace("Warming course cache for category {$categoryid}...");

        try {
            // Call the webservice execute method to load all courses.
            // This will populate the cache with all course data.
            $result = get_filtered_courses::execute($categoryid);

            $coursecount = count($result['courses']);
            mtrace("Successfully warmed cache for {$coursecount} courses.");

            if (!empty($result['programmeheader'])) {
                $columncount = count($result['programmeheader']['programmecolumns']);
                mtrace("Programme header contains {$columncount} columns.");
            }
        } catch (\Exception $e) {
            mtrace('Error warming course cache: ' . $e->getMessage());
            mtrace('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
