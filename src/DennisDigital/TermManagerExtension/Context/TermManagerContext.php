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
    $this->drupalContext->getDriver('drupal')->getCore()->bootstrap();

    // Make sure term manager is enabled.
    variable_set('dennis_term_manager_enabled', 1);
  }

  /**
   * Batch processing.
   *
   * @param $file
   */
  private function batch($file) {
    // Initial cleanup of taxonomy tree and queue.
    $this->taxonomyCleanup();
    $this->queueCleanup('dennis_term_manager_queue');

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
    //@todo this is not working
    //drupal_cron_run();

    $this->processQueue($file);

  }

  /**
   * Processes the queue.
   *
   * @param $file
   */
  private function processQueue($file) {
    // Process the queue.
    foreach (dennis_term_manager_cron_queue_info() as $queue_name => $info) {
      $function = $info['worker callback'];
      if ($queue = \DrupalQueue::get($queue_name)) {
        while ($item = $queue->claimItem()) {
          $function($item->data);
          $queue->deleteItem($item);
        }
      }
    }

    $date = date('Y-m-d_H-i-s', REQUEST_TIME);
    $errors_file = preg_replace("/(.*)[.](.*)/", "$1-$date-errors.$2", $file->uri);
    $dry_run_file = preg_replace("/(.*)[.](.*)/", "$1-$date-dry_run.$2", $file->uri);
    $report_file = preg_replace("/(.*)[.](.*)/", "$1-$date-report.txt", $file->uri);

    // Test that file with errors doesn't exist.
    if (file_exists($errors_file)) {
      throw new Exception(t('There were errors during execution, see !file_name for more details', array(
        '!file_name' => $errors_file,
      )));
    }
  }

  /**
   * Cleans queue table.
   *
   * @param $batch
   */
  private function queueCleanup($name) {
    db_delete('queue')
      ->condition('name', $name)
      ->execute();
  }

  /**
   * Cleans batch table.
   *
   * @param $batch
   */
  private function batchCleanup($batch) {
    if (!empty($batch['id'])) {
      db_delete('batch')
        ->condition('bid', $batch['id'])
        ->execute();
    }
  }

  /**
   * Helper to clean up terms created during tests.
   */
  private function taxonomyCleanup() {
    // Delete terms created during tests.
    $term = taxonomy_get_term_by_name('Temp', 'category');
    if ($term = reset($term)) {
      taxonomy_term_delete($term->tid);
    }

    $term = taxonomy_get_term_by_name('TM-Fruits', 'category');
    if ($term = reset($term)) {
      taxonomy_term_delete($term->tid);
    }

    $term = taxonomy_get_term_by_name('TM-Fruits2', 'category');
    if ($term = reset($term)) {
      taxonomy_term_delete($term->tid);
    }
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
