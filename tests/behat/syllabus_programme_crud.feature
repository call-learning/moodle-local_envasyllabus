@local @local_envasyllabus @customfield @customfield_sprogramme @javascript
Feature: Programme CRUD operations in customfield_sprogramme

  Background:
    Given the following config values are set as admin:
      | enablenewprogramme | 1 | local_envasyllabus |
    And the following "courses" exist:
      | fullname          | shortname | enablecompletion |
      | Syllabus Course 1 | uc_SYLL1     | 1                |
    And the following "custom field categories" exist:
      | name          | component   | area   | itemid |
      | Course fields | core_course | course | 0      |
    And the following "custom fields" exist:
      | name             | category      | type       | shortname  | description |
      | SProgramme field | Course fields | sprogramme | programme | SProgramme  |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | uc_SYLL1  | editingteacher |
    And the programme custom field "programme" is enabled for course "uc_SYLL1"

  Scenario: Entering, Saving and checking data
    Given I am on the "uc_SYLL1" course page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Edit Programme" "link"

    # Create Module 1 with 2 rows
    And I set mod "1" name to "Module 1: Basic Concepts"
    And I set mod "1" row "1" column "Session title or exercise" to "Session 1"
    And I set mod "1" row "1" column "CM" to "2.5"
    And I add a new row to mod "1"
    And I set mod "1" row "2" column "Session title or exercise" to "Session 2"
    And I set mod "1" row "2" column "TD" to "3.0"

    # Add Module 2 with 2 rows
    And I add a new module
    And I set mod "2" name to "Module 2: Advanced Topics"
    And I set mod "2" row "1" column "Session title or exercise" to "Advanced Session 1"
    And I set mod "2" row "1" column "TP" to "4.5"
    And I add a new row to mod "2"
    And I set mod "2" row "2" column "Session title or exercise" to "Advanced Session 2"
    And I set mod "2" row "2" column "CM" to "1.5"

    # Save the programme
    And I click on "Save" "button" in the "Edit Programme" "dialogue"

    # Verify data appears on syllabus page
    And I am on "uc_SYLL1" course homepage
    When I click on "See Syllabus" "link"
    Then ".customfield-sprogramme.syllabuspage" "css_element" should exist
    And I should see "Module 1: Basic Concepts" in the programme region
    And I should see "Module 2: Advanced Topics" in the programme region
    And I should see "Session 1" in the programme region
    And I should see "Session 2" in the programme region
    And I should see "Advanced Session 1" in the programme region
    And I should see "Advanced Session 2" in the programme region

  Scenario: Entering, Modifying and checking data
    Given I am on the "uc_SYLL1" course page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Edit Programme" "link"

    # Create initial data
    And I set mod "1" name to "Module 1: Initial Name"
    And I set mod "1" row "1" column "Session title or exercise" to "Initial Session"
    And I set mod "1" row "1" column "CM" to "1.0"
    And I add a new row to mod "1"
    And I set mod "1" row "2" column "Session title or exercise" to "Second Session"
    And I set mod "1" row "2" column "TD" to "2.0"

    # Add second module
    And I add a new module
    And I set mod "2" name to "Module 2: Original Name"
    And I set mod "2" row "1" column "Session title or exercise" to "Original Session"
    And I set mod "2" row "1" column "TP" to "3.0"

    # Save initial data
    And I click on "Save" "button" in the "Edit Programme" "dialogue"

    # Modify the data
    And I set mod "1" name to "Module 1: Updated Name"
    And I set mod "1" row "1" column "Session title or exercise" to "Updated Session"
    And I set mod "1" row "1" column "CM" to "5.5"
    And I set mod "2" name to "Module 2: Modified Name"
    And I set mod "2" row "1" column "Session title or exercise" to "Modified Session"
    And I set mod "2" row "1" column "TP" to "7.5"

    # Save modifications
    And I click on "Save" "button" in the "Edit Programme" "dialogue"

    # Verify modifications appear on syllabus page
    And I am on "uc_SYLL1" course homepage
    When I click on "See Syllabus" "link"
    Then ".customfield-sprogramme.syllabuspage" "css_element" should exist
    And I should see "Module 1: Updated Name" in the programme region
    And I should see "Module 2: Modified Name" in the programme region
    And I should see "Updated Session" in the programme region
    And I should see "Modified Session" in the programme region
    And I should not see "Module 1: Initial Name" in the programme region
    And I should not see "Module 2: Original Name" in the programme region
    And I should not see "Initial Session" in the programme region
    And I should not see "Original Session" in the programme region

  Scenario: Entering, Deleting and checking
    Given I am on the "uc_SYLL1" course page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Edit Programme" "link"

    # Create initial data with 3 modules and multiple rows
    And I set mod "1" name to "Module 1: First Module"
    And I set mod "1" row "1" column "Session title or exercise" to "Module 1 Session 1"
    And I set mod "1" row "1" column "CM" to "1.5"
    And I add a new row to mod "1"
    And I set mod "1" row "2" column "Session title or exercise" to "Module 1 Session 2"
    And I set mod "1" row "2" column "TD" to "2.5"
    And I add a new row to mod "1"
    And I set mod "1" row "3" column "Session title or exercise" to "Module 1 Session 3"
    And I set mod "1" row "3" column "TP" to "3.5"

    And I add a new module
    And I set mod "2" name to "Module 2: Second Module"
    And I set mod "2" row "1" column "Session title or exercise" to "Module 2 Session 1"
    And I set mod "2" row "1" column "CM" to "4.5"

    And I add a new module
    And I set mod "3" name to "Module 3: Third Module"
    And I set mod "3" row "1" column "Session title or exercise" to "Module 3 Session 1"
    And I set mod "3" row "1" column "TP" to "5.5"

    # Save initial data
    And I click on "Save" "button" in the "Edit Programme" "dialogue"

    # Delete module 2 (middle module)
    And I delete mod "2"

    # Delete row 2 from module 1
    And I delete mod "1" row "2"

    # Save deletions
    And I click on "Save" "button" in the "Edit Programme" "dialogue"

    # Verify deletions on syllabus page
    And I am on "uc_SYLL1" course homepage
    When I click on "See Syllabus" "link"
    Then ".customfield-sprogramme.syllabuspage" "css_element" should exist
    And I should see "Module 1: First Module" in the programme region
    And I should see "Module 3: Third Module" in the programme region
    And I should see "Module 1 Session 1" in the programme region
    And I should see "Module 1 Session 3" in the programme region
    And I should see "Module 3 Session 1" in the programme region
    And I should not see "Module 2: Second Module" in the programme region
    And I should not see "Module 2 Session 1" in the programme region
    And I should not see "Module 1 Session 2" in the programme region

  Scenario: Editing from See Syllabus page
    Given I am on the "uc_SYLL1" course page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Edit Programme" "link"

    # Create initial data
    And I set mod "1" name to "Module 1: Original Title"
    And I set mod "1" row "1" column "Session title or exercise" to "Original Session Title"
    And I set mod "1" row "1" column "CM" to "2.0"
    And I add a new row to mod "1"
    And I set mod "1" row "2" column "Session title or exercise" to "Second Original Session"
    And I set mod "1" row "2" column "TD" to "3.0"

    And I add a new module
    And I set mod "2" name to "Module 2: Second Original"
    And I set mod "2" row "1" column "Session title or exercise" to "Module 2 Original Session"
    And I set mod "2" row "1" column "TP" to "4.0"

    # Save initial data
    And I click on "Save" "button" in the "Edit Programme" "dialogue"

    # Test editing from syllabus page with editing mode on
    And I am on "uc_SYLL1" course homepage with editing mode on
    When I click on "See Syllabus" "link"
    And I should see "Edit Programme"
    When I click on "Edit Programme" "link"

    # Make changes from syllabus page
    And I set mod "1" row "1" column "Session title or exercise" to "Syllabus Updated Session"
    And I set mod "2" row "1" column "Session title or exercise" to "Module 2 Syllabus Updated"

    # Save and close editing form
    And I click on "Save" "button" in the "Edit Programme" "dialogue"
    And I close the programme editing form

    # Verify changes are immediately visible
    And I should see "Syllabus Updated Session" in the programme region
    And I should see "Module 2 Syllabus Updated" in the programme region
    And I should not see "Original Session Title" in the programme region
    And I should not see "Module 2 Original Session" in the programme region

    # Test that editing mode off removes Edit Programme button
    And I am on "uc_SYLL1" course homepage with editing mode off
    When I click on "See Syllabus" "link"
    And I should not see "Edit Programme"

    # Verify changes persisted after turning editing mode off
    And I should see "Syllabus Updated Session" in the programme region
    And I should see "Module 2 Syllabus Updated" in the programme region
