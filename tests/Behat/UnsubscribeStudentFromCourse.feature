Feature: Unsubscribing students from courses

  Scenario: Unsubscribing non-existing student from course
    Given course "c1" exists
    When student "non-existing" unsubscribes from course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to unsubscribe student with id "non-existing" from course with id "c1" because a student with that id does not exist
      """
    And no events should be appended

  Scenario: Unsubscribing student from non-existing course
    Given course "c1" exists
    And student "s1" is registered
    When student "s1" unsubscribes from course "non-existing"
    Then the command should be rejected with the following message:
      """
      Failed to unsubscribe student with id "s1" from course with id "non-existing" because a course with that id does not exist
      """
    And no events should be appended

  Scenario: Unsubscribing student from course that the student is not subscribed to
    Given courses "c1,c2" exists
    And student "s1" is registered
    And student "s1" is subscribed to course "c1"
    When student "s1" unsubscribes from course "c2"
    Then the command should be rejected with the following message:
      """
      Failed to unsubscribe student with id "s1" from course with id "c2" because that student is not subscribed to this course
      """
    And no events should be appended

  Scenario: Unsubscribing student from course
    Given courses "c1,c2,c3,c4" exist
    And students "s1,s2,s3,s4" are registered
    And student "s1" is subscribed to course "c1"
    And student "s2" is subscribed to course "c1"
    And student "s2" unsubscribes from course "c1"
    Then the following events should be read:
      | Type                        | Tags                        |
      | "CourseCreated"             | ["course:c1"]               |
      | "StudentRegistered"         | ["student:s2"]              |
      | "StudentSubscribedToCourse" | ["course:c1", "student:s1"] |
      | "StudentSubscribedToCourse" | ["course:c1", "student:s2"] |
    And the command should pass without errors
    And the following event should be appended:
      | Type                            | Data                                  | Tags                        |
      | "StudentUnsubscribedFromCourse" | {"courseId": "c1", "studentId": "s2"} | ["course:c1", "student:s2"] |
