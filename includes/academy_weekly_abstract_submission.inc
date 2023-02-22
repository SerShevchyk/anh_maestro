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
 * The manager approval form used in the workflow "Academy Week Abstract Submission Staff Approving".
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
function anh_maestro_academy_weekly_abstract_submission_staff_approve_start_form(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);

  $form['#attributes']['class'][] = 'academy-form';

  $form['#title'] = t('Academy Week Abstract Submission Staff Approving Start');

  $form['abstract_submission_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['abstract-submission-container']],
    '#weight'     => -10,
  ];
  $form['abstract_submission_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['abstract-submission-title']],
    '#value'      => "Academy Week Abstract Review",
  ];

  // Get Webform submission by process ID
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  if ($sid) {
    $webformSubmission = \Drupal::entityTypeManager()->getStorage('webform_submission')->load($sid);
    $webformSubmissionData = $webformSubmission->getData();

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $form["webform_submission"] = $view_builder->view($webformSubmission, "default");

    if ($webformSubmissionData["academy_week"] && !empty($webformSubmissionData["academy_week"]) && $nodeAcademyWeekId = $webformSubmissionData["academy_week"]) {
      $node = Node::load($webformSubmissionData["academy_week"]);
      if ($node) {
        $nodeTitle = $node->label();
      }
    }
  }

  $form['abstract_submission_container']["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['abstract-submission-description']],
    '#value'      => isset($nodeTitle) ? "Welcome and thank you for taking part in the review of $nodeTitle abstract submission. The review shouldn’t take that long, but before you begin please read through their submission below." : "Welcome and thank you for taking part in the review of abstract submission. The review shouldn’t take that long, but before you begin please read through their submission below.",
  ];

  $form['actions']['submit']['#value'] = t('Accept Request');

  $form['actions']['reject'] = array(
    '#type'  => 'submit',
    '#value' => t('Reject Request'),
  );

  return $form;
}

/**
 * This is the submit handler used in "Academy Week Abstract Submission Staff Approving".
 *
 * @param array $form
 * @param FormStateInterface $form_state
 * @param int $queueID
 *
 * @return \Drupal\Core\Form\FormStateInterface
 */
function anh_maestro_academy_weekly_abstract_submission_staff_approve_start_form_submit(&$form, &$form_state, $queueID = 0) {
  $queueID = intval($form_state->getValue('maestro_queue_id'));
  $triggeringElement = $form_state->getTriggeringElement();
  if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
    \Drupal::messenger()
      ->addMessage("You have accepted request successfully", 'status');
    MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
  }
  else {
    \Drupal::messenger()->addStatus('You have rejected request successfully');

    MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
    MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
  }
}

/**
 * The manager approval form used in the workflow "Academy Weekly Abstract Submission Reviewer Approving".
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
function anh_maestro_academy_weekly_abstract_submission_reviewer_approve_form(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  // Get Webform submission by process ID
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  if (!$sid) {
    throw new NotFoundHttpException();
  }

  $webformSubmission = \Drupal::entityTypeManager()
    ->getStorage('webform_submission')
    ->load($sid);
  $webformSubmissionData = $webformSubmission->getData();
  $view_builder = \Drupal::entityTypeManager()
    ->getViewBuilder('webform_submission');

  if (isset($webformSubmissionData["academy_week"]) && !is_null($webformSubmissionData["academy_week"]) && !empty($webformSubmissionData["academy_week"]) && $nodeAcademyWeekId = $webformSubmissionData["academy_week"]) {
    $node = Node::load($webformSubmissionData["academy_week"]);
    $nodeTitle = $node->label();
  }

  $form['abstract_submission_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['abstract-submission-container']],
    '#weight'     => -10,
  ];
  $form['abstract_submission_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['abstract-submission-title']],
    '#value'      => "Academy Week Abstract Review",
  ];
  $form['abstract_submission_container']["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['abstract-submission-description']],
    '#value'      => isset($nodeTitle) ? "Welcome and thank you for taking part in the review of $nodeTitle abstract submission. The review shouldn’t take that long, but before you begin please read through their submission below." : "Welcome and thank you for taking part in the review of abstract submission. The review shouldn’t take that long, but before you begin please read through their submission below.",
  ];

  $form['abstract_submission_results_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['abstract-submission-results-container']],
    '#weight'     => -10,
  ];
  $form['abstract_submission_results_container']["webform_submission"] = $view_builder->view($webformSubmission, "default");
  $form['abstract_submission_results_container']['download_abstract_submissions_url'] = [
    '#type'  => 'link',
    '#title' => t('Download Abstract Submissions'),
    '#url'   => Url::fromUserInput("/print/pdf/webform_submission/$sid?view_mode=table", [
      'attributes' => [
        'target' => '_blank',
        'class'  => "button webform-entity-print-link webform-entity-print-link-pdf",
      ],
    ]),
  ];

  $current_path = \Drupal::request()->getRequestUri();
  $url = Url::fromUserInput($current_path, ["fragment" => "abstract-submission-review"]);

  $form['abstract_submission_results_container']['begin_review_url'] = [
    '#attributes' => [
      'class' => ['edit-next-button'],
      'target' => "_blank"
    ],
    '#type' => 'link',
    '#url' => $url,
    '#title' => t('Begin Review')
  ];

  $form['abstract_submission_review_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['abstract-submission-review-container']],
  ];
  $form['abstract_submission_review_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h5',
    '#attributes' => ['class' => ['title mb-4 mt-4']],
    '#value'      => t("Review - Multiple choice"),
  ];

  $form['abstract_submission_review_container']["relevance_container"] = [
    '#type' => 'container',
  ];
  $form['abstract_submission_review_container']["relevance_container"]["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h5',
    '#attributes' => ['class' => ['title']],
    '#value'      => t("Relevance"),
  ];
  $form['abstract_submission_review_container']["relevance_container"]["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['description mb-3']],
    '#value'      => t("To what extent does the abstract address issues that present significant interdisciplinary
      research challenges to agriculture and food systems for improved nutrition and health in LMICs?
      The research should investigate the relationship between agriculture and food systems on the one hand,
      and nutrition and/or health on the other."),
  ];

  $options = [
    1 => t('Not competitive'),
    2 => t('Satisfactory'),
    3 => t('Good'),
    4 => t('Excellent'),
    5 => t('Outstanding'),
  ];

  $form['abstract_submission_review_container']["relevance_container"]['relevance'] = [
    '#type'     => 'radios',
    '#options'  => $options,
    '#required' => TRUE,
  ];

  $form['abstract_submission_review_container']["scientific_rigour_container"] = [
    '#type' => 'container',
  ];
  $form['abstract_submission_review_container']["scientific_rigour_container"]["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h5',
    '#attributes' => ['class' => ['title mt-4']],
    '#value'      => t("Scientific rigour"),
  ];
  $form['abstract_submission_review_container']["scientific_rigour_container"]["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['description mb-3']],
    '#value'      => t("Is the research rigorous in relation to international standards of scientific excellence?
      Are the methods and approaches sound and are the research findings and interpretations supported empirically?"),
  ];
  $form['abstract_submission_review_container']["scientific_rigour_container"]['scientific_rigour'] = [
    '#type'     => 'radios',
    '#options'  => $options,
    '#required' => TRUE,
  ];

  $form['abstract_submission_review_container']["abstract_quality_container"] = [
    '#type' => 'container',
  ];
  $form['abstract_submission_review_container']["abstract_quality_container"]["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h5',
    '#attributes' => ['class' => ['title mt-4']],
    '#value'      => t("Abstract quality"),
  ];
  $form['abstract_submission_review_container']["abstract_quality_container"]["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['description mb-3']],
    '#value'      => t("Is this a convincing abstract? Are the objectives, methods and results presented clearly and coherently?
      We don’t want to disadvantage authors for whom English is not their first language, so please focus on the extent to
      which the abstract makes a convincing scientific argument."),
  ];
  $form['abstract_submission_review_container']["abstract_quality_container"]['abstract_quality'] = [
    '#type'     => 'radios',
    '#options'  => $options,
    '#required' => TRUE,
  ];

  $form['abstract_submission_review_container']["originality_container"] = [
    '#type' => 'container',
  ];
  $form['abstract_submission_review_container']["originality_container"]["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h5',
    '#attributes' => ['class' => ['title mt-4']],
    '#value'      => t("Originality"),
  ];
  $form['abstract_submission_review_container']["originality_container"]["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['description mb-3']],
    '#value'      => t("How innovative and original is the research? Does it address issues concerning agriculture and food systems for
      improved nutrition and health in ways and with methods that are less explored to date? Specific features mentioned
      in the Call for Abstracts may be considered here as well – e.g. innovative methods, equity focus, learning from failure."),
  ];
  $form['abstract_submission_review_container']["originality_container"]['originality'] = [
    '#type'     => 'radios',
    '#options'  => $options,
    '#required' => TRUE,
  ];

  $form['abstract_submission_review_container']["presentation_container"] = [
    '#type' => 'container',
  ];
  $form['abstract_submission_review_container']["presentation_container"]["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h5',
    '#attributes' => ['class' => ['title mt-4']],
    '#value'      => t("Poster or oral presentation recommendation?"),
  ];
  $form['abstract_submission_review_container']["presentation_container"]["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['description']],
    '#value'      => t("Please select one or leave black if not recommended for any."),
  ];
  $form['abstract_submission_review_container']["presentation_container"]['presentation'] = [
    '#type'     => 'radios',
    '#options'  => [
      1 => t('Poster'),
      2 => t('Oral'),
      3 => t('Both'),
    ],
    '#required' => TRUE,
  ];

  $form['abstract_submission_review_container']['feedback'] = [
    '#title' => t("Please provide at least one sentence of feedback."),
    '#type'      => 'textfield',
    '#maxlength' => 200,
  ];
  $form['abstract_submission_review_container']['previous_step'] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#title' => t('Previous Step'),
    '#attributes' => ['class' => ['back__btn']],
    '#value'      => t("Previous Step")
  ];

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

  $form['abstract_submission_review_container']['submit'] = [
    '#type'  => 'submit',
    '#attributes' => ['class' => ['confrirm__submission']],
    '#value' => t('Confirm Submission'),
  ];

  unset($form['actions']['submit']);

  return $form;
}

/**
 * This is the submit handler used in "Academy Week Abstract Submission
 * Reviewer Approving".
 *
 * @param array $form
 * @param FormStateInterface $form_state
 * @param int $queueID
 *
 * @return \Drupal\Core\Form\FormStateInterface
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function anh_maestro_academy_weekly_abstract_submission_reviewer_approve_form_submit(&$form, &$form_state, $queueID = 0) {
  $currentUserID = \Drupal::currentUser()->id();
  $queueID = intval($form_state->getValue('maestro_queue_id'));

  $comment = Comment::create([
    'entity_type'                 => 'maestro_process',
    'entity_id'                   => $form_state->getValue('processID'),
    'field_name'                  => 'comment',
    'uid'                         => $currentUserID,
    'comment_type'                => 'abstract_review',
    'status' => 1,
    'subject'                     => t('Academy Week Abstract Submission Review'),
    'field_abstract_quality'      => $form_state->getValue('abstract_quality'),
    'field_originality'           => $form_state->getValue('originality'),
    'field_poster_presentation'   => $form_state->getValue('presentation'),
    'field_relevance'             => $form_state->getValue('relevance'),
    'field_scientific_rigour'     => $form_state->getValue('scientific_rigour'),
    'field_feedback'              => $form_state->getValue('feedback'),
    'field_webform_submission_id' => $form_state->getValue('sid'),
  ]);
  $comment->save();

  \Drupal::messenger()->addStatus('You have added review successfully');
  MaestroEngine::completeTask($queueID, $currentUserID);
}

/**
 * The manager approval form used in the workflow "Academy Week Abstract Form Staff Approving Finish".
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
function anh_maestro_academy_weekly_abstract_submission_astaff_approve_finish_form(&$form, $queueID, $obj) {
  $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
  $entityTypeManager = \Drupal::entityTypeManager();
  // Get Webform submission by process ID
  $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');

  if (!$sid) {
    throw new NotFoundHttpException();
  }

  $webformSubmission = $entityTypeManager
    ->getStorage('webform_submission')
    ->load($sid);
  $webformSubmissionData = $webformSubmission->getData();
  $view_builder = $entityTypeManager->getViewBuilder('webform_submission');

  if ($webformSubmissionData["academy_week"] && !empty($webformSubmissionData["academy_week"]) && $nodeAcademyWeekId = $webformSubmissionData["academy_week"]) {
    $node = Node::load($webformSubmissionData["academy_week"]);
    $nodeTitle = $node->label();
  }

  $form['abstract_submission_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['abstract-submission-container']],
    '#weight'     => -10,
  ];
  $form['abstract_submission_container']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['abstract-submission-title']],
    '#value'      => "Academy Week Abstract Review",
  ];
  $form['abstract_submission_container']["description"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'div',
    '#attributes' => ['class' => ['abstract-submission-description']],
    '#value'      => isset($nodeTitle) ? "Welcome and thank you for taking part in the review of $nodeTitle abstract submission. The review shouldn’t take that long, but before you begin please read through their submission below." : "Welcome and thank you for taking part in the review of abstract submission. The review shouldn’t take that long, but before you begin please read through their submission below.",
  ];

  $form['abstract_submission_results_container'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['abstract-submission-results-container']],
    '#weight'     => -10,
  ];
  $form['abstract_submission_results_container']["webform_submission"] = $view_builder->view($webformSubmission, "table");

  $form['abstract_submission_reviews'] = [
    '#type'       => 'container',
    '#attributes' => [
      'class' => [
        'abstract-submission-container',
        "abstract-submission-reviews",
      ],
    ],
  ];
  $form['abstract_submission_reviews']["title"] = [
    '#type'       => 'html_tag',
    '#tag'        => 'h2',
    '#attributes' => ['class' => ['abstract-submission-title']],
    '#value'      => "Academy Week Abstract Reviews",
  ];

  $form['abstract_submission_reviews']["result"] = [
    '#type'       => 'container',
    '#attributes' => [
      'class' => [
        'result',
      ],
    ],
  ];

  $reviewsCommentsBuild = \Drupal::service('anh_maestro.reviews_comments_build');
  $comments = $reviewsCommentsBuild->getAbstractReviewCommentsDetails($processID);
  $commentsResult = [];

  foreach ($comments as $commentKey => $comment) {
    $commentArray = (array) $comment;
    foreach ($commentArray as $key => $value) {
      if (in_array($key, ["field_abstract_quality_value", "field_originality_value", "field_relevance_value", "field_scientific_rigour_value"])) {
        $value = $reviewsCommentsBuild->getOptionsLabel($value);
      }
      if ($key == "field_poster_presentation_value") {
        switch ($value) {
          case 1 :
            $value = "Poster";
            break;
          case 2 :
            $value = 'Oral';
            break;
          case 3 :
            $value = 'Both';
            break;
        }
      }

      $commentsResult[$commentKey][$key] = $value;
    }
  }

  $form['abstract_submission_reviews']["result"]["comments"] = [
    '#theme' => 'anh_maestro_abstract_review_comments',
    '#comments' => $commentsResult,
  ];

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

  $form['actions']['submit']['#value'] = t('Confirm Submission');


  $form['abstract_submission_review_container']['reject'] = [
    '#type'  => 'submit',
    '#value' => t('Reject Submission'),
  ];

  return $form;
}

/**
 * This is the submit handler used in "Academy Week Abstract Form Staff Approving Finish".
 *
 * @param array $form
 * @param FormStateInterface $form_state
 * @param int $queueID
 */
function anh_maestro_academy_weekly_abstract_submission_astaff_approve_finish_form_submit(&$form, &$form_state, $queueID = 0) {
//  global $base_url;
  $queueID = intval($form_state->getValue('maestro_queue_id'));
//  $processRecord = MaestroEngine::getProcessEntryById($form_state->getValue('processID'));
  $triggeringElement = $form_state->getTriggeringElement();
//  $initiatorUid = $processRecord->get("initiator_uid")->value;
//  $webformSubmissionId = $form_state->getValue('sid');

//  $invitationService = \Drupal::service('anh_maestro.anh_invitation');
//  $result = $invitationService->setData($initiatorUid, $webformSubmissionId);
//
//  $account = User::load($initiatorUid);
//  $name = $account->get("field_user_first_name")->value . " " . $account->get("field_user_last_name")->value;
//
//  $url = Url::fromUserInput('/submit-presentation-form/' . $invitationService->getToken(), ["absolute"   => TRUE, 'attributes' => ['target' => '_blank'],
//  ]);
//  $link = Link::fromTextAndUrl("page", $url)->toString();
//
//  $invitationService->sendMail("ANH Academy Week Abstract Presentation Submission", "Hello $name. Please send your presentation for ANH Academy Week Abstract on the $link");

  if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
    MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
  }
  else {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $key = 'reject_task';

    $processRecord = MaestroEngine::getProcessEntryById($form_state->getValue('processID'));
    $initiatorUid = $processRecord->get("initiator_uid")->value;
    $account = User::load($initiatorUid);
    $name = $account->get("field_user_first_name")->value . " " . $account->get("field_user_last_name")->value;
    $to = $account->getEmail();
    $params['message'] = "Hello $name. Your request to ANH Academy Week Abstract Submission was rejected";
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $result = $mailManager->mail("anh_maestro", $key, $to, $langcode, $params, NULL, TRUE);

    //we'll complete the task, but we'll also flag it as TASK_STATUS_CANCEL
    MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
    MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
  }
}
