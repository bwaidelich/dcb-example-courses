Feature:

  Scenario:
    Given student "s1" is registered
    When a new student is registered with id "s1"
    Then the command should be rejected with the following message:
      """
      Failed to register student with id "s1" because a student with that id already exists
      """

  Scenario:
    Given course "c1" exists
    Given student "non-existing" subscribes to course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "non-existing" to course with id "c1" because a student with that id does not exist
      """

  Scenario:
    Given course "c1" exists
    And student "s1" is registered
    And student "s1" is subscribed to course "c1"
    When student "s1" subscribes to course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "s1" to course with id "c1" because that student is already subscribed to this course
      """

  Scenario:
    Given course "c1" exists
    Given student "non-existing" unsubscribes from course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to unsubscribe student with id "non-existing" from course with id "c1" because a student with that id does not exist
      """

  Scenario:
    Given course "c1" exists
    And student "s1" is registered
    When student "s1" unsubscribes from course "c1"
    Then the command should be rejected with the following message:
      """
      Failed to unsubscribe student with id "s1" from course with id "c1" because that student is not subscribed to this course
      """

  Scenario:
    Given courses "c1,c2,c3,c4,c5,c6,c7,c8,c9,c10" exist
    And student "s1" is registered
    And student "s1" is subscribed to courses "c1,c2,c3,c4,c5,c6,c7,c8,c9"
    When student "s1" subscribes to course "c10"
    Then the command should pass without errors

  Scenario:
    Given courses "c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11" exist
    And student "s1" is registered
    And student "s1" is subscribed to courses "c1,c2,c3,c4,c5,c6,c7,c8,c9,c10"
    When student "s1" subscribes to course "c11"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "s1" to course with id "c11" because that student is already subscribed the maximum of 10 courses
      """