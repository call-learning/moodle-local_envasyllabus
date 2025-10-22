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
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';

/**
 * Initialize excel export functionality
 *
 * @param {int} catalogTagId
 */
export const init = (catalogTagId) => {
    // Add export button to the catalog
    addExportButton(catalogTagId);
};

/**
 * Add export button to catalog
 *
 * @param {int} catalogTagId
 */
const addExportButton = (catalogTagId) => {
    // Find the catalog container
    const catalogContainer = document.querySelector('[data-catalog-tag-id="' + catalogTagId + '"]');
    if (!catalogContainer) {
        return;
    }

    // Find the filter container or create one
    let filterContainer = catalogContainer.querySelector('[data-region="catalog-filter"]');
    if (!filterContainer) {
        filterContainer = catalogContainer.querySelector('.catalog-filters');
    }

    if (filterContainer) {
        // Create export button
        const exportBtn = document.createElement('button');
        exportBtn.className = 'btn btn-secondary ml-2';
        exportBtn.type = 'button';
        exportBtn.innerHTML = '<i class="fa fa-download" aria-hidden="true"></i> Export Excel';

        // Add click handler
        exportBtn.addEventListener('click', () => {
            handleExportClick(catalogTagId);
        });

        // Append button to filter container
        filterContainer.appendChild(exportBtn);
    }
};

/**
 * Handle export button click
 *
 * @param {int} catalogTagId
 */
const handleExportClick = (catalogTagId) => {
    const catalogCourseTag = getCatalogCourseTag(catalogTagId);
    if (!catalogCourseTag) {
        return;
    }

    // Get parameters from catalog
    const categoryId = catalogCourseTag.dataset.categoryRootId || 0;
    const extendedMode = catalogCourseTag.dataset.modus === 'extended' ? 1 : 0;
    const currentLang = catalogCourseTag.dataset.currentLang || 'en';

    // Build export URL
    const baseUrl = `${Config.wwwroot}/local/envasyllabus/export.php`;
    const params = `?categoryid=${categoryId}&extendedmode=${extendedMode}&lang=${currentLang}`;
    const exportUrl = baseUrl + params;

    // Trigger download
    window.location.href = exportUrl;
};

/**
 * Get catalog course tag element
 *
 * @param {int} catalogTagId
 * @returns {Element|null}
 */
const getCatalogCourseTag = (catalogTagId) => {
    return document.querySelector('[data-catalog-tag-id="' + catalogTagId + '"]');
};
