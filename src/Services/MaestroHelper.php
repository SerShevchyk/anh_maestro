<?php

namespace Drupal\anh_maestro\Services;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class MaestroHelper extends ControllerBase {

  private $processID;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  private $processRecord;

  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function init($processID) {
    $this->processID = $processID;
    $this->processRecord = MaestroEngine::getProcessEntryById($this->processID);
  }

  public function getInitiatorFullName() {
    $initiatorUid = $this->processRecord->get("initiator_uid")->value;
    $initiator = User::load($initiatorUid);
    return $initiator->get("field_user_salutation_title")->value . " " . $initiator->get("field_user_first_name")->value . " " . $initiator->get("field_user_last_name")->value;
  }

  public function getParentNodeTitle($nid) {
    return Node::load($nid)->label();
  }
}
