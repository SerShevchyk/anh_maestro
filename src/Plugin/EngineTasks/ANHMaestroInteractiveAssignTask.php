<?php

namespace Drupal\anh_maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\Plugin\EngineTasks\MaestroInteractiveTask;
use Drupal\user\Entity\User;

/**
 * Maestro Interactive Example Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue.
 *
 * @Plugin(
 *   id = "ANHMaestroInteractiveAssignTask",
 *   task_description = @Translation("The Maestro Engine's ANH Interactive Assign task."),
 * )
 */
class ANHMaestroInteractiveAssignTask extends MaestroInteractiveTask implements MaestroEngineTaskInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The incoming configuration information from the engine execution.
   *   [0] - is the process ID
   *   [1] - is the queue ID
   *   The processID and queueID properties are defined in the MaestroTaskTrait.
   */
  public function __construct(array $configuration = NULL) {
    if (is_array($configuration)) {
      $this->processID = $configuration[0];
      $this->queueID = $configuration[1];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function isInteractive() {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return $this->t('ANH Maestro Interactive Assign Task');
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('ANH Maestro Interactive Assign Task');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'ANHMaestroInteractiveAssignTask';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#0000ff';
  }

  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    $form['queueID'] = [
      // This is just a placeholder form to get you under way.
      '#type' => 'hidden',
      '#title' => $this->t('The queue ID of this task'),
      '#default_value' => $this->queueID,
      '#description' => $this->t('queueID'),
    ];

    $form['information_text'] = [
      '#plain_text' => $this->t('Default Maestro Interactive Task.'),
      '#suffix' => '<br><br>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Complete'),
    ];

    $form['actions']['reject'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reject'),
    ];

    if ($modal == 'modal') {
      $form['actions']['submit']['#ajax'] = [
        'callback' => [$parent, 'completeForm'],
        'wrapper' => '',
      ];

      $form['actions']['reject']['#ajax'] = [
        'callback' => [$parent, 'completeForm'],
        'wrapper' => '',
      ];
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
    $queueID = intval($form_state->getValue('maestro_queue_id'));
    $triggeringElement = $form_state->getTriggeringElement();
    if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
      MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
    }
    else {
      // we'll complete the task, but we'll also flag it as TASK_STATUS_CANCEL.
      MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
      MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
    }

    $task = MaestroEngine::getTemplateTaskByQueueID($queueID);
    if (isset($task['data']['redirect_to'])) {
      $response = new TrustedRedirectResponse($task['data']['redirect_to']);
      $form_state->setResponse($response);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    $form = [
      '#markup' => t('Interactive Task Edit'),
    ];

    // Let modules signal the handlers they wish to share.
    $handlers = \Drupal::moduleHandler()->invokeAll('maestro_interactive_handlers', []);
    $handler_desc = $this->t('The function that contains the form definition for this instance of the interactive task.');
    if (isset($task['handler']) && isset($handlers[$task['handler']])) {
      $handler_desc = $handlers[$task['handler']];
    }

    // The handler will use a lookahead.
    $form['handler'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Handler'),
      '#default_value' => isset($task['handler']) ? $task['handler'] : '',
      '#required' => FALSE,
      '#autocomplete_route_name' => 'maestro.autocomplete.interactive_handlers',
      '#ajax' => [
        'callback' => [$this, 'interactiveHandlerCallback'],
        'event' => 'autocompleteclose',
        'wrapper' => 'handler-ajax-refresh-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['handler_help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $handler_desc,
      '#readonly' => TRUE,
      '#attributes' => [
        'class' => ['handler-help-message'],
        'id' => ['handler-ajax-refresh-wrapper'],
      ],
    ];

    $form['redirect_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return Path'),
      '#description' => $this->t('You can specify where your return path should go upon task completion.'),
      '#default_value' => isset($task['data']['redirect_to']) ? $task['data']['redirect_to'] : 'taskconsole',
      '#required' => TRUE,
    ];

    $form['modal'] = [
      '#type' => 'select',
      '#title' => $this->t('Task presentation'),
      '#description' => $this->t('Should this task be shown as a modal or full screen task.'),
      '#default_value' => isset($task['data']['modal']) ? $task['data']['modal'] : 'modal',
      '#options' => [
        'modal' => $this->t('Modal'),
        'notmodal' => $this->t('Full Page'),
      ],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    /*
     * When you use a task in the template builder, it will be up to the task to provide any sort of debugging and validation
     * information to the end user.  Do you have a field that MUST be set in order for the task to execute?
     * How about a field that doesn't have the right values?  This is where you would populate the
     * $validation_failure_tasks array with failure information and the
     * $validation_information_tasks with informational messages.
     *
     * See the MaestroEngineTaskInterface.php interface declaration of this method for details.
     */

    // We force-set the handler in our prepareTaskForSave method.
    // if for some reason this doesn't get set, we fail validation.
    if ((array_key_exists('handler', $task) && $task['handler'] == '')  || !array_key_exists('handler', $task)) {
      $validation_failure_tasks[] = [
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Example Interactive Task handler is missing and thus the engine will fail to show an execute link to the user. Try to edit and resave the task.'),
      ];
    }

    // Forcing the modal option to appear as well, so we check for it.
    if ((array_key_exists('modal', $task['data']) && $task['data']['modal'] == '')  || !array_key_exists('modal', $task['data'])) {
      $validation_failure_tasks[] = [
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Example Interactive Task modal option is missing. Try to edit and resave the task.'),
      ];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {
    return ['edit', 'drawlineto', 'removelines', 'remove'];
  }

  /**
   * Retrieve the core Maestro form edit elements for Assignments and Notifications.
   *
   * @param array $task
   *   The task loaded from the template.
   * @param string $templateMachineName
   *   The Maestro template's machine name.
   */
  public function getAssignmentsAndNotificationsForm(array $task, $templateMachineName) {
    $variables = MaestroEngine::getTemplateVariables($templateMachineName);
    $options = [];
    foreach ($variables as $variableName => $arr) {
      $options[$variableName] = $variableName;
    }

    // Assignments section.
    $form['assignments'] = [
      '#title' => $this->t('Assignments'),
    ];

    $form['edit_task_assignments'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#group' => 'assignments',
      '#title' => 'Assignment Details',
    ];

    // The following are the assignment mechanisms.
    $form['edit_task_assignments']['select_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Assign by'),
      '#options' => [
        'fixed' => $this->t('Fixed Value'),
        'variable' => $this->t('Variable'),
      ],
      '#default_value' => 'fixed',
      '#attributes' =>
        [
          'onchange' => 'maestro_task_editor_assignments_assignby(this.value);',
        ],
    ];

    /*
     * Developers:  You can add to the onchange for this as you see fit to allow for other types
     */
    $form['edit_task_assignments']['select_assign_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Assign to'),
      '#options' => [
        'initiator' => $this->t('Initiator'),
        'user' => $this->t('User'),
        'role' => $this->t('Role'),
      ],
      '#default_value' => 'user',
      '#attributes' =>
        [
          'onchange' => 'maestro_task_editor_assignments_assignto(this.value);',
        ],
    ];

    $form['edit_task_assignments']['select_assigned_user'] = [
      '#id' => 'select_assigned_user',
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => '',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#title' => $this->t('User'),
      '#required' => FALSE,
      '#prefix' => '<div class="maestro-engine-user-and-role"><div class="maestro-engine-assignments-hidden-user">',
      '#suffix' => '</div>',
    ];

    $form['edit_task_assignments']['select_assigned_role'] = [
      '#id' => 'select_assigned_role',
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => $this->t('Role'),
      '#autocomplete_route_name' => 'maestro.autocomplete.roles',
      '#required' => FALSE,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-role">',
      '#suffix' => '</div></div>',
    ];

    $form['edit_task_assignments']['variable'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the variable'),
      '#required' => FALSE,
      '#default_value' => '',
      '#options' => $options,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-variable">',
      '#suffix' => '</div>',
    ];

    // Now to list the existing assignments here:
    $form['edit_task_assignments']['task_assignment_table'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [$this->t('Delete'), $this->t('To What'), $this->t('By'), $this->t('Assignee')],
      '#empty' => t('There are no assignments.'),
    ];

    isset($task['assigned']) ? $assignments = explode(',', $task['assigned']) : $assignments = [];
    $cntr = 0;
    foreach ($assignments as $assignment) {
      if ($assignment != '') {
        // [0]=to what, [1]=by fixed or variable, [2]=who or varname
        $howAssigned = explode(':', $assignment);

        $form['edit_task_assignments']['task_assignment_table'][$cntr]['delete'] = [
          '#type' => 'checkbox',
          '#default_value' => 0,
        ];
        $form['edit_task_assignments']['task_assignment_table'][$cntr]['to_what'] = [
          '#plain_text' => $howAssigned[0],
        ];
        $form['edit_task_assignments']['task_assignment_table'][$cntr]['by'] = [
          '#plain_text' => $howAssigned[1],
        ];
        $form['edit_task_assignments']['task_assignment_table'][$cntr]['asignee'] = [
          '#plain_text' => $howAssigned[2],
        ];

        $cntr++;
      }
    }

    // End of assignments section.
    // Notifications section.
    $form['notifications'] = [
      '#title' => $this->t('Notifications'),
    ];

    $form['edit_task_notifications'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#group' => 'notifications',
      '#title' => 'Notification Details',
    ];

    $form['edit_task_notifications']['select_notification_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Notify by'),
      '#options' => [
        'fixed' => $this->t('Fixed Value'),
        'variable' => $this->t('Variable'),
      ],
      '#default_value' => 'fixed',
      '#attributes' =>
        [
          'onchange' => 'maestro_task_editor_notifications_assignby(this.value);',
        ],
    ];

    $form['edit_task_notifications']['select_notification_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Notification to'),
      '#options' => [
        'initiator' => $this->t('Initiator'),
        'user' => $this->t('User'),
        'role' => $this->t('Role'),
      ],
      '#default_value' => 'user',
      '#attributes' =>
        [
          'onchange' => 'maestro_task_editor_notifications_assignto(this.value);',
        ],
    ];

    $form['edit_task_notifications']['select_notification_user'] = [
      '#id' => 'select_notification_user',
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => '',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#title' => $this->t('User'),
      '#required' => FALSE,
      '#prefix' => '<div class="maestro-engine-user-and-role-notifications"><div class="maestro-engine-notifications-hidden-user">',
      '#suffix' => '</div>',
    ];

    $form['edit_task_notifications']['select_notification_role'] = [
      '#id' => 'select_notification_role',
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => $this->t('Role'),
      '#autocomplete_route_name' => 'maestro.autocomplete.roles',
      '#required' => FALSE,
      '#prefix' => '<div class="maestro-engine-notifications-hidden-role">',
      '#suffix' => '</div></div>',
    ];

    $form['edit_task_notifications']['variable'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the variable'),
      '#required' => FALSE,
      '#default_value' => '',
      '#options' => $options,
      '#prefix' => '<div class="maestro-engine-notifications-hidden-variable">',
      '#suffix' => '</div>',
    ];

    $whichNotification = [
      'assignment' => $this->t('Assignment'),
      'reminder' => $this->t('Reminder'),
      'escalation' => $this->t('Escalation'),
    ];
    $form['edit_task_notifications']['which_notification'] = [
      '#type' => 'radios',
      '#title' => $this->t('Which notification'),
      '#required' => FALSE,
      '#default_value' => 'assignment',
      '#options' => $whichNotification,
      '#prefix' => '<div class="">',
      '#suffix' => '</div>',
    ];

    $form['edit_task_notifications']['reminder_after'] = [
      '#type' => 'textfield',
      '#default_value' => '0',
      '#title' => $this->t('Reminder After (days)'),
      '#required' => FALSE,
      '#size' => 2,
      '#prefix' => '<div class="maestro-engine-reminder-escalation-values"><div class="maestro-reminder-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['edit_task_notifications']['escalation_after'] = [
      '#type' => 'textfield',
      '#default_value' => '0',
      '#title' => $this->t('Escalation After (days)'),
      '#required' => FALSE,
      '#size' => 2,
      '#prefix' => '<div class="maestro-escalation-wrapper">',
      '#suffix' => '</div></div>',
    ];

    // Now to list the existing assignments here:
    $form['edit_task_notifications']['task_notifications_table'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [$this->t('Delete'), $this->t('To What'), $this->t('By'), $this->t('Assignee'), $this->t('Notification Type')],
      '#empty' => t('There are no notifications.'),
    ];

    if (array_key_exists('notifications', $task) && array_key_exists('notification_assignments', $task['notifications'])) {
      $notifications = explode(',', $task['notifications']['notification_assignments']);
      $cntr = 0;
      foreach ($notifications as $notification) {
        if ($notification != '') {
          // [0]=to what, [1]=by fixed or variable, [2]=who or varname, [3] which notification
          $howAssigned = explode(':', $notification);

          $form['edit_task_notifications']['task_notifications_table'][$cntr]['delete'] = [
            '#type' => 'checkbox',
            '#default_value' => 0,
          ];
          $form['edit_task_notifications']['task_notifications_table'][$cntr]['to_what'] = [
            '#plain_text' => $howAssigned[0],
          ];
          $form['edit_task_notifications']['task_notifications_table'][$cntr]['by'] = [
            '#plain_text' => $howAssigned[1],
          ];
          $form['edit_task_notifications']['task_notifications_table'][$cntr]['asignee'] = [
            '#plain_text' => $howAssigned[2],
          ];

          $form['edit_task_notifications']['task_notifications_table'][$cntr]['type'] = [
            '#plain_text' => $howAssigned[3],
          ];
          $cntr++;
        }
      }
    }

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['edit_task_notifications']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['maestro'],
      ];
    }
    else {
      $form['edit_task_notifications']['token_tree'] = [
        '#plain_text' => $this->t('Enabling the Token module will reveal the replacable tokens available for custom notifications.'),
      ];
    }
    $form['edit_task_notifications']['notification_assignment_subject'] = [
      '#type' => 'textarea',
      '#default_value' => isset($task['notifications']['notification_assignment_subject']) ? $task['notifications']['notification_assignment_subject'] : '',
      '#title' => $this->t('Custom Assignment Subject'),
      '#required' => FALSE,
      '#rows' => 1,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-notification">',
      '#suffix' => '</div>',
    ];
    $form['edit_task_notifications']['notification_assignment'] = [
      '#id' => 'notification_assignment',
      '#type' => 'textarea',
      '#default_value' => isset($task['notifications']['notification_assignment']) ? $task['notifications']['notification_assignment'] : '',
      '#title' => $this->t('Custom Assignment Message'),
      '#required' => FALSE,
      '#rows' => 2,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-notification">',
      '#suffix' => '</div>',
    ];
    $form['edit_task_notifications']['notification_reminder_subject'] = [
      '#type' => 'textarea',
      '#default_value' => isset($task['notifications']['notification_reminder_subject']) ? $task['notifications']['notification_reminder_subject'] : '',
      '#title' => $this->t('Custom Reminder Subject'),
      '#required' => FALSE,
      '#rows' => 1,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-notification">',
      '#suffix' => '</div>',
    ];
    $form['edit_task_notifications']['notification_reminder'] = [
      '#id' => 'notification_reminder',
      '#type' => 'textarea',
      '#default_value' => isset($task['notifications']['notification_reminder']) ? $task['notifications']['notification_reminder'] : '',
      '#title' => $this->t('Custom Reminder Message'),
      '#required' => FALSE,
      '#rows' => 2,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-escalation">',
      '#suffix' => '</div>',
    ];
    $form['edit_task_notifications']['notification_escalation_subject'] = [
      '#type' => 'textarea',
      '#default_value' => isset($task['notifications']['notification_escalation_subject']) ? $task['notifications']['notification_escalation_subject'] : '',
      '#title' => $this->t('Custom Escalation Subject'),
      '#required' => FALSE,
      '#rows' => 1,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-notification">',
      '#suffix' => '</div>',
    ];
    $form['edit_task_notifications']['notification_escalation'] = [
      '#id' => 'notification_escalation',
      '#type' => 'textarea',
      '#default_value' => isset($task['notifications']['notification_escalation']) ? $task['notifications']['notification_escalation'] : '',
      '#title' => $this->t('Custom Escalation Message'),
      '#required' => FALSE,
      '#rows' => 2,
      '#prefix' => '<div class="maestro-engine-assignments-hidden-escalation">',
      '#suffix' => '</div>',
    ];

    $form['#attached']['library'][] = 'maestro/maestro-engine-css';
    $form['#attached']['library'][] = 'maestro/maestro-engine-task-edit';

    return $form;
  }

  public function saveTask(array &$form, FormStateInterface $form_state, array &$task) {
    $result = FALSE;
    $templateMachineName = $form_state->getValue('template_machine_name');
    $taskID = $form_state->getValue('task_id');
    $taskAssignments = $form_state->getValue('edit_task_assignments');
    $taskNotifications = $form_state->getValue('edit_task_notifications');

    // $task holds the loaded task from the template.  We can now perform our general operations on the task
    // to ensure that it contains all of the proper elements in it for use by the engine.
    // the elements of our task that are minimally required are as follows:
    // id, tasktype, label, nextstep, nextfalsestep, top, left, assignby, assignto, assigned
    // first, do a validity check on the task structure
    // These are the keys that SHOULD exist in our task.
    $requiredKeys = [
      'id' => $taskID,
      'tasktype' => '',
      'label' => $form_state->getValue('label'),
      'nextstep' => '',
      'nextfalsestep' => '',
      'top' => 25,
      'left' => 25,
      'assignby' => '',
      'assignto' => '',
      'assigned' => '',
      'runonce' => 0,
      'handler' => '',
      'showindetail' => 1,
      'participate_in_workflow_status_stage' => 0,
      'workflow_status_stage_number' => 0,
      'workflow_status_stage_message' => '',
    ];
    $missingKeys = array_diff_key($requiredKeys, $task);
    foreach ($missingKeys as $key => $val) {
      // Seed the key properly with default values.
      $task[$key] = $val;
    }
    // Now the core fields.
    $task['label'] = $form_state->getValue('label');
    $task['participate_in_workflow_status_stage'] = $form_state->getValue('participate_in_workflow_status_stage');
    $task['workflow_status_stage_number'] = $form_state->getValue('workflow_status_stage_number');
    $task['workflow_status_stage_message'] = $form_state->getValue('workflow_status_stage_message');

    // Now the assignments.
    $executableTask = MaestroEngine::getPluginTask($task['tasktype']);
    if ($executableTask->isInteractive()) {
      // ok, we now manipulate the assignments if we're in here
      // first to detect if we're deleting anything
      // break out the current assignments.
      isset($task['assigned']) ? $currentAssignments = explode(',', $task['assigned']) : $currentAssignments = [];
      isset($taskAssignments['task_assignment_table']) ? $deleteAssignmentsList = $taskAssignments['task_assignment_table'] : $deleteAssignmentsList = [];

      if (isset($deleteAssignmentsList) && is_array($deleteAssignmentsList)) {
        foreach ($deleteAssignmentsList as $key => $arr) {
          // The deleteAssignmentsList is a key-for-key alignment with the currentAssignments.
          if ($arr['delete'] == 1) {
            unset($currentAssignments[$key]);
          }
        }
      }
      $task['assigned'] = implode(',', $currentAssignments);

      if (($taskAssignments['select_assigned_role'] != '' || $taskAssignments['select_assigned_user'] != '') && $taskAssignments['select_method'] == 'fixed') {
        // alright, formulate the assignment.
        if ($taskAssignments['select_assigned_user'] != '' && $taskAssignments['select_assign_to'] == 'user') {
          // Need to get the username.
          $account = User::load($taskAssignments['select_assigned_user']);
          $assignee = $account->getAccountName();
        }
        elseif ($taskAssignments['select_assigned_role'] != '' && $taskAssignments['select_assign_to'] == 'role') {
          // Need to strip out the text surrounding the bracketed values.
          preg_match('#\((.*?)\)#', $taskAssignments['select_assigned_role'], $match);
          $assignee = $match[1];
        }
        $assignment = $taskAssignments['select_assign_to'] . ':' . $taskAssignments['select_method'] . ':' . $assignee;
      }
      elseif ($taskAssignments['select_method'] == 'variable') {
        $assignment = $taskAssignments['select_assign_to'] . ':' . $taskAssignments['select_method'] . ':' . $taskAssignments['variable'];
      }
      if ($taskAssignments['select_assign_to'] == 'initiator') {
        $assignment = 'initiator';
      }
      if (isset($assignment) && $assignment != '') {
        if ($task['assigned'] != '') {
          $task['assigned'] .= ',';
        }
        $task['assigned'] .= $assignment;
      }

      // And now notifications
      // we need to parse out the notification form to determine what this person is trying to add in a similar fashion to that of the assignments.
      if (!array_key_exists('notifications', $task)) {
        // Lets just seed the main array key.
        $task['notifications'] = [];
      }
      if (array_key_exists('notification_assignments', $task['notifications'])) {
        $currentNotifications = explode(',', $task['notifications']['notification_assignments']);
        $deleteNotificationsList = $taskNotifications['task_notifications_table'];
        foreach ($deleteNotificationsList as $key => $arr) {
          // The $deleteNotificationsList is a key-for-key alignment with the currentNotifications.
          if ($arr['delete'] == 1) {
            unset($currentNotifications[$key]);
          }
        }
        $task['notifications']['notification_assignments'] = implode(',', $currentNotifications);
      }
      $notifications = '';
      if (($taskNotifications['select_notification_role'] != '' || $taskNotifications['select_notification_user'] != '') && $taskNotifications['select_notification_method'] == 'fixed') {
        // alright, formulate the assignment.
        if ($taskNotifications['select_notification_user'] != '' && $taskNotifications['select_notification_to'] == 'user') {
          // Need to get the username.
          $account = User::load($taskNotifications['select_notification_user']);
          $assignee = $account->getAccountName();
        }
        elseif ($taskNotifications['select_notification_role'] != '' && $taskNotifications['select_notification_to'] == 'role') {
          // Need to strip out the text surrounding the bracketed values.
          preg_match('#\((.*?)\)#', $taskNotifications['select_notification_role'], $match);
          $assignee = $match[1];
        }
        $notifications = $taskNotifications['select_notification_to'] . ':' . $taskNotifications['select_notification_method'] . ':' . $assignee . ':' . $taskNotifications['which_notification'];
      }
      elseif ($taskNotifications['select_notification_method'] == 'variable') {
        $notifications = $taskNotifications['select_notification_to'] . ':' . $taskNotifications['select_notification_method'] . ':' . $taskNotifications['variable'] . ':' . $taskNotifications['which_notification'];
      }

      if ($notifications != '') {
        if ($task['notifications']['notification_assignments'] != '') {
          $task['notifications']['notification_assignments'] .= ',';
        }
        $task['notifications']['notification_assignments'] .= $notifications;
      }
    }
    $task['notifications']['notification_assignment_subject'] = $taskNotifications['notification_assignment_subject'];
    $task['notifications']['notification_assignment'] = $taskNotifications['notification_assignment'];
    $task['notifications']['notification_reminder_subject'] = $taskNotifications['notification_reminder_subject'];
    $task['notifications']['notification_reminder'] = $taskNotifications['notification_reminder'];
    $task['notifications']['notification_escalation_subject'] = $taskNotifications['notification_escalation_subject'];
    $task['notifications']['notification_escalation'] = $taskNotifications['notification_escalation'];

    $task['notifications']['reminder_after'] = $taskNotifications['reminder_after'];
    $task['notifications']['escalation_after'] = $taskNotifications['escalation_after'];

    // Let other modules do their own assignments and notifications and any other task mods they want.
    \Drupal::moduleHandler()->invokeAll('maestro_pre_task_save', [$templateMachineName, $taskID, &$task, $taskAssignments, $taskNotifications]);

    // Finally save the task.
    $result = MaestroEngine::saveTemplateTask($templateMachineName, $taskID, $task);
    // TODO: What to do with the result if an error exists?
    // we now clear out the form values for a few specific fields we have control over.
    $arr = $form_state->getUserInput();
    $arr['edit_task_assignments']['select_assigned_role'] = '';
    $arr['edit_task_assignments']['select_assigned_user'] = '';
    $arr['edit_task_assignments']['select_assign_to'] = 'user';
    $arr['edit_task_assignments']['select_method'] = 'fixed';
    $arr['edit_task_notifications']['select_notification_role'] = '';
    $arr['edit_task_notifications']['select_notification_user'] = '';
    $arr['edit_task_notifications']['select_notification_to'] = 'user';
    $arr['edit_task_notifications']['select_notification_method'] = 'fixed';
    $arr['edit_task_notifications']['which_notification'] = 'assignment';

    $form_state->setUserInput($arr);

    return $result;
  }
}
