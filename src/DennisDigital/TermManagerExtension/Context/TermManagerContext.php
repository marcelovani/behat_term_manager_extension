<?php

namespace DennisDigital\TermManagerExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Class TermManagerContext
 *
 * @package Behat\TermManagerExtension\Context
 */
class TermManagerContext implements SnippetAcceptingContext
{

  private $drupalContext;

  /**
   * @BeforeScenario
   *
   * @param BeforeScenarioScope $scope
   */
  public function before(BeforeScenarioScope $scope) {
    // Get the environment.
    $environment = $scope->getEnvironment();

    // Get all the contexts we need.
    //$this->BDDCommonContext = $environment->getContext('Behat\BDDCommonExtension\Context\BDDCommonContext');
    //$this->MinkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
    $this->drupalContext = $environment->getContext('Drupal\DrupalExtension\Context\DrupalContext');
  }

  public function __construct() {

    // Bootstrap drupal.
    //$this->getDriver()->getCore()->bootstrap();
  }

  /**
   * @Given I create a taxonomy tree for testing term manager
   */
  public function iCreateATaxonomyTreeForTestingTermManager()
  {
    $this->drupalContext->getDriver('drupal')->getCore()->bootstrap();
    //print_r(get_class_methods($this->drupalContext->getDriver()));

    dennis_term_manager_tests_create();
  }

  /**
   * @When term manager processes :arg1
   */
  public function termManagerProcesses($arg1)
  {
    //throw new PendingException();
  }

  /**
   * @Then the term manager resulting tree should match :arg1
   */
  public function theTermManagerResultingTreeShouldMatch($arg1)
  {
    //throw new PendingException();
  }

  /**
   * @When term manager processes dupe actions
   */
  public function termManagerProcessesDupeActions()
  {
    //throw new PendingException();
  }

  /**
   * @Then I clean up the testing terms for term manager
   */
  public function iCleanUpTheTestingTermsForTermManager()
  {
    //throw new PendingException();
  }
}
