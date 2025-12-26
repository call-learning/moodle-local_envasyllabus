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
 * Localised templates for Enva Syllabus.
 *
 * When we render the content of the templates and we have selected a different
 * language than the default one, we need to adjust the template content because
 * if not it keeps using the default language content of the page.
 *
 * @module     local_envasyllabus/localised_templates
 *
 * @copyright  2022 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import CoreTemplates from 'core/templates';
import * as coreConfig from 'core/config';
import CoreRenderer from 'core/local/templates/renderer';
import * as UserDate from 'core/user_date';
import {getStrings as getLocalisedStrings} from 'local_envasyllabus/localised_str';

let forcedLang = null;

class LocalisedRenderer extends CoreRenderer {
    async processRenderedContent(renderedContent) {
        let html = renderedContent.trim();
        let js = this.getJS();

        const lang = forcedLang ?? coreConfig.language;

        if (this.requiredStrings.length > 0) {
            // Use our language-safe getStrings (no M.str page-lang fallback).
            const strings = await getLocalisedStrings(this.requiredStrings, lang);

            const stringMap = new Map(
                strings.map((string, index) => [`[[_s${index}]]`, string])
            );

            this.requiredDates = this.requiredDates.map(function(date) {
                return {
                    timestamp: this.treatStringsInContent(date.timestamp, stringMap),
                    format: this.treatStringsInContent(date.format, stringMap),
                };
            }.bind(this));

            html = this.treatStringsInContent(html, stringMap);
            js = this.treatStringsInContent(js, stringMap);
        }

        if (this.requiredDates.length > 0) {
            const dates = await UserDate.get(this.requiredDates);
            html = this.treatDatesInContent(html, dates);
            js = this.treatDatesInContent(js, dates);
        }

        return {html, js};
    }
}

const LocalisedTemplates = {
    ...CoreTemplates,

    setLanguage: (langcode) => {
        forcedLang = langcode || null;
    },

    render: (templateName, context, themeName = coreConfig.theme) => {
        const renderer = new LocalisedRenderer();

        return $.when(new Promise((resolve, reject) => {
            renderer.render(templateName, context, themeName).then(resolve).catch(reject);
        })).then(({html, js}) => $.Deferred().resolve(html, js));
    },

    renderForPromise: (templateName, context, themeName) => {
        const renderer = new LocalisedRenderer();
        return renderer.render(templateName, context, themeName);
    },
};

export default LocalisedTemplates;
