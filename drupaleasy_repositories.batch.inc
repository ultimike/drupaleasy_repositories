<?php

/**
 * @file
 * Batch-related functions for DrupalEasy Repositories module.
 */

/**
 * Calls the correct method responsible for handling a given batch operation.
 *
 * @param int $uid
 *   User ID to update.
 * @param array|\ArrayAccess $context
 *   Batch API context.
 */
function drupaleasy_update_repositories_batch_operation($uid, &$context) {
  $drupaleasy_repositories_batch = \Drupal::service('drupaleasy_repositories.batch');
  return $drupaleasy_repositories_batch->updateRepositoriesBatch($uid, $context);
}

/**
 * This is the "Finished" function for Batch API process.
 *
 * @param bool $success
 *   Did the batch complete successfully?
 * @param array $results
 *   Array of results of batch operations.
 * @param array $operations
 *   Array of operations called.
 */
function drupaleasy_update_all_repositories_finished(bool $success, array $results, array $operations) {
  if ($success) {
    $message = t('Updated repository nodes for @num users.', ['@num' => $results['num']]);
  }
  else {
    $message = t('Finished with an error.');
  }
  \Drupal::messenger()->addMessage($message);
}
