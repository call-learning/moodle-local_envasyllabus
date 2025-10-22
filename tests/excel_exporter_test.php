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

/**
 * Unit tests for excel_exporter
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envasyllabus;

use local_envasyllabus\output\excel_exporter;

/**
 * Test cases for excel_exporter class
 *
 * @package local_envasyllabus
 * @covers \local_envasyllabus\output\excel_exporter
 */
final class excel_exporter_test extends \advanced_testcase {
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test constructor initialization
     */
    public function test_constructor(): void {
        $exporter = new excel_exporter(123, true, 'fr');

        $this->assertInstanceOf(excel_exporter::class, $exporter);

        // Test with default parameters.
        $exporterdefault = new excel_exporter(456);
        $this->assertInstanceOf(excel_exporter::class, $exporterdefault);
    }

    /**
     * Test get_headers method in basic mode
     */
    public function test_get_headers_basic_mode(): void {
        $exporter = new excel_exporter(123, false, 'en');
        $headers = $exporter->get_headers();

        $this->assertCount(4, $headers);
        $this->assertContains('Course', $headers);
    }

    /**
     * Test get_headers method in extended mode
     */
    public function test_get_headers_extended_mode(): void {
        // Create real exporter instead of mock.
        $exporter = new excel_exporter(123, true, 'en');

        // Use reflection to set programmecolumns directly.
        $reflection = new \ReflectionClass($exporter);
        $property = $reflection->getProperty('programmecolumns');
        $property->setAccessible(true);
        $property->setValue($exporter, [
            ['column' => 'prog1', 'name' => 'Programme 1'],
            ['column' => 'prog2', 'name' => 'Programme 2'],
        ]);

        $headers = $exporter->get_headers();

        $this->assertCount(6, $headers); // 4 basic + 2 programme columns
        $this->assertContains('Programme 1', $headers);
        $this->assertContains('Programme 2', $headers);
    }

    /**
     * Test find_value_for_custom_field method
     */
    public function test_find_value_for_custom_field(): void {
        $exporter = new excel_exporter(123);

        // Create test course object.
        $course = new \stdClass();
        $course->customfields = [
            ['shortname' => 'uc_annee', 'value' => '2024'],
            ['shortname' => 'uc_semestre', 'value' => '1'],
            ['shortname' => 'uc_ects', 'value' => '6'],
        ];

        // Use reflection to access private method.
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('find_value_for_custom_field');
        $method->setAccessible(true);

        $result = $method->invokeArgs($exporter, [$course, 'uc_annee']);
        $this->assertEquals('2024', $result);

        $result = $method->invokeArgs($exporter, [$course, 'uc_semestre']);
        $this->assertEquals('1', $result);

        $result = $method->invokeArgs($exporter, [$course, 'nonexistent', 'default']);
        $this->assertEquals('default', $result);

        // Test with empty customfields.
        $course->customfields = null;
        $result = $method->invokeArgs($exporter, [$course, 'uc_annee', 'fallback']);
        $this->assertEquals('fallback', $result);
    }

    /**
     * Test get_custom_field_value method
     */
    public function test_get_custom_field_value(): void {
        $exporter = new excel_exporter(123);

        // Create test course object.
        $course = new \stdClass();
        $course->customfields = [
            ['shortname' => 'uc_acronyme', 'value' => 'TEST101'],
            ['shortname' => 'uc_ects', 'value' => '6'],
        ];

        // Use reflection to access private method.
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('get_custom_field_value');
        $method->setAccessible(true);

        $result = $method->invokeArgs($exporter, [$course, 'uc_acronyme']);
        $this->assertEquals('TEST101', $result);

        $result = $method->invokeArgs($exporter, [$course, 'nonexistent']);
        $this->assertEquals('', $result);

        // Test with empty customfields.
        $course->customfields = [];
        $result = $method->invokeArgs($exporter, [$course, 'uc_acronyme']);
        $this->assertEquals('', $result);
    }

    /**
     * Test process_course_data method
     */
    public function test_process_course_data(): void {
        $exporter = new excel_exporter(123, false);

        // Create test course object.
        $course = new \stdClass();
        $course->displayname = 'Test Course';
        $course->fullname = 'Full Test Course Name';
        $course->customfields = [
            ['shortname' => 'uc_acronyme', 'value' => 'TC101'],
            ['shortname' => 'uc_ects', 'value' => '6'],
        ];
        $course->managers = [
            (object)['fullname' => 'John Doe'],
            (object)['fullname' => 'Jane Smith'],
        ];

        $result = $exporter->process_course_data($course);

        $this->assertCount(4, $result); // Basic mode: 4 columns.
        $this->assertEquals('Test Course', $result[0]);
        $this->assertEquals('TC101', $result[1]);
        $this->assertEquals('John Doe, Jane Smith', $result[2]);
        $this->assertEquals('6', $result[3]);
    }

    /**
     * Test process_course_data method in extended mode
     */
    public function test_process_course_data_extended_mode(): void {
        $exporter = new excel_exporter(123, true);

        // Set programme columns using reflection on real object.
        $reflection = new \ReflectionClass($exporter);
        $property = $reflection->getProperty('programmecolumns');
        $property->setAccessible(true);
        $property->setValue($exporter, [
            ['column' => 'prog1', 'name' => 'Programme 1'],
            ['column' => 'prog2', 'name' => 'Programme 2'],
        ]);

        // Create test course object.
        $course = new \stdClass();
        $course->displayname = 'Test Course';
        $course->customfields = [
            ['shortname' => 'uc_acronyme', 'value' => 'TC101'],
            ['shortname' => 'uc_ects', 'value' => '6'],
        ];
        $course->managers = [];
        $course->programmevalues = [
            ['column' => 'prog1', 'sum' => '12'],
            ['column' => 'prog2', 'sum' => '8'],
        ];

        $result = $exporter->process_course_data($course);

        $this->assertCount(6, $result); // Extended mode: 4 basic + 2 programme columns.
        $this->assertEquals('Test Course', $result[0]);
        $this->assertEquals('TC101', $result[1]);
        $this->assertEquals('', $result[2]); // No managers.
        $this->assertEquals('6', $result[3]);
        $this->assertEquals('12', $result[4]); // Prog1 value.
        $this->assertEquals('8', $result[5]); // Prog2 value.
    }

    /**
     * Test build_course_list method
     */
    public function test_build_course_list(): void {
        $exporter = new excel_exporter(123);

        // Create test courses.
        $courses = [
            $this->create_test_course('Course 1', '2024', '1'),
            $this->create_test_course('Course 2', '2024', '2'),
            $this->create_test_course('Course 3', '2023', '1'),
            $this->create_test_course('Course 4', '2024', '1'),
        ];

        // Use reflection to access private method.
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('build_course_list');
        $method->setAccessible(true);

        $result = $method->invokeArgs($exporter, [$courses]);

        // Should have 2 years (2023, 2024).
        $this->assertCount(2, $result);

        // Check year ordering (should be ascending).
        $this->assertEquals('2023', $result[0]['year']);
        $this->assertEquals('2024', $result[1]['year']);

        // Check 2023 has 1 semester.
        $this->assertCount(1, $result[0]['semesters']);
        $this->assertEquals('1', $result[0]['semesters'][0]['semester']);
        $this->assertCount(1, $result[0]['semesters'][0]['courses']);

        // Check 2024 has 2 semesters.
        $this->assertCount(2, $result[1]['semesters']);
        $this->assertEquals('1', $result[1]['semesters'][0]['semester']);
        $this->assertEquals('2', $result[1]['semesters'][1]['semester']);

        // Check semester 1 of 2024 has 2 courses.
        $this->assertCount(2, $result[1]['semesters'][0]['courses']);
        // Check semester 2 of 2024 has 1 course.
        $this->assertCount(1, $result[1]['semesters'][1]['courses']);
    }

    /**
     * Test basic functionality that doesn't require external dependencies
     */
    public function test_exporter_basic_functionality(): void {
        $exporter = new excel_exporter(123, false, 'en');

        // Test that headers work.
        $headers = $exporter->get_headers();
        $this->assertIsArray($headers);
        $this->assertCount(4, $headers);

        // Test that a simple course can be processed.
        $course = new \stdClass();
        $course->displayname = 'Test Course';
        $course->customfields = [
            ['shortname' => 'uc_acronyme', 'value' => 'TC101'],
            ['shortname' => 'uc_ects', 'value' => '6'],
        ];
        $course->managers = [
            (object)['fullname' => 'Test Manager'],
        ];

        $result = $exporter->process_course_data($course);
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertEquals('Test Course', $result[0]);
        $this->assertEquals('TC101', $result[1]);
        $this->assertEquals('Test Manager', $result[2]);
        $this->assertEquals('6', $result[3]);
    }

    /**
     * Test edge cases and error handling
     */
    public function test_edge_cases(): void {
        $exporter = new excel_exporter(123, false, 'en');

        // Test course with missing customfields.
        $course = new \stdClass();
        $course->displayname = 'Minimal Course';
        $course->customfields = null;
        $course->managers = [];

        $result = $exporter->process_course_data($course);
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertEquals('Minimal Course', $result[0]);
        $this->assertEquals('', $result[1]); // Empty acronyme.
        $this->assertEquals('', $result[2]); // No managers.
        $this->assertEquals('', $result[3]); // No ECTS.

        // Test with empty customfields array.
        $course->customfields = [];
        $result2 = $exporter->process_course_data($course);
        $this->assertEquals($result, $result2); // Should be the same.
    }

    /**
     * Test create_spreadsheet method with testable subclass
     */
    public function test_create_spreadsheet_structure(): void {
        // Create a testable exporter that overrides get_export_data.
        $exporter = new class (123, false, 'en') extends excel_exporter {
            /**
             * Get test export data.
             *
             * @return array
             */
            public function get_export_data(): array {
                // Return test data without calling external webservice.
                return [
                    [
                        'year' => '2024',
                        'semesters' => [
                            [
                                'semester' => '1',
                                'year' => '2024',
                                'courses' => [
                                    (object)[
                                        'displayname' => 'Test Course 1',
                                        'customfields' => [
                                            ['shortname' => 'uc_acronyme', 'value' => 'TC1'],
                                            ['shortname' => 'uc_ects', 'value' => '6'],
                                        ],
                                        'managers' => [(object)['fullname' => 'Test Manager']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };

        // Test the spreadsheet creation.
        $spreadsheet = $exporter->create_spreadsheet();

        // Basic assertions.
        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Spreadsheet::class, $spreadsheet);

        $sheet = $spreadsheet->getActiveSheet();
        $this->assertEquals('Syllabus Export', $sheet->getTitle());

        // Check that main headers are set in row 1.
        $this->assertEquals('Course', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Acronym', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Responsible', $sheet->getCell('C1')->getValue());
        $this->assertEquals('ECTS', $sheet->getCell('D1')->getValue());

        // Check that we have semester header in row 2.
        $this->assertNotEmpty($sheet->getCell('A2')->getValue());

        // Check that we have actual course data in row 3.
        $this->assertEquals('Test Course 1', $sheet->getCell('A3')->getValue());
        $this->assertEquals('TC1', $sheet->getCell('B3')->getValue());
        $this->assertEquals('Test Manager', $sheet->getCell('C3')->getValue());
        $this->assertEquals('6', $sheet->getCell('D3')->getValue());
    }

    /**
     * Helper method to create test course objects
     */
    private function create_test_course($name, $year, $semester) {
        $course = new \stdClass();
        $course->displayname = $name;
        $course->fullname = $name;
        $course->customfields = [
            ['shortname' => 'uc_annee', 'value' => $year],
            ['shortname' => 'uc_semestre', 'value' => $semester],
            ['shortname' => 'uc_acronyme', 'value' => strtoupper(substr($name, 0, 6))],
            ['shortname' => 'uc_ects', 'value' => '6'],
        ];
        $course->managers = [
            (object)['fullname' => 'Test Manager'],
        ];
        $course->programmevalues = [];

        return $course;
    }
}
