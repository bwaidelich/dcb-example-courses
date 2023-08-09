Feature: Updating the capacity of a course

  Scenario: Changing capacity of a non-existing course
    Given course "c1" exists
    When course "c2" capacity is changed to 3
    Then the command should be rejected with the following message:
      """
      Failed to change capacity of course with id "c2" to 3 because a course with that id does not exist
      """
    And no events should be appended

  Scenario: Changing capacity of a course to a value that is not different
    Given course "c1" exists with a capacity of 3
    When course "c1" capacity is changed to 3
    Then the command should be rejected with the following message:
      """
      Failed to change capacity of course with id "c1" to 3 because that is already the courses capacity
      """
    And no events should be appended

    # NOTE: The following behavior actually deviates from https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-6-the-aggregate-could-cause-unecessary-complexity.html
    # "the course Capacity, can change at any time to any positive integer different from the current one (even if the number of currently subscribed students is larger than the new value)"
  Scenario: Changing capacity of a course to a value that is lower than the currently active subscriptions
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
    And no events should be appended

  Scenario: Changing capacity of a course to a higher value
    Given course "c1" exists with a capacity of 3
    When course "c1" capacity is changed to 4
    Then the following events should be read:
      | Type            | Tags               |
      | "CourseCreated" | ["course:c1"] |
    And the command should pass without errors
    And the following event should be appended:
      | Type                    | Data                                 | Tags          |
      | "CourseCapacityChanged" | {"courseId": "c1", "newCapacity": 4} | ["course:c1"] |

  Scenario: Changing capacity of a course to a lower value
    Given course "c1" exists with a capacity of 4
    When course "c1" capacity is changed to 3
    Then the following events should be read:
      | Type            | Tags               |
      | "CourseCreated" | ["course:c1"] |
    And the command should pass without errors
    And the following event should be appended:
      | Type                    | Data                                 | Tags          |
      | "CourseCapacityChanged" | {"courseId": "c1", "newCapacity": 3} | ["course:c1"] |