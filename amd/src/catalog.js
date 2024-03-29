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

/**
 * Initialise catalog
 *
 * @param {int} catalogTagId
 */
export const init = (catalogTagId) => {
    // TODO: take the initial filter from the form.
    refreshCoursesList(catalogTagId, {
        sort: {
            field: 'fullname',
            order: 'asc'
        }
    });
    document.addEventListener('enva-syllabus-catalog-filter', (eventData) => {
        if (eventData.detail) {
            refreshCoursesList(catalogTagId, eventData.detail);
        }
    });
};

const refreshCoursesList = (catalogTagId, filterParams = {}) => {
    const catalogNode = document.getElementById(catalogTagId);
    const catalogCourseTag = catalogNode.querySelector('.catalog-courses');
    const rootCategoryId = JSON.parse(catalogCourseTag.dataset.categoryRootId);
    const currentLang = catalogCourseTag.dataset.currentLang;
    repository.getCoursesForCategoryId(rootCategoryId, filterParams, currentLang).then(
        (courses) => renderCourses(catalogCourseTag, courses)).catch(displayException);
};
/**
 * Render all courses
 *
 * @param {Object} element element to render into
 * @param {Array} courses list of courses with data
 */
const renderCourses = (element, courses) => {
    Templates.render('local_envasyllabus/catalog_course_categories', {
        sortedCourses: buildCourseList(courses)
    }).then((html, js) => {
        Templates.replaceNodeContents(element, html, js);
    }).catch(displayException);
};

/**
 * Sort courses by year and semester
 *
 * Also tweaks the display depending on language selected
 * @param {Array} courses
 * @returns {{year: *, semesters: *}[]}
 */
const buildCourseList = (courses) => {
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
    // Flattern the object into an array.
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
