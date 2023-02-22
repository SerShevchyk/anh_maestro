<?php

use Drupal\maestro\Engine\MaestroEngine;

function anh_maestro_academy_weekly_additional_personal_information_batch_handler($processID, $queueID) {
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  $webformSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($sid);
  $webformSubmissionData = $webformSubmission->getData();

  if (isset($webformSubmissionData["bursary_request"]) && $webformSubmissionData["bursary_request"] == "Yes") {
    return TRUE;
  }
  else {
    MaestroEngine::endProcess($processID);
  }
}
