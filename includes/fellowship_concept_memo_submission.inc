<?php

use Drupal\comment\Entity\Comment;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The manager approval form used in the workflow "Fellowship Submission".
 *
 * @param unknown $form
 * @param int $queueID
 *
 * @param $obj
 *
 * @return \unknown
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function anh_maestro_fellowship_concept_memo_submission_staff_approving_form(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  // Get Webform submission by process ID
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  $form['#attributes']['class'][] = 'fellowship-form';

  if (!$sid) {
    throw new NotFoundHttpException();
  }

  $webformSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($sid);
  $view_builder = \Drupal::entityTypeManager()
    ->getViewBuilder('webform_submission');

  $form['fellowship_submission_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['container', 'step-container']],
    '#weight'     => -10,
  ];
  $form['fellowship_submission_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['title']],
    '#value'      => "Fellowship Concept Note Review",
  ];

  $form['fellowship_submission_results_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['fellowship-submission-results-container']],
    '#weight'     => -10,
  ];
  $form['fellowship_submission_results_container']["webform_submission"] = $view_builder->view($webformSubmission, "default");

  $form['sid'] = [
    '#type'          => 'hidden',
    '#title'         => 'the queue ID in the event we need it in later processing',
    '#default_value' => $sid,
    '#description'   => ('queueID'),
  ];

  $form['processID'] = [
    '#type'          => 'hidden',
    '#title'         => 'the queue ID in the event we need it in later processing',
    '#default_value' => $processID,
    '#description'   => ('queueID'),
  ];

  $form['actions']['reject'] = [
    '#type'  => 'submit',
    '#value' => t('Reject Request'),
  ];

  $form['fellowship_submission_review_container']['submit'] = [
    '#type'       => 'submit',
    '#attributes' => ['class' => ['confirm__submission']],
    '#value'      => t('Confirm'),
  ];

  unset($form['actions']['submit']);

  return $form;
}

/**
 * This is the submit handler used in "Fellowship Submission".
 *
 * @param array $form
 * @param FormStateInterface $form_state
 * @param int $queueID
 *
 * @return void
 */
function anh_maestro_fellowship_concept_memo_submission_staff_approving_form_submit(&$form, &$form_state, $queueID = 0) {
  $currentUserID = \Drupal::currentUser()->id();
  $queueID = intval($form_state->getValue('maestro_queue_id'));
  $triggeringElement = $form_state->getTriggeringElement();

  if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
    \Drupal::messenger()->addMessage("You have accepted request successfully", 'status');
    MaestroEngine::completeTask($queueID, $currentUserID);
  }
  else {
    \Drupal::messenger()->addStatus('You have rejected request successfully');

    MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
    MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
  }
}

/**
 * The manager approval form used in the workflow "Fellowship Submission".
 *
 * @param unknown $form
 * @param int $queueID
 *
 * @param $obj
 *
 * @return \unknown
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function anh_maestro_fellowship_final_review_form(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  // Get Webform submission by process ID
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'fellowship_external_review');
  $fullProposalSid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'fellowship_full_proposal_submission');

  if (!$sid || !$fullProposalSid) {
    throw new NotFoundHttpException();
  }

  $webformSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($sid);

  $fullProposalWebformSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($fullProposalSid);

  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $form['fellowship_submission_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['container', 'step-container']],
    '#weight'     => -10,
  ];
  $form['fellowship_submission_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['title']],
    '#value'      => "Fellowship Final Review",
  ];

  $form['fellowship_submission_results_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['fellowship-submission-results-container']],
    '#weight'     => -10,
  ];

  $form['fellowship_submission_results_container']["full_proposal_container"] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['container']],
    '#weight'     => -10,
  ];
  $form['fellowship_submission_results_container']["full_proposal_container"]["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h3',
    '#attributes' => ['class' => ['title']],
    '#value'      => "Fellowship Full Proposal Submission",
  ];
  $form['fellowship_submission_results_container']["full_proposal_container"]["webform_submission"] = $view_builder->view($fullProposalWebformSubmission, "default");

  $form['fellowship_submission_results_container']["external_review_container"] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['container']],
    '#weight'     => -10,
  ];
  $form['fellowship_submission_results_container']["external_review_container"]["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h3',
    '#attributes' => ['class' => ['title']],
    '#value'      => "Fellowship External Review Submission",
  ];
  $form['fellowship_submission_results_container']["external_review_container"]["webform_submission"] = $view_builder->view($webformSubmission, "default");

  $form['sid'] = [
    '#type'          => 'hidden',
    '#title'         => 'the queue ID in the event we need it in later processing',
    '#default_value' => $sid,
    '#description'   => ('queueID'),
  ];

  $form['processID'] = [
    '#type'          => 'hidden',
    '#title'         => 'the queue ID in the event we need it in later processing',
    '#default_value' => $processID,
    '#description'   => ('queueID'),
  ];

  $form['actions']['reject'] = [
    '#type'  => 'submit',
    '#value' => t('Reject Request'),
  ];

  $form['fellowship_submission_review_container']['submit'] = [
    '#type'       => 'submit',
    '#attributes' => ['class' => ['confirm__submission']],
    '#value'      => t('Confirm'),
  ];

  unset($form['actions']['submit']);

  return $form;
}

/**
 * This is the submit handler used in "Fellowship Submission".
 *
 * @param array $form
 * @param FormStateInterface $form_state
 * @param int $queueID
 *
 * @return void
 */
function anh_maestro_fellowship_final_review_form_submit(&$form, &$form_state, $queueID = 0) {
  $currentUserID = \Drupal::currentUser()->id();
  $queueID = intval($form_state->getValue('maestro_queue_id'));
  $processRecord = MaestroEngine::getProcessEntryById($form_state->getValue('processID'));
  $triggeringElement = $form_state->getTriggeringElement();
  $initiatorUid = $processRecord->get("initiator_uid")->value;
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
    $webformSubmission = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->load($sid);
    $webformSubmissionData = $webformSubmission->getData();

    if (isset($webformSubmissionData["fellowship"])) {
      $invitationService = \Drupal::service('anh_maestro.anh_grant_additional_documents_invitation');
      $result = $invitationService->setData($initiatorUid, $webformSubmissionData["fellowship"]);

      $account = User::load($initiatorUid);
      $name = $account->get("field_user_first_name")->value . " " . $account->get("field_user_last_name")->value;

      $url = Url::fromUserInput('/fellowship-additional-documents', ['absolute' => TRUE, 'attributes' => ['target' => '_blank'], 'query' => ["access" => $invitationService->getToken()]]);
      $link = Link::fromTextAndUrl("page", $url)->toString();

      $invitationService->sendMail("ANH Fellowship Submission", "Hello $name. Congratulations, you have success with the fellowship. Please send your additional documents for ANH Fellowship on the $link");

      $anhTrack = \Drupal::entityTypeManager()->getStorage('anh_track')->create([
        "user" => $initiatorUid,
        "grant" => $webformSubmissionData["fellowship"],
        "submission" => NULL,
      ]);
      $anhTrack->save();

      \Drupal::messenger()->addMessage("You have accepted request successfully", 'status');
      MaestroEngine::completeTask($queueID, $currentUserID);

      $form_state->setRedirect('<front>');
    }
  }
  else {
    \Drupal::messenger()->addStatus('You have rejected request successfully');

    MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
    MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
  }
}
