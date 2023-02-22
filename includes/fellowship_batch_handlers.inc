<?php

use Drupal\maestro\Engine\MaestroEngine;

function anh_maestro_fellowship_reference_request_batch_handler($processID, $queueID) {
  $allEntityIdentifiers = MaestroEngine::getAllEntityIdentifiersForProcess($processID);

  $referenceRequests = [
    "fellowship_reference_request_home",
    "fellowship_reference_request_host",
    "fellowship_reference_request_other",
  ];

  foreach ($referenceRequests as $key => $referenceRequest) {
    if (isset($allEntityIdentifiers[$referenceRequest])) {
      unset($referenceRequests[$key]);
    }
  }

  $query = \Drupal::entityTypeManager()->getStorage('maestro_queue')->getQuery();
  $query->condition('archived', 1);
  $query->condition('process_id', $processID);
  $query->notExists('completed');
  $entity_ids = $query->execute();

  foreach ($entity_ids as $queueID) {
    $queueRecord = \Drupal::entityTypeManager()
      ->getStorage('maestro_queue')
      ->load($queueID);

    $tasId = $queueRecord->task_id->getString();

    if (in_array($tasId, $referenceRequests)) {
      unset($referenceRequests[array_search($tasId, $referenceRequests)]);
    }
  }

  if (empty($referenceRequests)) {
    return TRUE;
  }
}
