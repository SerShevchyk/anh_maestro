<?php
/**
 * @file
 * Contains Drupal\maestro_taskconsole\Controller\MaestroTaskConsoleController.
 */

namespace Drupal\anh_maestro\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\maestro\Controller\MaestroOrchestrator;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Utility\MaestroStatus;
use Drupal\maestro\Utility\TaskHandler;
use Drupal\maestro_taskconsole\Controller\MaestroTaskConsoleController;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\views\Views;

class ANHMaestroTaskConsoleController extends MaestroTaskConsoleController {

  /**
   * getTasks method
   * This method is called by the menu router for /taskconsole
   * The output of this method is the current user's task console.
   *
   * @param int $highlightQueueID
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getTasks($highlightQueueID = 0) {
    global $base_url;

    $currentUserID = \Drupal::currentUser()->id();
    $processes = [];
    $config = \Drupal::config('maestro.settings');
    //before we do anything, let's see if we should be running the orchestrator through task console refreshes:
    if ($config->get('maestro_orchestrator_task_console')) {
      $orchestrator = new MaestroOrchestrator();
      $orchestrator->orchestrate($config->get('maestro_orchestrator_token'));
    }
    $engine = new MaestroEngine();

    $build = [];
    $build['task_console_table'] = [
      '#type'       => 'table',
      '#header'     => [
        $this->t('Initiator'),
        $this->t('Task'),
        $this->t('Flow'),
        $this->t('Assigned'),
        $this->t('Actions'),
        $this->t('Details'),
      ],
      '#empty'      => t('You have no tasks.'),
      '#attributes' => [
        'class' => ['taskconsole-tasks'],
      ],
    ];

    //fetch the user's queue items
    $queueIDs = MaestroEngine::getAssignedTaskQueueIds($currentUserID);

    foreach ($queueIDs as $queueID) {
      $highlight = '';
      $url_from_route = FALSE;
      if ($highlightQueueID == $queueID) {
        //set the highlight for the queue entry
        $highlight = 'maestro-highlight-task';
      }

      /*
       *  Reset the internal static cache for this queue record and then reload it
       *  Doing this because we found in certain cases it was not reflecting actual queue record
       */
      \Drupal::entityTypeManager()
        ->getStorage('maestro_queue')
        ->resetCache([$queueID]);
      $queueRecord = \Drupal::entityTypeManager()
        ->getStorage('maestro_queue')
        ->load($queueID);

      $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
      $processRecord = MaestroEngine::getProcessEntryById($processID);
      $sid = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');
      $templateId = $processRecord->get("template_id")->value;

      switch ($templateId) {
        case "academy_weekly_abstract_submission" :
          if (in_array($processID, $processes)) {
            continue;
          }
          else {
            $task = MaestroEngine::getTemplateTaskByQueueID($queueID);
            $currentStatusNumber = $task['workflow_status_stage_number'];
            if (isset($task['participate_in_workflow_status_stage']) && $task['participate_in_workflow_status_stage'] == 1 && $currentStatusNumber == 4) {
              $query = \Drupal::entityQuery('maestro_process_status')
                ->condition('process_id', $processID)
                ->condition('stage_number', $currentStatusNumber - 1);
              $statusEntityIDs = $query->execute();
              foreach ($statusEntityIDs as $entity_id) {
                $statusRecord = \Drupal::entityTypeManager()->getStorage('maestro_process_status')->load($entity_id);
                if ($statusRecord) {
                  if ($currentUserID == $statusRecord->get("complete_uid")->value) {
                    continue 3;
                  }
                }
              }
            }
          }
          break;
      }
      $processes[] = $processID;

      if ($sid) {
        $webformSubmission = \Drupal::entityTypeManager()->getStorage('webform_submission')->load($sid);
        if (!$webformSubmission) {
          continue;
        }
        $webformSubmissionData = $webformSubmission->getData();

        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
        $form["webform_submission"] = $view_builder->view($webformSubmission, "default");

        if (isset($webformSubmissionData["academy_week"]) && !empty($webformSubmissionData["academy_week"]) && $nodeAcademyWeekId = $webformSubmissionData["academy_week"]) {
          $node = Node::load($webformSubmissionData["academy_week"]);

          if ($node) {
            $academyWeekLinkRenderArray = [
              '#type'   => 'link',
              '#title'  => "Academy Week: " . $node->label(),
              '#attributes' => ['class' => ['academy-week-title'], "target" => "_blank"],
              '#url'    => Url::fromRoute('entity.node.canonical', ['node' => $webformSubmissionData["academy_week"]], ['absolute' => TRUE, 'target' => '_blank']),
            ];
          }
        }
      }

      $initiatorUid = $processRecord->get("initiator_uid")->value;
      $initiator = User::load($initiatorUid);
      $initiatorUrl = $initiator->toUrl();

      $build['task_console_table'][$queueID]['#attributes'] = ['class' => $highlight];

      $build['task_console_table'][$queueID]['initiator'] = [
        '#type'   => 'link',
        '#title'  => sprintf("%s %s", $initiator->get("field_user_first_name")->value, $initiator->get("field_user_last_name")->value),
        '#attributes' => ['class' => ['initiator'], "target" => "_blank"],
        '#url'    => $initiatorUrl->setOption("target", "_blank"),
      ];
      $build['task_console_table'][$queueID]['task'] = ['#plain_text' => $queueRecord->task_label->getString()];

      $build['task_console_table'][$queueID]['flow'] = [
        '#plain_text' => $processRecord->process_name->getString(),
      ];

      $build['task_console_table'][$queueID]['assigned'] = [
        '#plain_text' => \Drupal::service('date.formatter')
          ->format($queueRecord->created->getString(), 'custom', 'Y-m-d H:i:s'),
      ];

      $templateMachineName = $engine->getTemplateIdFromProcessId($queueRecord->process_id->getString());
      $taskTemplate = $engine->getTemplateTaskByID($templateMachineName, $queueRecord->task_id->getString());
      $template = MaestroEngine::getTemplate($templateMachineName);
      $link = 'Execute';  // Default link title
      $use_modal = FALSE;
      $query_options = ['queueid' => $queueID];

      if (array_key_exists('data', $taskTemplate) && array_key_exists('modal', $taskTemplate['data']) && $taskTemplate['data']['modal'] == 'modal') {
        $use_modal = TRUE;
      }
      /*
       * If this is an interactive Maestro task, it means we show an Operations Dropbutton form element
       * This is a  button with one or more links where the links can be to a node add/edit or
       * to open up a modal window for an interactive task like a form approval action.
       *
       * We need to determine if we have any special handling for this interactive task. It could be
       * a link to an external system.
       */

      /*
       * Test to see if this is a URL that can be deduced from a Drupal route or not.
       * if it's not a route, then $url_from_route will be FALSE
       */

      $handler = $queueRecord->handler->getString();
      if ($handler && !empty($handler) && $queueRecord->is_interactive->getString() == '1') {

        $handler = str_replace($base_url, '', $handler);
        $handler_type = TaskHandler::getType($handler);

        $handler_url_parts = UrlHelper::parse($handler);
        $query_options += $handler_url_parts['query'];

        //Let's call a hook here to let people change the name of the link to execute the task if they so choose to do so
        \Drupal::moduleHandler()
          ->invokeAll('maestro_task_console_interactive_link_alter', [
            &$link,
            $taskTemplate,
            $queueRecord,
            $templateMachineName,
          ]);

      }
      elseif ($queueRecord->is_interactive->getString() == '1' && empty($handler)) {
        //handler is empty.  If this is an interactive task and has no handler, we're still OK.  This is an interactive function that uses a default handler then.
        $handler_type = 'function';
      }
      else {
        //we shouldn't be processing this. Skip the rest.
        continue;
      }

      $links = [];

      switch ($handler_type) {
        case 'external' :
          $build['task_console_table'][$queueID]['execute']['maestro_link'] = [
            '#type'  => 'link',
            '#title' => $this->t($link),
            '#url'   => Url::fromUri($handler, ['query' => $query_options]),
          ];
          break;

        case 'internal':
          $build['task_console_table'][$queueID]['execute'] = [
            'data' => [
              '#type'  => 'operations',
              '#links' => [
                'maestro_link' => [
                  'title' => $this->t($link),
                  'url'   => Url::fromUserInput($handler, ['query' => $query_options]),
                ],
              ],
            ],
          ];

          //Let's call a hook here to let people change the name of the link to execute the task if they so choose to do so
          \Drupal::moduleHandler()
            ->invokeAll('maestro_task_console_interactive_link_alter', [
              &$link,
              $taskTemplate,
              $queueRecord,
              $templateMachineName,
            ]);
          break;

        case 'function':
          //Let's call a hook here to let people change the name of the link to execute the task if they so choose to do so
          \Drupal::moduleHandler()
            ->invokeAll('maestro_task_console_interactive_link_alter', [
              &$link,
              $taskTemplate,
              $queueRecord,
              $templateMachineName,
            ]);

          if ($use_modal) {
            $query_options += ['modal' => 'modal'];
            $links[$link] = [
              'title'      => $this->t($link),
              'url'        => Url::fromRoute('maestro.execute', $query_options),
              'attributes' => [
                'class'               => ['use-ajax'],
                'data-dialog-type'    => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                ]),
              ],
            ];
          }
          else {
            $query_options += ['modal' => 'notmodal'];
            $links[$link] = [
              'title' => $this->t($link),
              'url'   => Url::fromRoute('maestro.execute', $query_options),
              'attributes' => [
                'target' => '_blank'
              ]
            ];
          }

          $build['task_console_table'][$queueID]['execute'] = [
            'data' => [
              '#type'  => 'operations',
              '#links' => $links,
            ],
          ];

          break;

        default:
          $build['task_console_table'][$queueID]['execute'] = [
            '#plain_text' => $this->t('Invalid Link'),
          ];
      }

      /*
       * Provide your own execution links here if you wish
       */
      \Drupal::moduleHandler()
        ->invokeAll('maestro_task_console_alter_execution_link', [
          &$build['task_console_table'][$queueID]['execute'],
          $taskTemplate,
          $queueRecord,
          $templateMachineName,
        ]);


      $build['task_console_table'][$queueID]['expand'] = [
        '#wrapper_attributes' => ['class' => ['maestro-expand-wrapper']],
        '#plain_text'         => '',
      ];

      $var_workflow_stage_count = intval(MaestroEngine::getProcessVariable('workflow_timeline_stage_count', $processID));
      //if the show details is on OR the status bar is on, we'll show the toggler
      if ((isset($template->show_details) && $template->show_details) || (isset($template->default_workflow_timeline_stage_count) && intval($template->default_workflow_timeline_stage_count) > 0 && $var_workflow_stage_count > 0)) {
        //Provide details expansion column.  Clicking on it will show the status and/or the task detail information via ajax

        $build['task_console_table'][$queueID]['expand'] = [
          '#wrapper_attributes' => [
            'class' => [
              'maestro-expand-wrapper',
              'maestro-status-toggle-' . $queueID,
            ],
          ],
          '#attributes'         => [
            'class' => ['maestro-timeline-status', 'maestro-status-toggle'],
            'title' => $this->t('Open Details'),
          ],
          '#type'               => 'link',
          '#id'                 => 'maestro-id-ajax-' . $queueID,
          '#url'                => Url::fromRoute('maestro_taskconsole.status_ajax_open', [
            'processID' => $processID,
            'queueID'   => $queueID,
          ]),
          '#title'              => $this->t('Open Details'),
          '#ajax'               => [
            'progress' => [
              'type'    => 'throbber',
              'message' => NULL,
            ],
          ],
        ];

        $build['task_console_table'][$queueID . '_ajax']['#attributes']['id'] = $queueID . '_ajax';  //gives the <tr> tag an ID we can target
        $build['task_console_table'][$queueID . '_ajax']['#attributes']['class'] = ['maestro-ajax-row'];
        $build['task_console_table'][$queueID . '_ajax']['task'] = [
          '#wrapper_attributes' => ['colspan' => count($build['task_console_table'][$queueID])],
          '#prefix'             => '<div id="maestro-ajax-' . $queueID . '">',
          '#suffix'             => '</div>',
        ];
      }
    }
    $build['#attached']['library'][] = 'maestro_taskconsole/maestro_taskconsole_css';
    $build['#attached']['library'][] = 'maestro/maestro-engine-css'; //css for the status bar
    $build['#attached']['drupalSettings'] = [
      'baseURL' => base_path(),
    ];

    return $build;
  }
}
