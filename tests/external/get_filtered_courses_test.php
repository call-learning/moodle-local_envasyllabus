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

defined('MOODLE_INTERNAL') || die();

global $CFG;

use core_course\customfield\course_handler;
use core_external\external_api;
use local_envasyllabus\tests\test_helper;
use local_envasyllabus\utils;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the get_filtered_courses class.
 *
 * @package     local_envasyllabus
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_envasyllabus\external\get_filtered_courses
 */
final class get_filtered_courses_test extends \externallib_advanced_testcase {
    use test_helper;

    /**
     * Max categories
     */
    const MAX_CAT = 5;
    /**
     * @var array $categories all categories
     */
    protected $categories = [];
    /**
     * @var array $courses course lst
     */
    protected $courses = [];
    /**
     * Create courses and categories
     *
     * @return void
     */
    public function create_courses_and_categories(): void {
        for ($catindex = 1; $catindex < self::MAX_CAT; $catindex++) {
            $catdef = ['idnumber' => 'CAT' . $catindex];
            if ($catindex > 1 && ($catindex % 2)) {
                $catdef['parent'] = $this->categories['CAT1']->id;
            }
            $category = $this->getDataGenerator()->create_category($catdef);
            $this->categories['CAT' . $catindex] = $category;
        }
        $this->courses = [];
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', 'course-list.json'));
        $coursesdef = json_decode($json, true);
        foreach ($coursesdef as $cdef) {
            $course = $this->create_course_from_def($cdef);
            $this->courses[] = $course;
        }
    }

    /**
     * Test execute API CALL with no instance
     */
    public function test_execute_no_courses(): void {
        $this->resetAfterTest();
        $this->expectException('require_login_exception');
        $courses = $this->get_filtered_courses(0);
        $this->assertCount(0, $courses);
    }

    /**
     * Helper
     *
     * @param mixed ...$params
     * @return mixed
     */
    protected function get_filtered_courses(...$params) {
        $courses = get_filtered_courses::execute(...$params);
        return external_api::clean_returnvalue(get_filtered_courses::execute_returns(), $courses);
    }

    /**
     * Test execute API CALL when login as admin and a simple user
     */
    public function test_get_all_courses(): void {
        $this->resetAfterTest();
        $this->create_courses_and_categories();
        $this->setAdminUser();
        $filter = $this->get_filtered_courses($this->categories['CAT1']->id);
        $this->assertCount(9, $filter['courses']);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $filter = $this->get_filtered_courses($this->categories['CAT1']->id);
        $this->assertCount(8, $filter['courses']);
    }

    /**
     * Test execute API CALL to get filtered courses by year
     * @param array $filters
     * @param array $expected
     * @dataProvider filter_dataprovider
     */
    public function test_get_filtered_courses($filters, $expected): void {
        $this->resetAfterTest();
        $this->create_courses_and_categories();
        foreach ($expected as $usertype => $expectedcount) {
            switch ($usertype) {
                case 'admin':
                    $this->setAdminUser();
                    break;
                case 'guest':
                    $this->setGuestUser();
                    break;
                default:
                    $user = $this->getDataGenerator()->create_user();
                    $this->setUser($user);
                    break;
            }
            $filter = $this->get_filtered_courses($this->categories['CAT1']->id, 'fr', $filters);
            $this->assertCount($expectedcount, $filter['courses']);
        }
    }

    /**
     * Test execute API CALL to get filtered course and make sure that cache is rendering ok for all user
     *
     * @param array $filters
     * @param array $expected
     * @dataProvider filter_dataprovider
     */
    public function test_get_filtered_courses_for_user($filters, $expected): void {
        $this->resetAfterTest();
        $this->create_courses_and_categories();
        $this->setAdminUser();
        // Search courses as admin first. This is to check if there are no side effect to the cache.
        $this->get_filtered_courses($this->categories['CAT1']->id, 'fr', $filters);
        foreach ($expected as $usertype => $expectedcount) {
            switch ($usertype) {
                case 'admin':
                    $this->setAdminUser();
                    break;
                case 'guest':
                    $this->setGuestUser();
                    break;
                default:
                    $user = $this->getDataGenerator()->create_user();
                    $this->setUser($user);
                    break;
            }
            $filter = $this->get_filtered_courses($this->categories['CAT1']->id, 'fr', $filters);
            $this->assertCount($expectedcount, $filter['courses']);
        }
    }

    /**
     * Test execute API CALL to get filtered courses sorted by year
     *
     * @param string $field
     * @param string $order
     * @param array $expected
     * @dataProvider sort_dataprovider
     */
    public function test_get_filtered_courses_sort(string $field, string $order, array $expected): void {
        $this->resetAfterTest();
        $this->create_courses_and_categories();
        $this->setAdminUser();
        $filter = $this->get_filtered_courses(
            $this->categories['CAT1']->id,
            'fr',
            [],
            ['field' => "customfield_" . $field, 'order' => $order]
        );
        $courses = $filter['courses'];
        $this->assertCount(9, $courses);
        $courseyears = array_map(function ($course) use ($field) {
            foreach ($course['customfields'] as $customfield) {
                if ($customfield['shortname'] == $field) {
                    return $customfield['value'];
                }
            }
            return '';
        }, $courses);
        $this->assertEquals($expected, $courseyears);
    }

    /**
     * Filter data provider
     *
     * @return array[]
     */
    public static function sort_dataprovider(): array {
        return [
            'sort by year, descending' => [
                'field' => 'uc_annee',
                'order' => 'desc',
                'expected' =>
                    ['A2', 'A2', 'A1', 'A1', 'A1', 'A1', 'A1', 'A1', 'A1'],
            ],
            'sort by year, ascending' => [
                'field' => 'uc_annee',
                'order' => 'asc',
                'expected' =>
                    ['A1', 'A1', 'A1', 'A1', 'A1', 'A1', 'A1', 'A2', 'A2'],
            ],
        ];
    }

    /**
     * Filter data provider
     *
     * @return array[]
     */
    public static function filter_dataprovider(): array {
        return [
            'filter by year A1' => [
                'filters' => [
                    [
                        'type' => 'customfield',
                        'search' =>
                            [
                                'field' => 'uc_annee',
                                'value' => 'A1',
                            ],
                    ],
                ],
                'expected' => [
                    'admin' => 7,
                    'user' => 6,
                    'guest' => 6,
                ],
            ],
            'filter by year A2' => [
                'filters' => [
                    [
                        'type' => 'customfield',
                        'search' =>
                            [
                                'field' => 'uc_annee',
                                'value' => 'A2',
                            ],
                    ],
                ],
                'expected' => [
                    'admin' => 2,
                    'user' => 2,
                    'guest' => 2,
                ],
            ],
        ];
    }

    /**
     * Test the programme sum calculation
     */
    public function test_programme_sum(): void {
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category();
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', 'sample-course.json'));
        $coursedef = json_decode($json, true);
        $coursedef['category'] = $category->id;
        $course = $this->create_course_from_def($coursedef);
        $coursecfs = course_handler::create()->get_instance_data($course->id, true);
        $sprogrammefield = utils::get_programme_customfield($coursecfs);
        $programmesums = [];
        if ($sprogrammefield && $sprogrammefield->get('id')) {
            $programmesums = $sprogrammefield->get_sum();
        }
        $refgetfilter = new \ReflectionClass(get_filtered_courses::class);
        $refmethod = $refgetfilter->getMethod('process_programme_values');
        $refmethod->setAccessible(true);
        $programmesumsforcourse = $refmethod->invoke(null, $programmesums);
        // On ne garde que les colonnes et les sommes pour la comparaison.
        $expected = [
            'cm' => 18,
            'td' => 26,
            'tp' => 2,
            'tpa' => 20,
            'tc' => 0,
            'aas' => 11,
            'fmp' => 0,
            'perso' => 95.5,
            'active' => 76,
            'total' => 172.5,
        ];
        $this->assertEquals(
            $expected,
            array_column(
                $programmesumsforcourse,
                'value',
                'column'
            )
        );
    }
}
