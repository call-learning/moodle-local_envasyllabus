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

namespace local_envasyllabus\tests;

use core_course\reportbuilder\local\entities\course_category;
use core_course_category;
use stdClass;

/**
 * Class renderer
 *
 * @package local_envasyllabus
 * @copyright   2022 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait test_helper {
    /**
     * Create courses
     *
     * @param array $coursedef Course definition
     * @return stdClass The created course
     */
    public function create_course_from_def(array $coursedef): \stdClass {
        $generator = $this->getDataGenerator();
        $customfields = $coursedef['customfields'];
        $cdef = $coursedef;
        $cdef['customfields'] = [];
        foreach ($customfields as $key => $value) {
            $cdef['customfields'][] = [
                'shortname' => $key,
                'value' => $value,
            ];
        }
        $coursecats = core_course_category::get_all();
        $category = null;
        foreach ($coursecats as $coursecat) {
            if ($coursecat->idnumber == $coursedef['category']) {
                $category = $coursecat;
                break;
            }
        }
        if (is_null($category)) {
            $category = $generator->create_category(['name' => $coursedef['category']]);
        }
        $cdef['category'] = $category->id;
        $course = $generator->create_course($cdef);

        // Check if the programme field already exists or not and then create it.
        $coursehandler = \core_course\customfield\course_handler::create();
        $fields = $coursehandler->get_fields();
        $programmefield = null;
        foreach ($fields as $field) {
            if ($field->get('shortname') == 'programme') {
                $programmefield = $field;
                break;
            }
        }
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('core_customfield');
        if (is_null($programmefield)) {
            // Create the programme custom field and data.
            $cfcat = $cfgenerator->create_category();
            $programmefield = $cfgenerator->create_field(
                [
                    'categoryid' => $cfcat->get('id'),
                    'shortname' => 'programme',
                    'type' => 'sprogramme',
                    'configdata' => ['required' => 1],
                ]
            );
        }
        $pgenerator = $this->getDataGenerator()->get_plugin_generator('customfield_sprogramme');
        $cfdata = $cfgenerator->add_instance_data($programmefield, $course->id, 1);
        if (isset($coursedef['programmedata'])) {
            $pgenerator->create_programme(
                $cfdata->get('id'),
                $coursedef['programmedata']
            );
        }
        set_config('enablenewprogramme', 1, 'local_envasyllabus');
        return $course;
    }
}
