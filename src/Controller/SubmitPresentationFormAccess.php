<?php

namespace Drupal\anh_maestro\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Messenger\Messenger;

class SubmitPresentationFormAccess extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * SubmitPresentationFormAccess constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(EntityTypeManager $entityTypeManager, Messenger $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  public function access($token) {
    if ($token && \Drupal::service('anh_maestro.anh_invitation')->decodeToken($token)) {
      return AccessResult::allowed();
    }
    $this->messenger->addError($this->t('Invalid Token'));
    return AccessResult::forbidden();
  }
}
