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
 * Javascript to initialise the enva syllabus catalog page.
 *
 * @copyright  2022 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as repository from './repository';
import {exception as displayException} from 'core/notification';
import Templates from "core/templates";
import Config from 'core/config';
import $ from 'jquery';
/**
 * Current filter parameters
 * @type {Object}
 */
let currentFilterParams = {
    sort: {
        field: 'fullname',
        order: 'asc'
    }
};

/**
 * Initialise catalog
 *
 * @param {int} catalogTagId
 */
export const init = (catalogTagId) => {
    refreshCoursesList(catalogTagId, currentFilterParams);

    document.addEventListener('enva-syllabus-catalog-filter', (eventData) => {
        if (eventData.detail) {
            currentFilterParams = eventData.detail;
            const catalogCourseTag = getCatalogCourseTag(catalogTagId);
            if (catalogCourseTag.dataset.viewtype === 'chart') {
                refreshCoursesChart(catalogTagId, currentFilterParams);
            } else {
                refreshCoursesList(catalogTagId, currentFilterParams);
            }
        }
    });
    const toggleButtonRegions = document.querySelector('[data-region="catalogueviewtoggle"]');
    const listViewButton = toggleButtonRegions.querySelector('[data-action="listview"]');
    const gridViewButton = toggleButtonRegions.querySelector('[data-action="gridview"]');
    const chartViewButton = toggleButtonRegions.querySelector('[data-action="chartview"]');
    listViewButton.addEventListener('click', (event) => {
        event.preventDefault();
        listViewButton.classList.add('active');
        chartViewButton.classList.remove('active');
        gridViewButton.classList.remove('active');
        const catalogCourseTag = getCatalogCourseTag(catalogTagId);
        catalogCourseTag.dataset.viewtype = 'list';
        updateUrl('listview', '1');
        refreshCoursesList(catalogTagId, currentFilterParams);
    });
    gridViewButton.addEventListener('click', (event) => {
        event.preventDefault();
        listViewButton.classList.remove('active');
        chartViewButton.classList.remove('active');
        gridViewButton.classList.add('active');
        const catalogCourseTag = getCatalogCourseTag(catalogTagId);
        catalogCourseTag.dataset.viewtype = 'grid';
        updateUrl('listview', '0');
        refreshCoursesList(catalogTagId, currentFilterParams);
    });
    chartViewButton.addEventListener('click', (event) => {
        event.preventDefault();
        listViewButton.classList.remove('active');
        gridViewButton.classList.remove('active');
        chartViewButton.classList.add('active');
        const catalogCourseTag = getCatalogCourseTag(catalogTagId);
        catalogCourseTag.dataset.viewtype = 'chart';
        updateUrl('listview', 'chart');
        refreshCoursesChart(catalogTagId, currentFilterParams);
    });
    const extendedModeCheckbox = document.getElementById('catalog-extendedmode');
    extendedModeCheckbox.addEventListener('change', (event) => {
        const catalogCourseTag = getCatalogCourseTag(catalogTagId);
        catalogCourseTag.dataset.modus = event.target.checked ? 'extended' : 'normal';
        updateUrl('modus', event.target.checked ? 'extended' : 'normal');
        if (catalogCourseTag.dataset.viewtype === 'chart') {
            refreshCoursesChart(catalogTagId, currentFilterParams);
        } else {
            refreshCoursesList(catalogTagId, currentFilterParams);
        }
    });
    document.addEventListener('click', async(event) => {
        const popOvers = document.querySelectorAll('[data-toggle="popover"]');
        const currentPopover = event.target.closest('[data-toggle="popover"]');
        if (popOvers.length > 0) {
            popOvers.forEach((popover) => {
                if (popover !== currentPopover) {
                    $(popover).popover('hide');
                }
            });
        }
    });
};

const getCatalogCourseTag = (catalogTagId) => {
    const catalogNode = document.getElementById(catalogTagId);
    return catalogNode.querySelector('.catalog-courses');
};

const refreshCoursesList = (catalogTagId, filterParams = {}) => {
    const catalogNode = document.getElementById(catalogTagId);
    const catalogCourseTag = catalogNode.querySelector('.catalog-courses');
    const rootCategoryId = JSON.parse(catalogCourseTag.dataset.categoryRootId);
    const currentLang = catalogCourseTag.dataset.currentLang;
    repository.getCoursesForCategoryId(rootCategoryId, filterParams, currentLang).then(
        (data) => renderCourses(catalogCourseTag, data)).catch(displayException);
};

/**
 * Refresh and display courses as chart
 *
 * @param {int} catalogTagId
 * @param {object} filterParams
 */
const refreshCoursesChart = (catalogTagId, filterParams = {}) => {
    const catalogNode = document.getElementById(catalogTagId);
    const catalogCourseTag = catalogNode.querySelector('.catalog-courses');
    const rootCategoryId = JSON.parse(catalogCourseTag.dataset.categoryRootId);
    const currentLang = catalogCourseTag.dataset.currentLang;
    repository.getCoursesForCategoryId(rootCategoryId, filterParams, currentLang).then(
        (data) => renderCoursesChart(catalogCourseTag, data)).catch(displayException);
};

/**
 * Render all courses
 *
 * @param {Object} element element to render into
 * @param {Array} data list of courses with data
 */
const renderCourses = (element, data) => {
    Templates.render('local_envasyllabus/catalog_course_categories', {
        sortedCourses: buildCourseList(data.courses, data.programmecolumns),
        programmecolumns: data.programmecolumns,
        gridview: (element.dataset.viewtype == 'grid'),
        listview: (element.dataset.viewtype == 'list'),
        chartview: (element.dataset.viewtype == 'chart'),
        normalmodus: (element.dataset.modus == 'normal'),
        extendedmodus: (element.dataset.modus == 'extended'),
    }).then((html, js) => {
        return Templates.replaceNodeContents(element, html, js);
    }).catch(displayException);
};

/**
 * Render courses as chart view
 *
 * @param {Object} element element to render into
 * @param {Object} data list of courses with data
 */
const renderCoursesChart = (element, data) => {
    const sortedCourses = buildCourseList(data.courses, data.programmecolumns);
    const chartData = buildChartData(sortedCourses, data.programmecolumns);

    Templates.render('local_envasyllabus/catalog_course_categories', {
        sortedCourses: [],
        programmecolumns: data.programmecolumns,
        gridview: false,
        listview: false,
        chartview: true,
        normalmodus: (element.dataset.modus == 'normal'),
        extendedmodus: (element.dataset.modus == 'extended'),
        chartdata: JSON.stringify(chartData),
        uniqid: Date.now().toString()
    }).then((html, js) => {
        return Templates.replaceNodeContents(element, html, js);
    }).catch(displayException);
};

/**
 * Build chart data structure for Chart.js stacked bar chart
 *
 * @param {Array} sortedCourses - Courses sorted by year and semester
 * @param {Array} programmecolumns - Programme column definitions
 * @returns {Object} Chart data combining all years
 */
const buildChartData = (sortedCourses, programmecolumns) => {
    // Filter out 'active' and 'total' columns for chart display.
    const chartColumns = programmecolumns.filter((col) =>
        col.column !== 'active' && col.column !== 'total'
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
                `${yearData.year}-S${semester.semester}` :
                `${yearData.year}-No Semester`;
            labels.push(semesterLabel);

            // Add data for each programme column from totals.
            if (semester.totals && semester.totals.programmevalues) {
                semester.totals.programmevalues.forEach((pv) => {
                    if (datasets[pv.column]) {
                        datasets[pv.column].data.push(parseFloat(pv.sum) || 0);
                    }
                });
            } else {
                // If no totals, add zeros.
                chartColumns.forEach((col) => {
                    datasets[col.column].data.push(0);
                });
            }
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


/**
 * Sort courses by year and semester
 *
 * Also tweaks the display depending on language selected
 * @param {Array} courses
 * @param {Array} programmecolumns - Programme column definitions for ordering
 * @returns {{year: *, semesters: *}[]}
 */
const buildCourseList = (courses, programmecolumns = []) => {
    let sortedCourses = {};
    for (let course of courses.values()) {
        const yearValue = findValueForCustomField(course, 'uc_annee');
        const semesterValue = findValueForCustomField(course, 'uc_semestre');
        if (yearValue) {
            if (!sortedCourses.hasOwnProperty(yearValue)) {
                sortedCourses[yearValue] = {
                    year: yearValue,
                    semesters: []
                };
            }
            if (!sortedCourses[yearValue].semesters[semesterValue]) {
                sortedCourses[yearValue].semesters[semesterValue] = {
                    semester: semesterValue,
                    year: yearValue,
                    courses: []
                };
            }
            if (course.customfields) {
                course.cf = {};
                course.customfields.forEach((cf) => {
                    course.cf[cf.shortname] = cf;
                });
            }
            course.viewurl = Config.wwwroot + '/course/view.php?id=' + course.id;
            course.syllabusurl = Config.wwwroot + '/local/envasyllabus/syllabuspage.php?id=' + course.id;
            sortedCourses[yearValue].semesters[semesterValue].courses.push(course);
        }
    }

    // Calculate totals for each semester.
    Object.values(sortedCourses).forEach((yearDef) => {
        Object.values(yearDef.semesters).forEach((semester) => {
            const totals = {
                displayname: '',
                cf: {
                    // eslint-disable-next-line camelcase
                    uc_ects: {value: 0}
                },
                programmevalues: [],
                istotal: true
            };

            // Sum ECTS and programme values.
            const programmeMap = new Map();
            semester.courses.forEach((course) => {
                // Sum ECTS.
                const ects = parseFloat(course.cf?.uc_ects?.value) || 0;
                totals.cf.uc_ects.value += ects;

                // Sum programme values.
                if (course.programmevalues) {
                    course.programmevalues.forEach((pv) => {
                        const currentSum = programmeMap.get(pv.column) || 0;
                        const pvValue = parseFloat(pv.sum) || 0;
                        programmeMap.set(pv.column, currentSum + pvValue);
                    });
                }
            });

            // Convert programme map to array in the correct column order.
            if (programmecolumns.length > 0) {
                // Use the column order from programmecolumns.
                programmecolumns.forEach((col) => {
                    const sum = programmeMap.get(col.column) || 0;
                    totals.programmevalues.push({column: col.column, sum});
                });
            } else {
                // Fallback: use the map order.
                programmeMap.forEach((sum, column) => {
                    totals.programmevalues.push({column, sum});
                });
            }

            // Round ECTS to 2 decimal places.
            totals.cf.uc_ects.value = Math.round(totals.cf.uc_ects.value * 100) / 100;

            semester.totals = totals;
        });
    });

    // Flatten the object into an array.
    return Object.entries(sortedCourses)
        // Preserve the order of the years as Object.entries does not.
        .sort((y1, y2) => y1[0].localeCompare(y2[0]))
        .map(
            ([, yearDef]) => {
                // Always sort by semesters.
                const sortedSemesters = Object.keys(yearDef.semesters)
                    .sort()
                    .reduce((acc, key) => {
                        acc[key] = yearDef.semesters[key];
                        return acc;
                    }, {});

                return {
                    year: yearDef.year,
                    semesters: Object.values(sortedSemesters)
                };
            }
        );
};

/**
 * Retrieve the value of a give customfield from course data
 *
 * @param {Object} course course data
 * @param {string} cfsname shortname for customfield
 * @param {null|Object|int|String} defaultValue
 * @returns null|Object|int|String
 */
const findValueForCustomField = (course, cfsname, defaultValue = null) => {
    if (typeof course.customfields !== 'undefined') {
        for (let cf of course.customfields.values()) {
            if (cf.shortname === cfsname) {
                return cf.value;
            }
        }
    }
    return defaultValue;
};

/**
 * Update the current page URL with the selected query parameters
 *
 * @param {String} key
 * @param {String} value
 */
const updateUrl = (key, value) => {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    window.history.pushState({}, '', url);
};
