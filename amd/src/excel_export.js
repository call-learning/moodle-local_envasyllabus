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
 * Export button init.
 */
const initExportButton = () => {
    const button = document.querySelector(SELECTORS.EXPORT_BUTTON);
    if (!button) {
        return;
    }

    button.addEventListener('click', (e) => {
        e.preventDefault();

        if (isLocked(button)) {
            return;
        }

        // MUST stay synchronous (popup-safe)
        withButtonLock(button, () => {
            const {lang, extended} = getExportParamsFromUrl();
            const url = buildDownloadUrl(lang, extended);
            triggerDownloadViaNewTab(url);
        });
    });
};

/* =========================
 * Helpers
 * ========================= */

/**
 * Check if button is locked.
 *
 * @param {HTMLElement} button
 * @return {boolean}
 */
const isLocked = (button) =>
    button.getAttribute('data-locked') === 'true';

/**
 * Lock or unlock button.
 *
 * @param {HTMLElement} button
 * @param {boolean} locked
 */
const setLocked = (button, locked) => {
    button.setAttribute('data-locked', locked ? 'true' : 'false');
    button.disabled = locked;
};

/**
 * Set icon to loading spinner or original.
 *
 * @param {HTMLElement} button
 * @param {boolean} loading
 * @param {string} originalClasses
 */
const setIconLoading = (button, loading, originalClasses) => {
    const icon = button.querySelector(SELECTORS.ICON);
    if (!icon) {
        return;
    }
    icon.className = loading ? 'fa fa-spinner fa-spin' : originalClasses;
};

/**
 * Get export params from URL.
 * @return {{lang, extended: boolean}}
 */
const getExportParamsFromUrl = () => {
    const params = new URLSearchParams(window.location.search);
    return {
        lang: params.get('curlang') || 'en',
        extended: params.get('modus') === 'extended',
    };
};

/**
 * Build download URL with params.
 *
 * @param {string} lang
 * @param {string} extended
 * @return {string}
 */
const buildDownloadUrl = (lang, extended) => {
    const url = new URL(M.cfg.wwwroot + '/local/envasyllabus/download.php');
    url.searchParams.set('lang', lang);
    url.searchParams.set('modus', extended ? 'extended' : 'normal');
    return url.toString();
};

/**
 * Trigger download via new tab (popup-safe).
 * @param {string} url
 */
const triggerDownloadViaNewTab = (url) => {
    window.open(url, '_blank', 'noopener');
};

/**
 * Lock button, show spinner, run synchronous action, then unlock.
 * @param {Element} button
 * @param {Function} action Synchronous action to perform
 */
const withButtonLock = (button, action) => {
    const icon = button.querySelector(SELECTORS.ICON);
    const originalClasses = icon ? icon.className : '';

    try {
        setLocked(button, true);
        setIconLoading(button, true, originalClasses);

        // Must NOT contain await / promises.
        action();

        // UX unlock (download already started).
        window.setTimeout(() => {
            setLocked(button, false);
            setIconLoading(button, false, originalClasses);
        }, 1500);

    } catch (error) {
        setLocked(button, false);
        setIconLoading(button, false, originalClasses);
        Notification.exception(error);
    }
};