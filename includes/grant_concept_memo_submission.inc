<?php

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function anh_maestro_grant_concept_memo_submission_concept_note_review_form(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  // Get Webform submission by process ID
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  if (!$sid) {
    throw new NotFoundHttpException();
  }

  $form['#attributes']['class'][] = 'grant-form';

  $webformSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($sid);
  $webformSubmissionData = $webformSubmission->getData();
  if (isset($webformSubmissionData["parent_node"]) && !is_null($webformSubmissionData["parent_node"]) && !empty($webformSubmissionData["parent_node"]) && $nodeGrantId = $webformSubmissionData["parent_node"]) {
    $node = Node::load($nodeGrantId);
    $nodeTitle = $node->label();
  }

  $view_builder = \Drupal::entityTypeManager()
    ->getViewBuilder('webform_submission');

  $form['grant_submission_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['grant-submission-container']],
    '#weight'     => -10,
  ];
  $form['grant_submission_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['grant-submission-title']],
    '#value'      => "Concept Note Review",
  ];
  $form['grant_submission_container']["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['grant-submission-description']],
    '#value'      => isset($nodeTitle) ? "Welcome and thank you for taking part in the review of $nodeTitle grant submission. The review shouldn’t take that long, but before you begin please read through their submission below." : "Welcome and thank you for taking part in the review of grant submission. The review shouldn’t take that long, but before you begin please read through their submission below.",
  ];

  $form['grant_submission_results_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['grant-submission-results-container']],
    '#weight'     => -10,
  ];
  $form['grant_submission_results_container']["webform_submission"] = $view_builder->view($webformSubmission, "table");

  $reviewsCommentsBuild = \Drupal::service('anh_maestro.reviews_comments_build');
  $comments = $reviewsCommentsBuild->getGrantPeerReviewsDetails($processID);
  $commentsResult = [];

  foreach ($comments as $commentKey => $comment) {
    $commentArray = (array) $comment;
    foreach ($commentArray as $key => $value) {
      if (in_array($key, ["field_development_relevance_value", "field_collaborative_approaches_value", "field_innovation_value", "field_scientific_excellence_value", "field_translational_value_value"])) {
        $value = $reviewsCommentsBuild->getOptionsLabel($value);
      }

      $commentsResult[$commentKey][$key] = $value;
    }
  }

  if (isset($commentsResult) && !empty($commentsResult)) {
    $form['grant_submission_reviews_container'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => ['grant-submission-container reviews-container']],
      '#weight'     => -10,
    ];
    $form['grant_submission_reviews_container']["title"] = [
      '#type'       => 'html_tag',
      '#tag'        => 'h2',
      '#attributes' => ['class' => ['grant-submission-title']],
      '#value'      => "Grant Peer Reviews",
    ];

    $form['grant_submission_reviews_container']["result"]["comments"] = [
      '#theme' => 'anh_maestro_grant_peer_reviews',
      '#comments' => $commentsResult,
    ];
  }

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
    '#weight' => -10,
    '#value' => t('Reject'),
  ];

  $form['actions']['submit']['#value'] = t('Accept');
}

function anh_maestro_grant_concept_memo_submission_concept_note_review_form_submit(&$form, &$form_state, $queueID = 0) {
  $currentUserID = \Drupal::currentUser()->id();
  $queueID = intval($form_state->getValue('maestro_queue_id'));

  $triggeringElement = $form_state->getTriggeringElement();
  if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
    \Drupal::messenger()
      ->addMessage("You have accepted request successfully", 'status');
    MaestroEngine::completeTask($queueID, $currentUserID);
  }
  else {
    \Drupal::messenger()->addStatus('You have rejected request successfully');

    MaestroEngine::completeTask($queueID, $currentUserID);
    MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
  }
}

/**
 * Grant Submission Full Proposal
 *
 * @param $form
 * @param $queueID
 * @param $obj
 *
 */
function anh_maestro_grant_concept_memo_submission_full_proposal_form_staff(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);

  $form['user1'] = array(
    '#title'       => t('Choose User 1 for Grant - Full Proposal Peer review'),
    '#description' => 'Please select 1 user for Grant - Full Proposal Peer review',
    '#required'    => TRUE,
    '#type' => 'entity_autocomplete',
    '#target_type' => 'user',
    '#selection_handler' => 'views',
    '#selection_settings' => [
      'view' => [
        'view_name' => 'users',
        'display_name' => 'users_entity_reference',
        'arguments' => []
      ],
      'match_operator' => 'CONTAINS'
    ],
  );

  $form['user2'] = [
    '#type'        => 'entity_autocomplete',
    '#title'       => t('Choose User 2 for Grant - Full Proposal Peer review'),
    '#description' => 'Please select 2 user for Grant - Full Proposal Peer review',
    '#target_type' => 'user',
    '#required'    => TRUE,
    '#selection_handler' => 'views',
    '#selection_settings' => [
      'view' => [
        'view_name' => 'users',
        'display_name' => 'users_entity_reference',
        'arguments' => []
      ],
      'match_operator' => 'CONTAINS'
    ],
  ];

  $form['processID'] = [
    '#type'          => 'hidden',
    '#title'         => 'the queue ID in the event we need it in later processing',
    '#default_value' => $processID,
    '#description'   => ('queueID'),
  ];
}

/**
 * Grant Submission Full Proposal submit
 *
 * @param $form
 * @param $form_state
 * @param int $queueID
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function anh_maestro_grant_concept_memo_submission_full_proposal_form_staff_submit(&$form, &$form_state, $queueID = 0) {
  $currentUserID = \Drupal::currentUser()->id();
  $processID = intval($form_state->getValue('processID'));
  $queueID = intval($form_state->getValue('maestro_queue_id'));
  $assignedUserIds = implode("/", [
    $form_state->getValue('user1'),
    $form_state->getValue('user2'),
  ]);

  $values = [
    'process_id' => $processID,
    'variable_name' => "peer_review_stage_uids",
    'variable_value' => $assignedUserIds,
  ];
  $newVar = \Drupal::entityTypeManager()->getStorage('maestro_process_variables')->create($values);
  $newVar->save();

  \Drupal::messenger()->addMessage("You have accepted request successfully", 'status');
  MaestroEngine::completeTask($queueID, $currentUserID);
}

/**
 * Grant Submission Success
 *
 * @param $form
 * @param $queueID
 * @param $obj
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function anh_maestro_grant_concept_memo_submission_success_form(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
  $grantSubmissionFullProposalSID = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'grant_ipe_full_proposal_review');
  $webformGrantFullProposalSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($grantSubmissionFullProposalSID);

  $form['grant_submission_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['grant-submission-container']],
    '#weight'     => -10,
  ];
  $form['grant_submission_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['grant-submission-title']],
    '#value'      => "Grant IPE Full Proposal Review",
  ];
  $form['grant_submission_results_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['grant-submission-results-container']],
    '#weight'     => -10,
  ];

  $form["grantSubmissionIPEFullProposalReview"] = $view_builder->view($webformGrantFullProposalSubmission, "table");

  $form['actions']['submit']['#value'] = t('Accept');

  $form['actions']['reject'] = [
    '#type'  => 'submit',
    '#value' => t('Reject'),
  ];

  $form['processID'] = [
    '#type'          => 'hidden',
    '#title'         => 'the queue ID in the event we need it in later processing',
    '#default_value' => $processID,
    '#description'   => ('queueID'),
  ];
}

/**
 * Grant Submission Full Proposal submit
 *
 * @param $form
 * @param $form_state
 * @param int $queueID
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function anh_maestro_grant_concept_memo_submission_success_form_submit(&$form, &$form_state, $queueID = 0) {
  $currentUserID = \Drupal::currentUser()->id();
  $queueID = intval($form_state->getValue('maestro_queue_id'));
  $processRecord = MaestroEngine::getProcessEntryById($form_state->getValue('processID'));
  $triggeringElement = $form_state->getTriggeringElement();
  $initiatorUid = $processRecord->get("initiator_uid")->value;
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0 && $sid) {
    $webformSubmission = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->load($sid);
    $webformSubmissionData = $webformSubmission->getData();

    if (isset($webformSubmissionData["parent_node"])) {
      $anhTrack = \Drupal::entityTypeManager()->getStorage('anh_track')->create([
        "user" => $initiatorUid,
        "grant" => $webformSubmissionData["parent_node"],
        "submission" => NULL,
      ]);
      $anhTrack->save();

      \Drupal::messenger()->addMessage("You have accepted request successfully", 'status');
      MaestroEngine::completeTask($queueID, $currentUserID);
    }
  }
  else {
    \Drupal::messenger()->addStatus('You have rejected request successfully');

    MaestroEngine::completeTask($queueID, $currentUserID);
    MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
  }
}

/**
 * Grant Submission Full Proposal
 *
 * @param $form
 * @param $queueID
 * @param $obj
 *
 */
function anh_maestro_grant_peer_review_user_assigment(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);

  $form['user'] = array(
    '#title'       => t('Choose User to assigning for Grant - Peer Review'),
    '#description' => '',
    '#required'    => TRUE,
    '#type' => 'entity_autocomplete',
    '#target_type' => 'user',
    '#selection_handler' => 'views',
    '#selection_settings' => [
      'view' => [
        'view_name' => 'users',
        'display_name' => 'users_entity_reference',
        'arguments' => []
      ],
      'match_operator' => 'CONTAINS'
    ],
  );

  $form['processID'] = [
    '#type'          => 'hidden',
    '#title'         => 'the queue ID in the event we need it in later processing',
    '#default_value' => $processID,
    '#description'   => ('queueID'),
  ];
}

/**
 * Grant Submission Full Proposal submit
 *
 * @param $form
 * @param $form_state
 * @param int $queueID
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function anh_maestro_grant_peer_review_user_assigment_submit(&$form, &$form_state, $queueID = 0) {
  $currentUserID = \Drupal::currentUser()->id();
  $processID = intval($form_state->getValue('processID'));
  $queueID = intval($form_state->getValue('maestro_queue_id'));

  $values = [
    'process_id' => $processID,
    'variable_name' => "grant_peer_review_user_assigment",
    'variable_value' => $form_state->getValue('user'),
  ];
  $newVar = \Drupal::entityTypeManager()->getStorage('maestro_process_variables')->create($values);
  $newVar->save();

  \Drupal::messenger()->addMessage("You have accepted request successfully", 'status');
  MaestroEngine::completeTask($queueID, $currentUserID);
}
