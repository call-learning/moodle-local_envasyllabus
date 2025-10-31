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
 * Excel export functionality for envasyllabus catalog
 *
 * @module     local_envasyllabus/excel_export
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

const SELECTORS = {
    EXPORT_BUTTON: '[data-action="exportexcel"]',
    ICON: 'i.fa',
};

/**
 * Initialize excel export functionality
 */
export const init = () => {
    initExportButton();
};

/**
 * Initialize export button event handler
 */
const initExportButton = () => {
    const exportBtn = document.querySelector(SELECTORS.EXPORT_BUTTON);

    if (!exportBtn) {
        return;
    }

    exportBtn.addEventListener('click', (e) => {
        e.preventDefault();

        // Don't allow clicking if already locked.
        if (exportBtn.getAttribute('data-locked') === 'true') {
            return;
        }

        handleExportClick(exportBtn);
    });
};

/**
 * Handle export button click
 *
 * @param {HTMLElement} button The export button element
 */
const handleExportClick = async(button) => {
    const icon = button.querySelector(SELECTORS.ICON);
    const originalClasses = icon.className;

    // Lock the button.
    button.setAttribute('data-locked', 'true');
    button.disabled = true;
    icon.className = 'fa fa-spinner fa-spin';

    // Get export parameters.
    const urlParams = new URLSearchParams(window.location.search);
    const lang = urlParams.get('lang') || 'en';
    const extended = urlParams.get('modus') === 'extended';

    try {
        // Call the external service.
        const response = await Ajax.call([{
            methodname: 'local_envasyllabus_export_catalog',
            args: {
                lang: lang,
                extended: extended
            }
        }])[0];

        if (response.success) {
            // Trigger download using hidden iframe.
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = response.downloadurl;
            document.body.appendChild(iframe);

            // Remove iframe after download starts.
            setTimeout(() => {
                if (iframe.parentNode) {
                    iframe.parentNode.removeChild(iframe);
                }
            }, 5000);

            // Reset button after short delay.
            setTimeout(() => {
                button.setAttribute('data-locked', 'false');
                button.disabled = false;
                icon.className = originalClasses;
            }, 2000);
        } else {
            throw new Error('Export failed');
        }
    } catch (error) {
        // Reset button on error.
        button.setAttribute('data-locked', 'false');
        button.disabled = false;
        icon.className = originalClasses;

        Notification.exception(error);
    }
};
