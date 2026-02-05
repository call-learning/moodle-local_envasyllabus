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

use Generator;
use local_envasyllabus\local\course_syllabus_helper;
use local_envasyllabus\output\excel_exporter;
use local_envasyllabus\tests\test_helper;

/**
 * Test cases for excel_exporter class
 *
 * @package local_envasyllabus
 * @covers \local_envasyllabus\output\excel_exporter
 */
final class excel_exporter_test extends \advanced_testcase {
    use test_helper;

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
        // Use reflection to access get_headers directly.
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('get_headers');
        $method->setAccessible(true);
        $headers = $method->invoke($exporter);

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
            ['column' => 'prog1', 'label' => 'Programme 1'],
            ['column' => 'prog2', 'label' => 'Programme 2'],
        ]);

        // Use reflection to access get_headers directly.
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('get_headers');
        $method->setAccessible(true);
        $headers = $method->invoke($exporter);

        $this->assertCount(6, $headers); // 4 basic + 2 programme columns
        $this->assertContains('Programme 1', $headers);
        $this->assertContains('Programme 2', $headers);
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
     * Test create_spreadsheet method with testable subclass
     *
     * @param string $fixturename Name of the fixture file
     * @param array $expected Expected cell values in the spreadsheet
     * @dataProvider create_spreadsheet_provider
     */
    public function test_create_spreadsheet(string $fixturename, array $expected): void {
        $category = $this->getDataGenerator()->create_category(['idnumber' => 'CAT1']);
        $json = file_get_contents(self::get_fixture_path('local_envasyllabus', $fixturename));
        $coursedefs = json_decode($json, true);

        $responsible = $this->getDataGenerator()->create_user(['firstname' => 'Responsible', 'lastname' => 'Teacher']);
        $roleid = $this->getDataGenerator()->create_role(
            [
                'shortname' => course_syllabus_helper::RESPONSABLE_ROLE_NAME,
                'archetype' => 'editingteacher',
            ]
        );
        // Courses are sorted in reverse order for export, so create them in reverse too.
        foreach (array_reverse($coursedefs) as $singlecoursedef) {
            $course = $this->create_course_from_def($singlecoursedef);
            $this->getDataGenerator()->enrol_user($responsible->id, $course->id, $roleid);
        }
        $exporter = new excel_exporter($category->id, true);
        // Test the spreadsheet creation.
        $this->setAdminUser();
        $spreadsheet = $exporter->create_spreadsheet();

        // Basic assertions.
        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Spreadsheet::class, $spreadsheet);

        $this->assert_spreadsheet_equals(
            $spreadsheet,
            $expected
        );
    }

    /**
     * Data provider for test_create_spreadsheet
     */
    public static function create_spreadsheet_provider(): Generator {
        yield 'Header test' => [
            'fixturename' => 'course-list-sample-simple.json',
            'expected' => [
                'A1' => 'Course',
                'B1' => 'Acronym',
                'C1' => 'Responsible',
                'D1' => 'ECTS',
                'A2' => 'Year A1, S1',
                'B2' => 'A1',
                'C2' => 'Responsible',
                'C3' => 'Responsible Teacher',
            ],
        ];
        yield 'Total test' => [
            'fixturename' => 'course-list-sample-addition.json',
            'expected' => [
                'A1' => 'Course',
                'B1' => 'Acronym',
                'C1' => 'Responsible',
                'D1' => 'ECTS',
                'A3' => 'UC0101 - Fundamentals of Veterinary Medicine',
                'B3' => 'BVM',
                'C3' => 'Responsible Teacher',
                'D3' => '6.0',
                'A4' => 'UC0102 - Introduction to Animal Biology',
                'B4' => 'IAB',
                'C4' => 'Responsible Teacher',
                'D4' => '5.0',
                'A5' => 'Total',
                'D5' => '11.0', // ECTS.
                'E5' => '21.0', // CM.
                'F5' => '8.0', // TD.
                'G5' => '3.0', // TP.
                'H5' => '3.0', // TPa.
                'I5' => '4', // TC.
                'J5' => '11.0', // AAS.
                'K5' => '10.0', // FPM.
                'L5' => '25', // Perso.
                'M5' => '65.0', // Active.
                'N5' => '85.0', // Total hours.
            ],
        ];
    }
    /**
     * Assert that the given spreadsheet has expected cell values
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet The spreadsheet to check
     * @param array $expectedcellsvalues Array of expected cell values (cell => expected value)
     */
    protected function assert_spreadsheet_equals(
        \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet,
        array $expectedcellsvalues
    ): void {
        $currentsheet = $spreadsheet->getActiveSheet();
        foreach ($expectedcellsvalues as $cell => $expectedvalue) {
            $actualvalue = $currentsheet->getCell($cell)->getValue();
            $this->assertEquals($expectedvalue, $actualvalue, "Value mismatch at cell $cell");
        }
    }
}
