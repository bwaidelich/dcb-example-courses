Feature: Registering students

  Scenario: Registering a new student with an ID that already exists
    Given student "s1" is registered
    When a new student is registered with id "s1"
    Then the command should be rejected with the following message:
      """
      Failed to register student with id "s1" because a student with that id already exists
      """

  Scenario: Registering a new student
    Given student "s1" is registered
    When a new student is registered with id "s2"
    Then no events should be read
#    And the command should pass without errors
#    And the following event should be appended:
#      | Type                | Data                | Tags           |
#      | "StudentRegistered" | {"studentId": "s2"} | ["student:s2"] |