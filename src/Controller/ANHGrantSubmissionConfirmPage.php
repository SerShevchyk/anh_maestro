<?php

namespace Drupal\anh_maestro\Controller;

use Drupal\comment\CommentInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the ANH User module.
 */
class ANHGrantSubmissionConfirmPage extends ControllerBase {

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
   * @param \Drupal\comment\CommentInterface|null $comment
   *
   * @return array
   */
  public function render(CommentInterface $comment = NULL) {
    if ($comment && "grant" == $parentNode->bundle()) {
      $url = Url::fromUserInput('/grant-submission', ['attributes' => ['class' => 'link'], "query" => ["parentNodeId" => $parentNode->id()]]);
      $link = Link::fromTextAndUrl($this->t("Begin Application"), $url)->toString();

      $build = [
        '#theme'       => 'anh_maestro_grant_submission_page',
        '#title'       => $this->t("Grant concept memo Submission"),
        '#link'        => $link,
      ];

      return $build;
    }
    throw new NotFoundHttpException();
  }
}
