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
 * Localised str for Enva Syllabus.
 *
 *
 * anguage-aware getStrings which does NOT fall back to page language via M.str.
 * It avoids clobbering M.str[component][key] by using a synthetic component namespace
 * when lang != page language.
 *
 * @module     local_envasyllabus/localised_templates
 *
 * @copyright  2022 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';
import Config from 'core/config';
import LocalStorage from 'core/localstorage';

let promiseCache = Object.create(null);

/**
 * Build a synthetic component name so we can safely store non-page-language strings in M.str
 * without overwriting the real component bucket.
 *
 * @param {string} component
 * @param {string} lang
 * @returns {string}
 */
const getComponentBucket = (component, lang) => {
    const pageLang = Config.language;
    if (!lang || lang === pageLang) {
        return component || 'core';
    }
    // Keep it readable + stable.
    return `${component || 'core'}__l10n_${lang}`;
};

const getCacheKey = ({key, component, lang}) => `localised_str/${key}/${component}/${lang}`;

/* eslint-disable no-restricted-properties */
/**
 * Like core/str.getStrings but language-safe.
 *
 * @param {Array<{key: string, component?: string, param?: object|string, lang?: string}>} requests
 * @param {string|null} forcedLang If provided, overrides per-request lang
 * @returns {Promise<string[]>}
 */
export const getStrings = (requests, forcedLang = null) => {
    const pageLang = Config.language;
    let requestData = [];

    const stringPromises = requests.map((req) => {
        const key = req.key;
        const component = req.component || 'core';
        const param = req.param;
        const lang = (forcedLang || req.lang || pageLang);

        const bucket = getComponentBucket(component, lang);
        const cacheKey = getCacheKey({key, component, lang});

        const buildReturn = (promise) => {
            promiseCache[cacheKey] = promise;
            return promise;
        };

        // IMPORTANT: Only trust M.str fast-path when the language is the page language.
        // Otherwise core/str will incorrectly serve the page-language string.
        if (lang === pageLang) {
            if (component in M.str && key in M.str[component]) {
                return buildReturn(Promise.resolve(M.util.get_string(key, component, param)));
            }
        } else {
            // But we *can* use our own bucket as a cache for that language.
            if (bucket in M.str && key in M.str[bucket]) {
                return buildReturn(Promise.resolve(M.util.get_string(key, bucket, param)));
            }
        }

        // LocalStorage cache (language-aware by our own key).
        const cached = LocalStorage.get(cacheKey);
        if (cached) {
            if (!(bucket in M.str)) {
                M.str[bucket] = {};
            }
            M.str[bucket][key] = cached;
            return buildReturn(Promise.resolve(M.util.get_string(key, bucket, param)));
        }

        // Promise cache.
        if (cacheKey in promiseCache) {
            return buildReturn(promiseCache[cacheKey]).then(() => M.util.get_string(key, bucket, param));
        }

        // Server fetch.
        return buildReturn(new Promise((resolve, reject) => {
            requestData.push({
                methodname: 'core_get_string',
                args: {
                    stringid: key,
                    stringparams: [],
                    component,
                    lang,
                },
                done: (str) => {
                    // Store into the synthetic bucket (or normal bucket if lang == page lang).
                    if (!(bucket in M.str)) {
                        M.str[bucket] = {};
                    }
                    M.str[bucket][key] = str;

                    LocalStorage.set(cacheKey, str);

                    resolve(M.util.get_string(key, bucket, param));
                },
                fail: reject,
            });
        }));
    });

    if (requestData.length) {
        Ajax.call(requestData, true, false, false, 0, M.cfg.langrev);
    }

    return Promise.all(stringPromises);
};