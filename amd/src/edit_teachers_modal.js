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
 * Teaching team edit modal handler.
 *
 * @module     local_envasyllabus/edit_teachers_modal
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import * as Str from 'core/str';

const SELECTORS = {
    EDIT_TEACHERS_BUTTON: '[data-action="edit-teachers"]',
};

/**
 * Initialize the edit teachers modal functionality.
 */
export const init = () => {
    document.addEventListener('click', (e) => {
        const button = e.target.closest(SELECTORS.EDIT_TEACHERS_BUTTON);
        if (!button) {
            return;
        }

        e.preventDefault();

        const courseId = button.getAttribute('data-courseid');
        if (!courseId) {
            return;
        }

        showEditTeachersModal(courseId);
    });
};

/**
 * Show the edit teachers modal with explanation and link to participants page.
 *
 * @param {string} courseId The course ID
 */
const showEditTeachersModal = async(courseId) => {
    try {
        // Get language strings
        const [title, explanation, linkText] = await Promise.all([
            Str.get_string('edit_teachers', 'local_envasyllabus'),
            Str.get_string('edit_teachers_explanation', 'local_envasyllabus'),
            Str.get_string('manage_participants', 'local_envasyllabus')
        ]);

        // Create the modal
        const modal = await Modal.create({
            title: title,
            body: `
                <div class="mb-3">
                    <p>${explanation}</p>
                </div>
                <div class="text-center">
                    <a href="${M.cfg.wwwroot}/user/index.php?id=${courseId}"
                       class="btn btn-primary"
                       target="_blank">
                        ${linkText}
                    </a>
                </div>
            `,
            footer: '',
            show: true,
        });

        // Show the modal
        modal.show();

    } catch (error) {
        // eslint-disable-next-line no-console
        console.error('Error showing edit teachers modal:', error);
    }
};
