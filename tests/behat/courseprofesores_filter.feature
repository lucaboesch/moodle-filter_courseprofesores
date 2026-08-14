@filter @filter_courseprofesores
Feature: Render text content using a courseprofesores filter
  In order to know my course embedding
  As a student
  I need to be able to see the course professors

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | section | intro              | idnumber | visible |
      | label    | C1     | 1       | {courseprofesores} | 1        | 1       |

  @javascript
  Scenario: See the course professors filtered text
    Given the "courseprofesores" filter is "on"
    When I am on the "Course 1" "Course" page logged in as "student1"
    Then I should see "Teacher 1"
