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
    const sortedCourses = buildCourseList(data.courses, data.semestertotals);
    const isChartView = (element.dataset.viewtype === 'chart');
    let templateName = 'local_envasyllabus/catalog_course_categories';
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
 * @param {Array} semestertotals - Precalculated semester totals from the API and sorted courses
 * @returns {{year: *, semesters: *}[]}
 */
const buildCourseList = (courses, semestertotals) => {
    // Add course info and custom fields to each semester
    const semesterWithCourses = semestertotals.map(semesterInfo => {
        const semester = {
            semester: semesterInfo.semester,
            year: semesterInfo.year,
            courses: [],
            totals: {
                programmevalues: semesterInfo.programmevalues,
                ects: semesterInfo.ects,
            }
        };
        const semesterCourses = semesterInfo.courseidlist.map(courseidinfo => {
            return courses.find(course => course.id === courseidinfo.id);
        });

        semester.courses = semesterCourses.map(
            (course) => {
                if (course.customfields) {
                    course.cf = {};
                    course.customfields.forEach((cf) => {
                        course.cf[cf.shortname] = cf;
                    });
                }
                course.viewurl = Config.wwwroot + '/course/view.php?id=' + course.id;
                course.syllabusurl = Config.wwwroot + '/local/envasyllabus/syllabuspage.php?id=' + course.id;
                return course;
            }
        );
        return semester;
    });
    return Object.values(
        semesterWithCourses.reduce((acc, semester) => {
            const year = semester.year;
            if (!acc[year]) {
                acc[year] = {
                    year: year,
                    semesters: []
                };
            }
            acc[year].semesters.push(semester);
            return acc;
        }, {})
    );
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