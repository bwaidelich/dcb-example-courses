Feature:

  Scenario:
    Given course "c1" exists
    When a new course is created with id "c1"
    Then the command should be rejected with the following message:
      """
      Failed to create course with id "c1" because a course with that id already exists
      """

  Scenario:
    Given course "non-existing" is renamed to "New Course Title"
    Then the command should be rejected with the following message:
      """
      Failed to rename course with id "non-existing" because a course with that id does not exist
      """

  Scenario:
    Given course "non-existing" capacity is changed to 3
    Then the command should be rejected with the following message:
      """
      Failed to change capacity of course with id "non-existing" to 3 because a course with that id does not exist
      """

  Scenario:
    Given course "c1" exists with the title "Some Course Title"
    When course "c1" is renamed to "Some Course Title"
    Then the command should be rejected with the following message:
      """
      Failed to rename course with id "c1" to "Some Course Title" because this is already the title of this course
      """

  Scenario:
    Given student "s1" is registered
    And student "s1" subscribes to course "non-existing"
    Then the command should be rejected with the following message:
      """
      Failed to subscribe student with id "s1" to course with id "non-existing" because a course with that id does not exist
      """

  Scenario:
    Given student "s1" is registered
    And student "s1" unsubscribes from course "non-existing"
    Then the command should be rejected with the following message:
      """
      Failed to unsubscribe student with id "s1" from course with id "non-existing" because a course with that id does not exist
      """

  Scenario:
    Given course "c1" exists with a capacity of 3
    And students "s1,s2,s3" are registered
    And student "s1" is subscribed to course "c1"
    And student "s2" is subscribed to course "c1"
    And student "s3" subscribes to course "c1"
    Then the command should pass without errors

  Scenario:
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

  Scenario:
    Given course "c1" exists with a capacity of 4
    And students "s1,s2,s3,s4" are registered
    And student "s1" is subscribed to course "c1"
    And student "s2" is subscribed to course "c1"
    And student "s3" is subscribed to course "c1"
    And student "s4" subscribes to course "c1"
    And course "c1" capacity is changed to 3
    Then the command should be rejected with the following message:
      """
      Failed to change capacity of course with id "c1" to 3 because it already has 4 active subscriptions
      """