Feature: Renaming courses

  Scenario: Renaming non-existing course
    Given course "c1" exists
    When course "non-existing" is renamed to "New course Title"
    Then the command should be rejected with the following message:
      """
      Failed to rename course with id "non-existing" because a course with that id does not exist
      """
    And no events should be appended

  Scenario: Renaming non-existing course without actually changing the name
    Given course "c1" exists with the title "course 01"
    When course "c1" is renamed to "course 01"
    Then the command should be rejected with the following message:
      """
      Failed to rename course with id "c1" to "course 01" because this is already the title of this course
      """
    And no events should be appended

  Scenario: Renaming course
    Given course "c1" exists with the title "course 01"
    When course "c1" is renamed to "course 01 renamed"
    Then the following events should be read:
      | Type            | Domain Ids         |
      | "CourseCreated" | [{"course": "c1"}] |
    And the command should pass without errors
    And the following event should be appended:
      | Type            | Data                                                      | Domain Ids         |
      | "CourseRenamed" | {"courseId": "c1", "newCourseTitle": "course 01 renamed"} | [{"course": "c1"}] |