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

    // Initial cleanup of taxonomy tree and queue.
    $this->iCleanUpTheTestingTermsForTermManager();
  }

  /**
   * Batch processing.
   *
   * @param $file
   */
  private function batch($file) {
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
   * @Given I create a taxonomy tree using :csv
   */
  public function iCreateATaxonomyTreeUsing($csv)
  {
    $file = realpath(dirname(__FILE__) . '/../Resources/' . $csv);

    $this->batch($file);

  }

  /**
   * @Then I check that the taxonomy tree matches the contents of :csv
   */
  public function iCheckThatTheTaxonomyTreeMatchesTheContentsOf($csv)
  {
    $csv = realpath(dirname(__FILE__) . '/../Resources/' . $csv);

    // Export CSV of taxonomy tree.
    $columns = dennis_term_manager_default_columns();

    // Need to exclude some columns because they will be different on each site.
    $exclude = array('path', 'tid', 'target_tid');
    foreach ($exclude as $item) {
      unset ($columns[array_search($item, $columns)]);
    }
    dennis_term_manager_export_terms(',', array('Category'), $columns, DENNIS_TERM_MANAGER_DESTINATION_FILE);

    $destination = _dennis_term_manager_get_files_folder();
    $exported_tree = drupal_realpath($destination) . '/taxonomy_export.csv';

    // Compare exported CSV against the CSV saved on the repo.
    // Pass tree must be contained inside the exported tree in order for the test to pass.
    $this->diff($csv, $exported_tree);
  }

  /**
   * @When term manager processes :csv
   */
  public function termManagerProcesses($csv)
  {
    $file = realpath(dirname(__FILE__) . '/../Resources/' . $csv);

    $this->batch($file);
  }

  /**
   * Runs actions with duplicated terms, using the tid column.
   * This function will find duplicated term names and create a CSV file with actions to merge them
   * i.e. Raspberry-0 will be merged to Raspberry.
   *
   * @When term manager processes dupe actions
   */
  public function termManagerProcessesDupeActions()
  {
    $test_actions = array('merge', 'move parent');

    // Updated duplicated names, by removing the '-0' suffix.
    // This way we will end up with the same term name more than once. Useful to test the actions using tids.
    if (!$result = db_query("UPDATE {taxonomy_term_data} SET name = REPLACE(name, '-0', '') WHERE name like 'TM-%-0'")) {
      throw new Exception(t('Could not find/rename any term.'));
    }

    // Export tree.
    dennis_term_manager_export_terms(',', array('Category'), array(), DENNIS_TERM_MANAGER_DESTINATION_FILE);

    // Load the exported tree.
    $destination = _dennis_term_manager_get_files_folder();
    $exported_tree = drupal_realpath($destination) . '/taxonomy_export.csv';

    // Loop the CSV and add "merge" action to each duplicated term.
    $processed = array();
    $actions = array();
    if (($handle = fopen($exported_tree, "r")) !== FALSE) {
      $delimiter = _dennis_term_manager_detect_delimiter(file_get_contents($exported_tree));
      $heading_row = fgetcsv($handle, 1000, $delimiter);
      $columns = array_flip($heading_row);
      $vocabulary_name_column = $columns['vocabulary_name'];
      $name_column = $columns['term_name'];
      $tid_column = $columns['tid'];
      $target_tid_column = $columns['target_tid'];
      $target_term_name_column = $columns['target_term_name'];
      $target_vocabulary_name_column = $columns['target_vocabulary_name'];
      $action_column = $columns['action'];
      $term_child_count_column = $columns['term_child_count'];

      $row = 0;
      while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
        $vocabulary_name = $data[$vocabulary_name_column];
        $term_name = $data[$name_column];
        $tid = $data[$tid_column];

        if (!isset($processed[$term_name])) {
          // Store tid.
          $processed[$term_name] = $tid;
        }
        else {
          // Create action for duplicated term.
          $data[$action_column] = $test_actions[$row];
          $data[$target_tid_column] = $processed[$term_name];
          $data[$target_term_name_column] = $term_name;
          $data[$target_vocabulary_name_column] = $vocabulary_name;
          $actions[] = $data;

          // This counter is used to alternate the actions that are dynamically created.
          $row++;
          if ($row >= count($test_actions)) {
            $row = 0;
          }
        }
      }
    }
    // Sort actions by term_child_count, to make sure we process the children first.
    global $dennis_term_manager_sbk;
    $dennis_term_manager_sbk = $term_child_count_column;
    uasort($actions, '_dennis_term_manager_sbk');

    $out_filename = '/tmp/dupe_actions.csv';

    // Create new csv with actions.
    $out = fopen($out_filename, 'w');
    fputcsv($out, $heading_row, $delimiter, '"');
    foreach ($actions as $action) {
      fputcsv($out, $action, $delimiter, '"');
    }

    // Process file.
    $this->batch($out_filename);
  }

  /**
   * Helper to do a Diff between files.
   */
  private function diff($pass_tree, $exported_tree) {
    if (!file_exists($pass_tree)) {
      throw new Exception(t('!file doesn\'t exist', array(
        '!file' => $pass_tree,
      )));
    }
    $test_content = file_get_contents($pass_tree);

    if (!file_exists($exported_tree)) {
      throw new Exception(t('!file doesn\'t exist', array(
        '!file' => $exported_tree,
      )));
    }
    $tree_content = file_get_contents($exported_tree);

    // Remove heading.
    $test_content_lines = explode("\n", $test_content);
    array_shift($test_content_lines);
    $test_content = implode("\n", $test_content_lines);

    // Check if the pass tree is in the exported tree.
    if (strpos($tree_content, $test_content, 0) === FALSE) {
      // Get the failing line.
      $test_content_cumulative = '';
      foreach ($test_content_lines as $line) {
        $test_content_cumulative .= $line . "\n";
        if (strpos($tree_content, $test_content_cumulative, 0) === FALSE) {
          $failing_line = $line;
          break;
        }
      }
      // Throw exception with failing line.
      throw new Exception(t('Exported tree !file1 doesn\'t match !file2 at row !line', array(
        '!file1' => $exported_tree,
        '!file2' => $pass_tree,
        '!line' => $failing_line,
      )));
    }
  }

  /**
   * @Then I clean up the testing terms for term manager
   */
  public function iCleanUpTheTestingTermsForTermManager()
  {
    $this->taxonomyCleanup();
    $this->queueCleanup('dennis_term_manager_queue');
  }

}
