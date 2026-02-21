Feature: Defining courses

  Scenario: Defining a new course with an ID that already exists
    Given course "c1" exists
    When a new course is defined with id "c1"
    Then the command should be rejected with the following message:
      """
      Constraint "notCourseExists" failed
      """
    And no events should be appended

  Scenario: Defining a new course
    Given course "c1" exists
    When a new course is defined with id "c2", title "course 02" and capacity of 10
    Then no events should be read
    And the command should pass without errors
    And the following event should be appended:
      | Type            | Data                                                                    | Tags          |
      | "CourseDefined" | {"courseId": "c2", "initialCapacity": "10", "courseTitle": "course 02"} | ["course:c2"] |