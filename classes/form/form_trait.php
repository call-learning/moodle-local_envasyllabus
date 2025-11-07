<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify.
// it under the terms of the GNU General Public License as published by.
// the Free Software Foundation, either version 3 of the License, or.
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,.
// but WITHOUT ANY WARRANTY; without even the implied warranty of.
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the.
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form trait for Enva Syllabus plugin that helps with adding common fields.
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_envasyllabus\form;

use core_customfield\handler;

/**
 * Trait form_trait
 *
 * Provides helper methods to add common form fields.
 *
 * @package    local_envasyllabus
 * @copyright  2025 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait form_trait {
    /**
     * Check if a field is part of a multilingual pair
     * @param string $fieldshortname
     * @param \core_customfield\handler $handler
     * @return bool
     */
    private function is_multilingual_field(string $fieldshortname, \core_customfield\handler $handler): bool {
        $pairs = $this->get_multilingual_field_pairs();

        // Check if it's a base field that has an _en counterpart.
        if (isset($pairs[$fieldshortname])) {
            // Check if the english version actually exists.
            $fields = $handler->get_fields();
            foreach ($fields as $f) {
                if ($f->get('shortname') === $pairs[$fieldshortname]) {
                    return true;
                }
            }
        }

        // Check if it's an _en field that has a base counterpart.
        $basefield = array_search($fieldshortname, $pairs);
        if ($basefield !== false) {
            // Check if the base version actually exists.
            $fields = $handler->get_fields();
            foreach ($fields as $f) {
                if ($f->get('shortname') === $basefield) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * List of multilingual field pairs (base field => english field)
     * @return array
     */
    protected function get_multilingual_field_pairs(): array {
        return [
            'uc_competences' => 'uc_competences_en',
            'uc_prerequis' => 'uc_prerequis_en',
            'uc_programme' => 'uc_programme_en',
            'uc_validation' => 'uc_validation_en',
            'uc_infos_compl' => 'uc_infos_compl_en',
        ];
    }
}
