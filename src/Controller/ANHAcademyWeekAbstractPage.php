<?php

namespace Drupal\anh_maestro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the ANH User module.
 */
class ANHAcademyWeekAbstractPage extends ControllerBase {

  /**
   * @var \Drupal\node\NodeInterface
   */
  private $parentNode;

  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
//    $this->parentNode = $parentNode;
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
   * @param \Drupal\node\NodeInterface|null $parentNode
   *
   * @return array
   */
  public function render(NodeInterface $parentNode = NULL) {
    if ($parentNode && "academy_week" == $parentNode->bundle()) {
      $url = Url::fromUserInput('/form/academy-weekly-abstract', ['attributes' => ['class' => 'link'], "query" => ["parentNode" => $parentNode->id()]]);
      $link = Link::fromTextAndUrl($this->t("Begin Application"), $url)->toString();

      $media_field = $parentNode->get('field_event_image')->getString(); // Get media ID from your field.
      $media_entity_load = Media::load($media_field); // Loading media entity.
      $uri = $media_entity_load->field_media_image->entity->getFileUri(); // It is for image media.
      $media_url = file_create_url($uri); // Here you will get URL of uploaded image.

      $build = [
        '#theme'       => 'anh_maestro_academy_week_abstract_submission_page',
        '#title'       => $this->t("Academy Week Abstract Submission"),
        '#description' => $parentNode->field_aw_abstractappdescription->value,
        '#image' => $media_url,
        '#link'        => $link,
      ];

      return $build;
    }
    throw new NotFoundHttpException();
  }
}
