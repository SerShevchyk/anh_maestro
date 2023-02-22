<?php

namespace Drupal\anh_maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\Plugin\EngineTasks\MaestroEndTask;
use Drupal\user\Entity\User;

/**
 * Maestro End Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue.
 *
 * @Plugin(
 *   id = "ANHMaestroEnd",
 *   task_description = @Translation("The ANH Maestro Engine's end task."),
 * )
 */
class ANHMaestroEndTask extends MaestroEndTask {

  /**
   * Constructor.
   *
   * @param null $configuration
   */
  public function __construct($configuration = NULL) {
    parent::__construct($configuration);
  }

  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return t('ANH End Task');
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('ANH End Task.');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'ANHMaestroEnd';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#ff0000';
  }

  /**
   * Part of the ExecutableInterface
   * Execution of the End task will complete the process and return true so the engine completes the task.
   * {@inheritdoc}.
   */
  public function execute() {
    if ($this->processID > 0) {

      MaestroEngine::endProcess($this->processID);
//      $mailManager = \Drupal::service('plugin.manager.mail');
//      $key = 'reject_task';
//
//      $processRecord = MaestroEngine::getProcessEntryById($this->processID);
//      $initiatorUid = $processRecord->get("initiator_uid")->value;
//      $account = User::load($initiatorUid);
//      $name = $account->get("field_user_first_name")->value . " " . $account->get("field_user_last_name")->value;
//      $to = $account->getEmail();
//      $params['message'] = "Hello $name. Your request was rejected";
//      $langcode = \Drupal::currentUser()->getPreferredLangcode();
//      $result = $mailManager->mail("anh_maestro", $key, $to, $langcode, $params, NULL, TRUE);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {
    return ['edit', 'removelines', 'remove'];
  }

}
