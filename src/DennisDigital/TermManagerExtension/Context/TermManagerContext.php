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
   * Batch processing.
   *
   * @param $file
   */
  private function batch($file) {
    $this->drupalContext->getDriver('drupal')->getCore()->bootstrap();

    // Initial cleanup of taxonomy tree.
    //@todo see log. dont use _ function
    _dennis_term_manager_cleanup();

    $destination = _dennis_term_manager_get_files_folder();

    // Copy the CSV file into files folder.
    $file = _dennis_term_manager_file_copy($file, $destination);

    // Process the file.
    $batch = _dennis_term_manager_batch_init($file);

    // Tell Term Manager that the batch is being created by Behat extension.
    // Term Manager implements hook_batch_alter() to set progressive to FALSE.
    $batch['behat_extension'] = TRUE;

    // Process batch to queue up operations.
    batch_set($batch);
    batch_process();
    $this->batchCleanup($batch);

    // Process queue.
    drupal_cron_run();

  }

  /**
   * Cleans batch table.
   *
   * @param $batch
   */
  private function batchCleanup($batch) {
    db_delete('batch')
      ->condition('bid', $batch['id'])
      ->execute();
  }

  /**
   * @Given I create a taxonomy tree for testing term manager
   */
  public function iCreateATaxonomyTreeForTestingTermManager()
  {
    $csv = 'test_create_run.csv';
    $file = realpath(dirname(__FILE__) . '/../Resources/' . $csv);

    $this->batch($file);

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
