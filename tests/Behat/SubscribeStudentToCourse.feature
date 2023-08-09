Feature: Subscribing students to courses

  Scenario: Subscribing non-existing student to course
    Given course "c1" exists
    When student "non-existing" subscribes to course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "non-existing" to course with id "c1" because a student with that id does not exist
      """
    And no events should be appended

  Scenario: Subscribing student to non-existing course
    Given course "c1" exists
    And student "s1" is registered
    When student "s1" subscribes to course "non-existing"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "s1" to course with id "non-existing" because a course with that id does not exist
      """
    And no events should be appended

  Scenario: Subscribing student to course that the student is already subscribed to
    Given course "c1" exists
    And student "s1" is registered
    And student "s1" is subscribed to course "c1"
    When student "s1" subscribes to course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "s1" to course with id "c1" because that student is already subscribed to this course
      """
    And no events should be appended

  Scenario: Subscribing student to course that has already reached its capacity
    Given course "c1" exists with a capacity of 3
    And students "s1,s2,s3,s4" are registered
    And student "s1" is subscribed to course "c1"
    And student "s2" is subscribed to course "c1"
    And student "s3" is subscribed to course "c1"
    And student "s4" subscribes to course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "s4" to course with id "c1" because the course's capacity of 3 is reached
      """
    And no events should be appended

  Scenario: Subscribing student to 11 courses
    Given courses "c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11" exist
    And student "s1" is registered
    And student "s1" is subscribed to courses "c1,c2,c3,c4,c5,c6,c7,c8,c9,c10"
    When student "s1" subscribes to course "c11"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "s1" to course with id "c11" because that student is already subscribed the maximum of 10 courses
      """
    And no events should be appended

  Scenario: Subscribing student to 10 courses
    Given courses "c1,c2,c3,c4,c5,c6,c7,c8,c9,c10" exist
    And student "s1" is registered
    And student "s1" is subscribed to courses "c1,c2,c3,c4,c5,c6,c7,c8,c9"
    When student "s1" subscribes to course "c10"
    Then the following events should be read:
      | Type                        | Tags                        |
      | "CourseCreated"             | ["course:c10"]              |
      | "StudentRegistered"         | ["student:s1"]              |
      | "StudentSubscribedToCourse" | ["course:c1", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c2", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c3", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c4", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c5", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c6", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c7", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c8", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c9", "student:s1"] |
    Then the command should pass without errors
    And the following event should be appended:
      | Type                        | Data                                   | Tags                         |
      | "StudentSubscribedToCourse" | {"courseId": "c10", "studentId": "s1"} | ["course:c10", "student:s1"] |