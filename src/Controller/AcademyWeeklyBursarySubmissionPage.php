<?php

namespace Drupal\anh_maestro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the ANH User module.
 */
class AcademyWeeklyBursarySubmissionPage extends ControllerBase {

  /**
   * @var \Drupal\node\NodeInterface
   */
  private $parentNode;

  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns variable for User Congratulations Page.
   *
   * @param null $parentWebformSubmissionId
   *
   * @return array
   */
  public function render($parentWebformSubmissionId = NULL) {
    if ($parentWebformSubmissionId) {
      $url = Url::fromUserInput('/academy-week-bursary', ['attributes' => ['class' => 'link'], "query" => ["sid" => $parentWebformSubmissionId]]);
      $link = Link::fromTextAndUrl($this->t("Begin Application"), $url)->toString();

      $build = [
        '#theme'       => 'anh_maestro_academy_weekly_bursary_page',
        '#title'       => $this->t("Academy Week Bursary Application"),
//        '#description' => $parentNode->get("field_grant_appdescription")->value,
        '#link'        => $link,
      ];

      return $build;
    }
    throw new NotFoundHttpException();
  }
}
