<?php

namespace Drupal\anh_maestro\Plugin\EngineTasks;

use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\node\Entity\NodeType;
use Drupal\webform\Controller\WebformSubmissionViewController;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\maestro_webform\Plugin\EngineTasks\MaestroWebformTask;

/**
 * Maestro Webform Task Plugin.
 *
 * @Plugin(
 *   id = "ANHMaestroWebform",
 *   task_description = @Translation("The ANH Maestro Engine's Interactive Webform task."),
 * )
 */
class ANHMaestroWebformTask extends MaestroWebformTask implements MaestroEngineTaskInterface{

  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return $this->t('ANH Webfom Task');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    // The ID of the plugin.  Should match the @id shown in the annotation.
    return 'ANHMaestroWebform';
  }

  /**
   * Part of the ExecutableInterface
   * Execution of the Example task returns TRUE and does nothing else.
   * {@inheritdoc}.
   */
  public function execute() {
    /*
     * Setting our run_once flag so that the engine doesn't have to keep trying to process this task.
     */

    $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($this->queueID);
    $queueRecord->set('run_once', 0);
    $queueRecord->save();
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
    $completeTask = TRUE;
    $queueID = intval($form_state->getValue('maestro_queue_id'));
    $triggeringElement = $form_state->getTriggeringElement();

    $query = \Drupal::entityQuery('maestro_production_assignments');
    $query->condition('queue_id', $queueID);
    $query->condition('assign_id', $queueID);
    $assignmentIDs = $query->execute();
    foreach ($assignmentIDs as $assignmentID) {
      $assignmentRecord = \Drupal::entityTypeManager()->getStorage('maestro_production_assignments')->load($assignmentID);
      $assignmentRecord->set('task_completed', 1);
      $assignmentRecord->save();
    }

    if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
      MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
    }

    // Redirect based on where the task told us to go.
    if (isset($templateTask['data']['redirect_to']) && $templateTask['data']['redirect_to'] != '') {
      $response = new TrustedRedirectResponse('/' . $templateTask['data']['redirect_to']);
      $form_state->setResponse($response);
    }
    else {
      $response = new TrustedRedirectResponse('/taskconsole');
      $form_state->setResponse($response);
    }

    // Let the devs manage the submission as well:
    \Drupal::moduleHandler()->invokeAll('maestro_webform_submission_form_submit',
        [$queueID, &$form, &$form_state, $triggeringElement]);

  }
}
