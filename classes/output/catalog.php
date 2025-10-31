<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_envasyllabus\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use context_system;
use moodle_url;
use local_envasyllabus\output\language_switcher;

/**
 * Catalog page
 *
 * @package     local_envasyllabus
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalog implements renderable, templatable {
    /**
     * Default course category
     */
    const DEFAULT_COURSE_CATEGORY = 124;

    /**
     * @var mixed|string
     */
    private $currentlang;

    /**
     * @var bool $listview
     */
    private $listview = false;

    /**
     * @var string $modus
     */
    private $modus = 'normal';

    /**
     * @var bool $gridview
     */
    private $gridview = true;

    /**
     * Current lang
     *
     * @param string $currentlang
     */
    public function __construct($currentlang = '') {
        $this->listview = optional_param('listview', false, PARAM_BOOL);
        if ($this->listview) {
            $this->gridview = false;
        }
        $this->modus = optional_param('modus', 'normal', PARAM_TEXT);
        $this->currentlang = $currentlang;
    }
    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return stdClass
     * @throws \dml_exception
     */
    public function export_for_template(renderer_base $output) {
        $context = new stdClass();
        $filterform = new \local_envasyllabus\form\catalog_filter_form();
        $filterform->set_display_vertical();
        $context->filterform = $filterform->render();
        $context->categoryrootid = get_config('local_envasyllabus', 'rootcategoryid');
        $context->currentlang = $this->currentlang ?? '';
        $context->viewtype = $this->listview ? 'list' : 'grid';
        $context->modus = $this->modus;
        $context->extendedmodus = $this->modus === 'extended';
        $context->normalmodus = $this->modus === 'normal';
        $context->listview = $this->listview;
        $context->gridview = $this->gridview;
        $context->canexport = has_capability(
            'local/envasyllabus:exportcatalog',
            context_system::instance()
        );
        $languageswitcher = new language_switcher();
        $context->languageswitcher = $languageswitcher->export_for_template($output);
        return $context;
    }
}
