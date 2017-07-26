@term_manager @seo @content
Feature: Term Manager
  In order to save time managing terms
  As a SEO expert
  I want to upload a CSV file with bulk actions to be run against the taxonomy

  @term_manager_self_test @tm
  Scenario: Check that term manager works as expected.
    Given I create a taxonomy tree using "test_create_run.csv"
    Then I check that the taxonomy tree matches the contents of "test_create_pass.csv"

    When term manager processes "test_actions_run.csv"
    Then I check that the taxonomy tree matches the contents of "test_actions_pass.csv"

    When term manager processes dupe actions
    Then I check that the taxonomy tree matches the contents of "test_dupe_actions_pass.csv"

    Then I clean up the testing terms for term manager


  @api @seo @term_manager
  Scenario: Check that term manager works as expected working with nodes.
    # Initialize taxonomy tree.
    Given I run the drush command "tm-create"
    # Assign terms to few nodes.
    Given I am logged in as a user with the "editor" role
    Given I am on "/admin/content?published=1&type=article&article_type=All&category=All&order=changed&sort=desc"
    Then the response status code should be 200
    And store the result of "~.*?node/(.*?)/edit.*?~" for the element ".view-content-administration table tr:nth-child(1)" as "nid1"
    And store the result of "~.*?node/(.*?)/edit.*?~" for the element ".view-content-administration table tr:nth-child(2)" as "nid2"
    And store the result of "~.*?node/(.*?)/edit.*?~" for the element ".view-content-administration table tr:nth-child(3)" as "nid3"
    And store the result of "~.*?node/(.*?)/edit.*?~" for the element ".view-content-administration table tr:nth-child(4)" as "nid4"
    And store the result of "~.*?node/(.*?)/edit.*?~" for the element ".view-content-administration table tr:nth-child(5)" as "nid5"
    And store the result of "~.*?node/(.*?)/edit.*?~" for the element ".view-content-administration table tr:nth-child(6)" as "nid6"

    # Assign term that will be renamed.
    Given I am on "/node/{match:nid1:1}/edit"
    And I select "Other" from "edit-field-main-purpose-und"
    And I select "TM-Drupal" from "edit-field-category-primary-und"
    And I press the "edit-submit" button
    When the block cache has been cleared
    Then I should see a ".group_tags a" element with the "href" attribute which matches "~/tm-drupal~"

    # Assign term that will be merged.
    Given I am on "/node/{match:nid2:1}/edit"
    And I select "Other" from "edit-field-main-purpose-und"
    And I select "TM-Blackberry-0" from "edit-field-category-primary-und"
    And I press the "edit-submit" button
    Then I should see a ".group_tags a" element with the "href" attribute which matches "~/tm-blackberry-0~"

    # Assign term that will be deleted.
    Given I am on "/node/{match:nid3:1}/edit"
    And I select "Other" from "edit-field-main-purpose-und"
    And I select "TM-Mulberry" from "edit-field-category-primary-und"
    And I press the "edit-submit" button
    Then I should see a ".group_tags a" element with the "href" attribute which matches "~/tm-mulberry~"

    # Assign term that will have parent changed.
    Given I am on "/node/{match:nid4:1}/edit"
    And I select "Other" from "edit-field-main-purpose-und"
    And I select "TM-Pineapple" from "edit-field-category-primary-und"
    And I press the "edit-submit" button
    Then I should see a ".group_tags a" element with the "href" attribute which matches "~/tm-pineapple~"

    # Assign term that will be deleted (Secondary category).
    Given I am on "/node/{match:nid5:1}/edit"
    And I select "Other" from "edit-field-main-purpose-und"
    And I select "TM-Multiple" from "edit-field-category-secondary-und"
    And I press the "edit-submit" button
    Then I should see a ".group_tags a" element with the "href" attribute which matches "~/tm-multiple~"

    # Assign term that will be deduped.
    Given I am on "/node/{match:nid6:1}/edit"
    And I select "Other" from "edit-field-main-purpose-und"
    And I select "TM-Raspberry-0" from "edit-field-category-primary-und"
    And I press the "edit-submit" button
    # Run actions.
    Given I run the drush command "tm-actions"

    # Test term rename.
    Given I am on "/node/{match:nid1:1}"
    Then I should see a ".group_tags a" element with the "href" attribute which matches "~/tm-drupes~"

    # Test term merge.
    Given I am on "/node/{match:nid2:1}"
    When I follow "TM-Blackberry" in the ".group_tags" element
    And the ".breadcrumb li:nth-child(2) a" element should contain "Fruits"
    And the ".breadcrumb li:nth-child(3) a" element should contain "Aggregate"
    And the ".breadcrumb li:nth-child(4)" element should contain "TM-Blackberry"

    # Test term delete.
    Given I am on "/node/{match:nid3:1}"
    Then I should not see the link "TM-Mulberry"

    # Test parent change.
    Given I am on "/node/{match:nid4:1}"
    When I follow "TM-Pineapple" in the ".group_tags" element
    Then the ".breadcrumb li:nth-child(2) a" element should contain "Fruits"
    And the ".breadcrumb li:nth-child(3) a" element should contain "Simple"
    And the ".breadcrumb li:nth-child(4) a" element should contain "Hesperidiums"
    And the ".breadcrumb li:nth-child(5)" element should contain "TM-Pineapple"

    # Assign term that will be deleted (Secondary category).
    Given I am on "/node/{match:nid5:1}"
    Then I should not see the link "TM-Multiple"

    # Run dupe actions.
    Given I run the drush command "tm-dupe-actions"
    # Test dupe actions on node.
    Given I am on "/node/{match:nid6:1}"
    When I follow "TM-Raspberry" in the ".breadcrumb" element
    Then I should be on "/uk/tm-raspberry"
    # Clean up.
    Then I run the drush command "tm-cleanup"
