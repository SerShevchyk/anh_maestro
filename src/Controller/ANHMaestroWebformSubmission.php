<?php

namespace Drupal\anh_maestro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the ANH User module.
 */
class ANHMaestroWebformSubmission extends ControllerBase {

  public function __construct(AccountInterface $current_user, EntityTypeManager $entityTypeManager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns variable for User Congratulations Page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function render() {
    $build = [];
    $entity_type = 'webform_submission';
    $view_mode = 'default';

    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load(15);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $build["webform_submission"] = $view_builder->view($entity, $view_mode);

    return $build;
  }
}
