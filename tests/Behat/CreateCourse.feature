Feature: Creating courses

  Scenario: Creating a new course with an ID that already exists
    Given course "c1" exists
    When a new course is created with id "c1"
    Then the command should be rejected with the following message:
      """
      Failed to create course with id "c1" because a course with that id already exists
      """
    And no events should be appended

  Scenario: Creating a new course
    Given course "c1" exists
    When a new course is created with id "c2", title "course 02" and capacity of 10
    Then no events should be read
    And the command should pass without errors
    And the following event should be appended:
      | Type            | Data                                                                    | Domain Ids         |
      | "CourseCreated" | {"courseId": "c2", "initialCapacity": "10", "courseTitle": "course 02"} | [{"course": "c2"}] |