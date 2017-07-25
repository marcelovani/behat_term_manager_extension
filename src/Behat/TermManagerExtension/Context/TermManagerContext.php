<?php

namespace DennisDigital\TermManagerExtension\Context;

use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Class TermManagerContext
 *
 * @package Behat\TermManagerExtension\Context
 */
class TermManagerContext extends RawMinkContext implements TermManagerInterface
{
  public function __construct() {
    parent::__construct;

    // Bootstrap drupal.
    $this->getDriver()->getCore()->bootstrap();
  }

  /**
   * @Given I create a taxonomy tree for testing term manager
   */
  public function iCreateATaxonomyTreeForTestingTermManager()
  {
    dennis_term_manager_tests_create();
  }

  /**
   * @When term manager processes :arg1
   */
  public function termManagerProcesses($arg1)
  {
    throw new PendingException();
  }

  /**
   * @Then the term manager resulting tree should match :arg1
   */
  public function theTermManagerResultingTreeShouldMatch($arg1)
  {
    throw new PendingException();
  }

  /**
   * @When term manager processes dupe actions
   */
  public function termManagerProcessesDupeActions()
  {
    throw new PendingException();
  }

  /**
   * @Then I clean up the testing terms for term manager
   */
  public function iCleanUpTheTestingTermsForTermManager()
  {
    throw new PendingException();
  }
}
