Feature: Home Page

  Ensure the home page is rendering correctly

  @javascript @smoke
  Scenario: Anonymous user visits the homepage
    Given I am on the homepage
    And save screenshot
