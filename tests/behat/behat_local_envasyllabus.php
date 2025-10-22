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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;

/**
 * Behat steps in plugin local_envasyllabus
 *
 * @package    local_envasyllabus
 * @category   test
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_envasyllabus extends behat_base {

    /**
     * Checks if edit button exists for a specific field.
     *
     * @Then /^I should see the edit button for field "(?P<fieldname_string>(?:[^"]|\\")*)"$/
     * @param string $fieldname The field shortname (e.g., uc_competences)
     * @throws ExpectationException
     */
    public function i_should_see_edit_button_for_field($fieldname) {
        // Look for button with data-action="edit-field" and data-fieldname matching.
        $selector = "button[data-action='edit-field'][data-fieldname='{$fieldname}']";
        $editbutton = $this->find('css', $selector);
        if (!$editbutton || !$editbutton->isVisible()) {
            throw new ExpectationException(
                "Edit button for field '{$fieldname}' not found or not visible on syllabus page",
                $this->getSession()
            );
        }
    }

    /**
     * Checks if edit button does NOT exist for a specific field.
     *
     * @Then /^I should not see the edit button for field "(?P<fieldname_string>(?:[^"]|\\")*)"$/
     * @param string $fieldname The field shortname (e.g., uc_competences)
     * @throws ExpectationException
     */
    public function i_should_not_see_edit_button_for_field($fieldname) {
        // Look for button with data-action="edit-field" and data-fieldname matching.
        $selector = "button[data-action='edit-field'][data-fieldname='{$fieldname}']";
        try {
            $editbutton = $this->find('css', $selector);
            if ($editbutton && $editbutton->isVisible()) {
                throw new ExpectationException(
                    "Edit button for field '{$fieldname}' was found but should not be visible",
                    $this->getSession()
                );
            }
        } catch (ElementNotFoundException $e) {
            // This is expected - the button should not be found.
            return;
        }
    }

    /**
     * Clicks the edit button for a specific field.
     *
     * @When /^I click the edit button for field "(?P<fieldname_string>(?:[^"]|\\")*)"$/
     * @param string $fieldname The field shortname (e.g., uc_competences)
     * @throws ExpectationException
     */
    public function i_click_edit_button_for_field($fieldname) {
        $selector = "button[data-action='edit-field'][data-fieldname='{$fieldname}']";
        $editbutton = $this->find('css', $selector);
        if (!$editbutton) {
            throw new ElementNotFoundException(
                $this->getSession(),
                'edit button',
                'css',
                $selector
            );
        }
        $editbutton->click();
        // Wait for modal to appear.
        $this->getSession()->wait(1000);
    }

    /**
     * Open the course syllabus page with editing mode enabled.
     *
     * @Given /^I am on "(?P<coursefullname_string>(?:[^"]|\\")*)" course syllabus page with editing mode on$/
     * @param string $coursefullname The course full name of the course.
     */
    public function i_am_on_course_syllabus_page_with_editing_mode_on($coursefullname) {
        $this->i_am_on_course_syllabus_page_with_editing_mode_set_to($coursefullname, 'on');
    }

    /**
     * Open the course syllabus page with editing mode off.
     *
     * @Given /^I am on "(?P<coursefullname_string>(?:[^"]|\\")*)" course syllabus page with editing mode off$/
     * @param string $coursefullname The course full name of the course.
     */
    public function i_am_on_course_syllabus_page_with_editing_mode_off($coursefullname) {
        $this->i_am_on_course_syllabus_page_with_editing_mode_set_to($coursefullname, 'off');
    }

    /**
     * Open the course syllabus page with editing mode set to either on, or off.
     *
     * @Given /^I am on "(?P<coursefullname_string>(?:[^"]|\\")*)" course syllabus page with editing mode "(?P<onoroff_string>on|off)"$/
     * @param string $coursefullname The course full name of the course.
     * @param string $onoroff Whether to switch editing on, or off.
     * @throws coding_exception
     */
    public function i_am_on_course_syllabus_page_with_editing_mode_set_to($coursefullname, $onoroff) {
        global $DB;

        if ($onoroff !== 'on' && $onoroff !== 'off') {
            throw new coding_exception("Unknown editing mode '{$onoroff}'. Accepted values are 'on' and 'off'");
        }

        // Get course ID from course full name.
        $course = $DB->get_record('course', ['fullname' => $coursefullname], '*', MUST_EXIST);
        $courseid = $course->id;
        $context = context_course::instance($courseid);

        // Build syllabus page URL.
        $syllabusurl = new moodle_url('/local/envasyllabus/syllabuspage.php', ['id' => $courseid]);

        // Build edit mode URL that redirects to syllabus page.
        $editmodeurl = new moodle_url('/editmode.php', [
            'context' => $context->id,
            'pageurl' => $syllabusurl->out(false),
            'setmode' => ($onoroff === 'on' ? 1 : 0),
        ]);

        $this->execute('behat_general::i_visit', [$editmodeurl]);
    }

    /**
     * Checks if the modal form opened correctly for field editing.
     *
     * @Then /^the edit field modal should be open$/
     * @throws ExpectationException
     */
    public function the_edit_field_modal_should_be_open() {
        // Wait for modal to appear.
        $this->getSession()->wait(2000);

        // Check for modal-form-dialogue class.
        $modal = $this->find('css', '.modal-form-dialogue');
        if (!$modal) {
            throw new ExpectationException(
                "Edit field modal is not open (modal-form-dialogue not found)",
                $this->getSession()
            );
        }
    }

    /**
     * Switches the language on the syllabus page.
     *
     * @When /^I switch syllabus language to "(?P<language_string>(?:[^"]|\\")*)"$/
     * @param string $language The language code (fra or en)
     */
    public function i_switch_syllabus_language_to($language) {
        $select = $this->find('css', '.local-envasyllabus-language-switcher select.singleselect');
        if (!$select) {
            throw new ElementNotFoundException(
                $this->getSession(),
                'language switcher',
                'css',
                '.local-envasyllabus-language-switcher select.singleselect'
            );
        }

        $select->selectOption($language);
        // Wait for language switch to complete.
        $this->getSession()->wait(1000);
    }
}
