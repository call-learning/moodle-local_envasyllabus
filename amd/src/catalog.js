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
import Config from 'core/config';
import $ from 'jquery';
import LocalisedTemplates from "./localised_templates";
import {buildChartData} from "./chart_builder";
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
            refreshCoursesList(catalogTagId, currentFilterParams);
        }
    });
    const toggleButtonRegions = document.querySelector('[data-region="catalogueviewtoggle"]');
    const listViewButton = toggleButtonRegions.querySelector('[data-action="listview"]');
    const gridViewButton = toggleButtonRegions.querySelector('[data-action="gridview"]');
    const chartViewButton = toggleButtonRegions.querySelector('[data-action="chartview"]');
    listViewButton.addEventListener('click', (event) => {
        event.preventDefault();
        jumpTo('viewtype', 'list');
    });
    gridViewButton.addEventListener('click', (event) => {
        event.preventDefault();
        jumpTo('viewtype', 'grid');
    });
    chartViewButton.addEventListener('click', (event) => {
        event.preventDefault();
        jumpTo('viewtype', 'chart');
    });
    const extendedModeCheckbox = document.getElementById('catalog-extendedmode');
    extendedModeCheckbox.addEventListener('change', (event) => {
        const catalogCourseTag = getCatalogCourseTag(catalogTagId);
        catalogCourseTag.dataset.modus = event.target.checked ? 'extended' : 'normal';
        jumpTo('modus', event.target.checked ? 'extended' : 'normal');
    });

    // Make table rows clickable.
    document.addEventListener('click', (event) => {
        const row = event.target.closest('tr.course-row[data-href]');
        if (row && !event.target.closest('a')) {
            window.location.href = row.dataset.href;
        }
    });

    document.addEventListener('click', (event) => {
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
        (data) => renderPage(catalogCourseTag, data)).catch(displayException);
};

/**
 * Render all courses
 *
 * @param {Object} element element to render into
 * @param {Array} data list of courses with data
 */
const renderPage = (element, data) => {
    LocalisedTemplates.setLanguage(element.dataset.currentLang ?? 'fr');
    const sortedCourses = buildCourseList(data.courses, data.programmecolumns);
    const isChartView = (element.dataset.viewtype === 'chart');
    let  templateName= 'local_envasyllabus/catalog_course_categories';
    let context = {};
    if (isChartView) {
        context = {
            chartdata: JSON.stringify(buildChartData(sortedCourses, data.programmecolumns))
        };
        templateName = 'local_envasyllabus/catalog_course_chart';
    } else {
        context = {
            sortedcourses: sortedCourses,
            programmecolumns: data.programmecolumns,
            gridview: (element.dataset.viewtype == 'grid'),
            listview: (element.dataset.viewtype == 'list'),
            normalmodus: (element.dataset.modus == 'normal'),
            extendedmodus: (element.dataset.modus == 'extended'),
        };
    }
    LocalisedTemplates.render(templateName, context).then((html, js) => {
        return LocalisedTemplates.replaceNodeContents(element, html, js);
    }).catch(displayException);
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
const jumpTo = (key, value) => {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    window.location.href = url.toString();
};