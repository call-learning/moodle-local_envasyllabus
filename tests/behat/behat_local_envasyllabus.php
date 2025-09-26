<?php
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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;

/**
 * Behat steps in plugin enva syllabus combines with customfield sprogramme.
 *
 * @package    local_envasyllabus
 * @category   test
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_envasyllabus extends behat_base {
    /**
     * Checks if text exists in the programme syllabus region.
     *
     * @Then /^I should see \"(?P<text_string>(?:[^\"]|\\\\\")*)\" in the programme region$/
     * @param string $text The text to look for
     * @throws ExpectationException
     */
    public function i_should_see_text_in_programme_region($text) {
        // Find the programme region.
        $programmeregion = $this->find('css', '.customfield-sprogramme.syllabuspage');
        if (!$programmeregion) {
            throw new ElementNotFoundException(
                $this->getSession(),
                'programme region',
                'css',
                '.customfield-sprogramme.syllabuspage'
            );
        }

        // Check if the text exists in the programme region.
        $regiontext = $programmeregion->getText();
        if (strpos($regiontext, $text) === false) {
            throw new ExpectationException('Text "' . $text . '" not found in the programme region', $this->getSession());
        }
    }

    /**
     * Checks that text does NOT exist in the programme syllabus region.
     *
     * @Then /^I should not see \"(?P<text_string>(?:[^\"]|\\\\\")*)\" in the programme region$/
     * @param string $text The text that should not be found
     * @throws ExpectationException
     */
    public function i_should_not_see_text_in_programme_region($text) {
        // Find the programme region.
        $programmeregion = $this->find('css', '.customfield-sprogramme.syllabuspage');
        if (!$programmeregion) {
            throw new ElementNotFoundException(
                $this->getSession(),
                'programme region',
                'css',
                '.customfield-sprogramme.syllabuspage'
            );
        }

        // Check if the text does NOT exist in the programme region.
        $regiontext = $programmeregion->getText();
        if (strpos($regiontext, $text) !== false) {
            throw new ExpectationException(
                'Text "' . $text . '" was found in the programme region but should not be present',
                $this->getSession()
            );
        }
    }
}
