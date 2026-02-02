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
 * Catalogue chart data builder for Enva Syllabus.
 *
 * @copyright  2022 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Build chart data structure for Chart.js stacked bar chart
 *
 * @param {Array} sortedCourses - Courses sorted by year and semester
 * @param {Array} programmecolumns - Programme column definitions
 * @returns {Object} Chart data combining all years
 */
export const buildChartData = (sortedCourses, programmecolumns) => {
    // Filter out 'active' and 'total' columns for chart display.
    const chartColumns = programmecolumns.filter(
        (col) => col.column !== 'active' && col.column !== 'total'
    );

    // Prepare labels (semester names across all years).
    const labels = [];
    const datasets = {};

    // Initialize datasets for each programme column.
    chartColumns.forEach((col) => {
        datasets[col.column] = {
            label: col.label,
            data: [],
            backgroundColor: getColorForColumn(col.column),
            stack: 'Stack 0'
        };
    });

    // Process all years and semesters.
    sortedCourses.forEach((yearData) => {
        yearData.semesters.forEach((semester) => {
            const semesterLabel = semester.semester ?
                `${yearData.year} - S${semester.semester}` :
                `${yearData.year} - No Semester`;
            labels.push(semesterLabel);

            // Add data for each programme column from totals.
            chartColumns.forEach((col) => {
                if (datasets[col.column]) {
                    let value = 0;
                    if (semester.totals && semester.totals.programmevalues) {
                        const pv = semester.totals.programmevalues.find(pv => pv.column === col.column);
                        if (pv) {
                            value = parseFloat(pv.sum) || 0;
                        }
                    }
                    datasets[col.column].data.push(value);
                }
            });
        });
    });

    // Convert datasets object to array.
    const datasetsArray = Object.values(datasets);

    return {
        type: 'bar',
        series: datasetsArray.map((ds) => ({
            label: ds.label,
            values: ds.data,
            colors: [ds.backgroundColor],
            axes: {
                x: null,
                y: null
            }
        })),
        labels: labels,
        title: 'Programme Hours per Semester',
        axes: {
            x: [],
            y: [{
                label: 'Hours',
                min: 0,
                position: 'left'
            }]
        },
        stacked: true
    };
};

/**
 * Get color for a programme column
 *
 * @param {string} column - Column identifier
 * @returns {string} Color in hex format
 */
const getColorForColumn = (column) => {
    const colors = {
        'cm': '#99BCE3',
        'td': '#D98C8C',
        'tp': '#C5A3D9',
        'tpa': '#8FD3E0',
        'tc': '#F7CDA0',
        'aas': '#C8E08C',
        'fmp': '#A67C6C',
        'perso_av': '#A7B77A',
        'perso_ap': '#A7B77A',
        'perso': '#A7B77A',
        'active': '#6C7FA1',
        'total': '#6C7FA1'
    };

    return colors[column] || '#' + Math.floor(Math.random() * 16777215).toString(16);
};
