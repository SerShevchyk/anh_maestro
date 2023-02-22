<?php

use Drupal\maestro\Engine\MaestroEngine;

function anh_maestro_grant_eligibity_check_batch_handler($processID, $queueID) {
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'grant_eligibity_check');

  $webformSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($sid);
  $webformSubmissionData = $webformSubmission->getData();

  if (isset($webformSubmissionData["eligible"]) && $webformSubmissionData["eligible"] == "Yes") {
    return TRUE;
  }
  else {
    MaestroEngine::endProcess($processID);
  }
}

function anh_maestro_grant_full_proposal_peer_review_validation_batch_handler($processID, $queueID) {
  $tasks = [
    "grant_full_proposal_peer_review",
    "grant_full_proposal_peer_review_2",
  ];

  $query = \Drupal::entityTypeManager()->getStorage('maestro_queue')->getQuery();
  $query->condition('process_id', $processID);
  $query->exists('completed');
  $entity_ids = $query->execute();

  foreach ($entity_ids as $queueID) {
    $queueRecord = \Drupal::entityTypeManager()
      ->getStorage('maestro_queue')
      ->load($queueID);

    $tasId = $queueRecord->task_id->getString();

    if (in_array($tasId, $tasks)) {
      unset($tasks[array_search($tasId, $tasks)]);
    }
  }

  if (empty($tasks)) {
    return TRUE;
  }
}
