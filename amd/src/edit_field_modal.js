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
 * Edit field modal form
 *
 * @module     local_envasyllabus/edit_field_modal
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import Notification from 'core/notification';

/**
 * Initialize the edit field modal functionality
 */
export const init = () => {
    // Prevent multiple initialization
    if (document.body.dataset.editFieldModalInitialized) {
        return;
    }
    document.body.dataset.editFieldModalInitialized = 'true';

    document.addEventListener('click', (event) => {
        // Check if the clicked element has the edit field action
        if (!event.target.closest('[data-action="edit-field"]')) {
            return;
        }

        const button = event.target.closest('[data-action="edit-field"]');
        event.preventDefault();

        // Get the field information from data attributes
        const courseid = button.dataset.courseid;
        const fieldid = button.dataset.fieldid;
        const fieldname = button.dataset.fieldname || '';

        // Determine if this is a multilingual field
        const multilingualFields = [
            'uc_summary', 'uc_summary_en',
            'uc_competences', 'uc_competences_en',
            'uc_prerequis', 'uc_prerequis_en',
            'uc_programme', 'uc_programme_en',
            'uc_validation', 'uc_validation_en',
            'uc_infos_compl', 'uc_infos_compl_en'
        ];

        const isMultilingual = multilingualFields.includes(fieldname);

        // Determine the modal title
        let titleString = 'editfield';
        if (isMultilingual) {
            titleString = 'multilanguagefields';
        }

        const modalForm = new ModalForm({
            modalConfig: {
                title: getString(titleString, 'local_envasyllabus'),
                large: true // Use large modal for better text area editing
            },
            formClass: '\\local_envasyllabus\\form\\edit_field_dynamic_form',
            args: {
                courseid: courseid,
                fieldid: fieldid
            },
            saveButtonText: getString('savechanges'),
        });

        // Handle successful form submission
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (event) => {
            const response = event.detail;
            if (response.result) {
                // Show success notification
                if (response.message) {
                    Notification.addNotification({
                        message: response.message,
                        type: 'success'
                    });
                }
                // Reload the page to show updated content
                window.location.reload();
            }
        });

        modalForm.show();
    });
};
