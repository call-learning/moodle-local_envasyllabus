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
 * API access functions
 *
 * @copyright  2022 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 *
 * @param {number} rootCategoryId
 * @param {object} filterParams
 * @param {string} currentLang
 * @returns {*}
 */
export const getCoursesForCategoryId = function (rootCategoryId, filterParams = {}, currentLang = 'fr') {

    let request = {
        methodname: 'local_envasyllabus_get_filtered_courses',
        args: Object.assign({
            rootcategoryid: rootCategoryId,
            currentlang : currentLang
        }, filterParams)
    };

    return Ajax.call([request])[0];
};
