@local @local_envasyllabus @javascript
Feature: Syllabus page edit buttons visibility and functionality
  In order to edit custom fields from the syllabus page
  As a teacher
  I need to see edit buttons when editing mode is turned on and be able to edit multilingual content

  Background:
    And the following "courses" exist:
      | fullname          | shortname | enablecompletion |
      | Syllabus Course 1 | SYLL1     | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | SYLL1  | editingteacher |
      | student1 | SYLL1  | student        |

  Scenario: Edit buttons appear when editing mode is on and disappear when editing mode is off
    Given I am on the "SYLL1" course page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Compétences générales visées      | Initial FR competences     |
      | Compétences générales visées(en)  | Initial EN competences     |
      | Prérequis                         | Initial FR prerequisites   |
      | Prérequis(en)                     | Initial EN prerequisites   |
    And I press "Save and display"

    # Test with editing mode ON
    When I am on "Syllabus Course 1" course syllabus page with editing mode on
    Then I should see the edit button for field "uc_competences"
    And I should see the edit button for field "uc_prerequis"

    # Test with editing mode OFF
    When I am on "Syllabus Course 1" course syllabus page with editing mode off
    Then I should not see the edit button for field "uc_competences"
    And I should not see the edit button for field "uc_prerequis"

    # Verify content is still visible when editing mode is off
    And I should see "Initial FR competences"
    And I should see "Initial FR prerequisites"

  Scenario: Students cannot see edit buttons
    Given I am on the "SYLL1" course page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Compétences générales visées      | Initial FR competences     |
      | Compétences générales visées(en)  | Initial EN competences     |
      | Prérequis                         | Initial FR prerequisites   |
      | Prérequis(en)                     | Initial EN prerequisites   |
    And I press "Save and display"
    And I am on the "SYLL1" course page logged in as "student1"

    # Test as student
    When I am on "Syllabus Course 1" course syllabus page with editing mode off
    Then I should not see the edit button for field "uc_competences"
    And I should not see the edit button for field "uc_prerequis"

    # But should still see the content (from Background setup)
    And I should see "Initial FR competences"
    And I should see "Initial FR prerequisites"

  Scenario: Edit multilingual field content via modal and verify language switching
    Given I am on the "SYLL1" course page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Compétences générales visées      | Initial FR competences     |
      | Compétences générales visées(en)  | Initial EN competences     |
      | Prérequis                         | Initial FR prerequisites   |
      | Prérequis(en)                     | Initial EN prerequisites   |
    And I press "Save and display"

    # Go to syllabus page with editing mode on
    When I am on "Syllabus Course 1" course syllabus page with editing mode on

    # Verify initial content from Background setup
    Then I should see "Initial FR competences"

    # Click edit button and verify modal opens
    When I click the edit button for field "uc_competences"
    Then the edit field modal should be open

    And I set the following fields to these values:
      | Compétences générales visées      | Compétences mises à jour   |
      | Compétences générales visées(en)  | Updated competences        |
    And I click on "Save" "button" in the ".modal-form-dialogue" "css_element"

    # Wait for modal to close and page to reload
    And I wait "2" seconds

    # Verify French content is visible by default
    Then I should see "Compétences mises à jour"

    # Switch to English and verify English content
    When I switch syllabus language to "en"
    Then I should see "Updated competences"
    And I should not see "Compétences mises à jour"

    # Switch back to French
    When I switch syllabus language to "fra"
    Then I should see "Compétences mises à jour"
    And I should not see "Updated competences"
