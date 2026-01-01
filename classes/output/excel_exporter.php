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
 * Excel exporter for envasyllabus
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envasyllabus\output;

use local_envasyllabus\external\get_filtered_courses;

/**
 * Class excel_exporter
 *
 * @package local_envasyllabus\output
 */
class excel_exporter {
    /** @var int Category ID */
    private $categoryid;

    /** @var bool Extended mode */
    private $extendedmode;

    /** @var string Language */
    private $lang;

    /** @var array Programme columns */
    private $programmecolumns = [];

    /**
     * Constructor
     *
     * @param int $categoryid
     * @param bool $extendedmode
     * @param string $lang
     */
    public function __construct(int $categoryid, bool $extendedmode = false, string $lang = 'en') {
        $this->categoryid = $categoryid;
        $this->extendedmode = $extendedmode;
        $this->lang = $lang;
    }

    /**
     * Get export data
     *
     * @return array
     */
    public function get_export_data(): array {
        // Use the webservice to get filtered courses.
        $result = get_filtered_courses::execute($this->categoryid, $this->lang, []);

        // Store programme columns for header generation.
        $this->programmecolumns = $result['programmecolumns'] ?? [];

        // Sort courses like JavaScript buildCourseList.
        $sortedcourses = $this->build_course_list($result['courses']);

        return $sortedcourses;
    }

    /**
     * Build course list sorted by year and semester (like JavaScript buildCourseList)
     *
     * @param array $courses
     * @return array
     */
    private function build_course_list(array $courses): array {
        $sortedcourses = [];

        foreach ($courses as $course) {
            $yearvalue = $this->find_value_for_custom_field($course, 'uc_annee');
            $semestervalue = $this->find_value_for_custom_field($course, 'uc_semestre');

            if ($yearvalue) {
                if (!isset($sortedcourses[$yearvalue])) {
                    $sortedcourses[$yearvalue] = [
                        'year' => $yearvalue,
                        'semesters' => [],
                    ];
                }

                if (!isset($sortedcourses[$yearvalue]['semesters'][$semestervalue])) {
                    $sortedcourses[$yearvalue]['semesters'][$semestervalue] = [
                        'semester' => $semestervalue,
                        'year' => $yearvalue,
                        'courses' => [],
                    ];
                }

                $sortedcourses[$yearvalue]['semesters'][$semestervalue]['courses'][] = $course;
            }
        }

        // Sort years.
        ksort($sortedcourses);

        // Sort semesters within each year and flatten structure.
        $result = [];
        foreach ($sortedcourses as $yeardef) {
            ksort($yeardef['semesters']);
            $result[] = [
                'year' => $yeardef['year'],
                'semesters' => array_values($yeardef['semesters']),
            ];
        }

        return $result;
    }

    /**
     * Find value for custom field (like JavaScript findValueForCustomField)
     *
     * @param \stdClass $course
     * @param string $fieldname
     * @param mixed $defaultvalue
     * @return mixed
     */
    private function find_value_for_custom_field(\stdClass $course, string $fieldname, $defaultvalue = null) {
        if (!empty($course->customfields)) {
            foreach ($course->customfields as $field) {
                if ($field['shortname'] === $fieldname) {
                    return $field['value'];
                }
            }
        }
        return $defaultvalue;
    }

    /**
     * Process individual course data
     *
     * @param \stdClass $course
     * @return array
     */
    public function process_course_data(\stdClass $course): array {
        $data = [];

        // Course name.
        $data[] = $course->displayname ?? $course->fullname ?? '';

        // Acronym.
        $data[] = $this->get_custom_field_value($course, 'uc_acronyme');

        // Responsible (managers).
        $managers = [];
        if (!empty($course->managers)) {
            foreach ($course->managers as $manager) {
                $managers[] = $manager['fullname'] ?? '';
            }
        }
        $data[] = implode(', ', $managers);

        // ECTS.
        $data[] = $this->get_custom_field_value($course, 'uc_ects');

        // Extended mode columns (programme values).
        if ($this->extendedmode && !empty($course->programmevalues)) {
            foreach ($this->programmecolumns as $column) {
                $value = '';
                foreach ($course->programmevalues as $progvalue) {
                    if ($progvalue['column'] === $column['column']) {
                        $value = $progvalue['sum'] ?? '';
                        break;
                    }
                }
                $data[] = $value;
            }
        }

        return $data;
    }

    /**
     * Get custom field value
     *
     * @param \stdClass $course
     * @param string $fieldname
     * @return string
     */
    private function get_custom_field_value(\stdClass $course, string $fieldname): string {
        if (!empty($course->customfields)) {
            foreach ($course->customfields as $field) {
                if ($field['shortname'] === $fieldname) {
                    return $field['value'] ?? '';
                }
            }
        }
        return '';
    }

    /**
     * Get export headers
     *
     * @return array
     */
    public function get_headers(): array {
        $headers = [
            get_string('course'),
            get_string('th:acronym', 'local_envasyllabus'),
            get_string('th:responsible', 'local_envasyllabus'),
            get_string('th:ects', 'local_envasyllabus'),
        ];

        // Add extended mode headers.
        if ($this->extendedmode) {
            foreach ($this->programmecolumns as $column) {
                $headers[] = $column['label'] ?? $column['column'] ?? '';
            }
        }

        return $headers;
    }

    /**
     * Create and return a fully formatted Excel spreadsheet
     *
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function create_spreadsheet(): \PhpOffice\PhpSpreadsheet\Spreadsheet {
        // Get sorted data.
        $sorteddata = $this->get_export_data();

        // Create spreadsheet.
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Syllabus Export');

        // Get headers for columns.
        $headers = $this->get_headers();
        $numcols = count($headers);

        // Set main headers.
        $col = 1;
        foreach ($headers as $header) {
            $cellcoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cellcoordinate, $header);
            $col++;
        }

        // Style the main header row.
        $headerrange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numcols) . '1';
        $sheet->getStyle($headerrange)->getFont()->setBold(true);
        $sheet->getStyle($headerrange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E6E6E6');

        // Add sorted data with semester headers.
        $row = 2;
        foreach ($sorteddata as $yeardata) {
            foreach ($yeardata['semesters'] as $semesterdata) {
                // Add semester header row.
                $semestertext = !empty($semesterdata['semester']) ?
                    get_string('course_semester', 'local_envasyllabus', [
                        'semester' => $semesterdata['semester'],
                        'year' => $semesterdata['year'],
                    ]) :
                    get_string('course_no_semester', 'local_envasyllabus', $semesterdata['year']);

                // Set semester in column 1, year in column 2, repeat headers for other columns.
                $sheet->setCellValue('A' . $row, $semestertext);
                $sheet->setCellValue('B' . $row, $semesterdata['year']);

                // Repeat column headers for columns 3 onward.
                for ($col = 3; $col <= $numcols; $col++) {
                    $cellcoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                    $sheet->setCellValue($cellcoordinate, $headers[$col - 1]);
                }

                // Style semester header row.
                $semesterheaderrange = 'A' . $row . ':' .
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numcols) . $row;
                $sheet->getStyle($semesterheaderrange)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($semesterheaderrange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('46185F');

                $row++;
                $semesterstartrow = $row; // Remember start of courses for this semester.

                // Add courses for this semester.
                foreach ($semesterdata['courses'] as $course) {
                    $coursedata = $this->process_course_data($course);
                    $col = 1;
                    foreach ($coursedata as $value) {
                        $cellcoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                        $sheet->setCellValue($cellcoordinate, $value);
                        $col++;
                    }
                    $row++;
                }

                $semesterendrow = $row - 1; // Last row with course data.

                // Add sum row with formulas for ECTS and other numeric columns (starting from column 4).
                $sheet->setCellValue('A' . $row, 'Total');
                $sheet->setCellValue('B' . $row, '');
                $sheet->setCellValue('C' . $row, '');

                // Add sum formulas for columns 4 onward (ECTS and programme columns).
                for ($col = 4; $col <= $numcols; $col++) {
                    $currentcol = $this->programmecolumns[$col - 5] ?? null; // Here -5 because we have the ECTS column at index 4 and
                    // then the programme columns.
                    $isaverage = ($currentcol['totaltype'] ?? '') === 'average';
                    $colletter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    if ($isaverage) {
                        $formula = "=ROUNDUP(IFERROR(AVERAGEIF({$colletter}{$semesterstartrow}:{$colletter}{$semesterendrow},\"<>0\"),0),0)";
                    } else {
                        $formula = "=SUM({$colletter}{$semesterstartrow}:{$colletter}{$semesterendrow})";

                    }
                    $cellcoordinate = $colletter . $row;
                    $sheet->setCellValue($cellcoordinate, $formula);
                }

                // Style sum row.
                $sumrowrange = 'A' . $row . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numcols) . $row;
                $sheet->getStyle($sumrowrange)->getFont()->setBold(true);
                $sheet->getStyle($sumrowrange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5D9F9');

                // Add black border around the entire semester block (header + courses + sum row).
                $blockrange = 'A' . ($semesterstartrow - 1) . ':' .
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numcols) . $row;
                $sheet->getStyle($blockrange)->getBorders()->getOutline()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle($blockrange)->getBorders()->getOutline()->getColor()->setRGB('000000');

                $row++; // Move to next row after sum row.

                // Add empty separator row (borderless).
                $row++;
            }
        }

        // Auto-size columns.
        foreach (range(1, $numcols) as $col) {
            if ($col == 3) {
                // Set fixed width for Responsible column (column 3) and enable text wrapping.
                $sheet->getColumnDimensionByColumn($col)->setWidth(35); // Approximately 250px.
                $columnletter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->getStyle($columnletter . '2:' . $columnletter . $row)
                    ->getAlignment()->setWrapText(true);
            } else {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }
}
