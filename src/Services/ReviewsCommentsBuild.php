<?php

namespace Drupal\anh_maestro\Services;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;

class ReviewsCommentsBuild extends ControllerBase {

  /**
   * @var array
   */
  private $commentsIds;

  private $processID;

  private $comments;

  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->commentsIds = [];
  }

  /**
   * Get comments ids by process id.
   *
   * @param $processID
   *
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCommentsIds($processID) {
    $this->processID = $processID;

    $this->commentsIds = $this->entityTypeManager
      ->getStorage('comment')
      ->getQuery('AND')
      ->condition('entity_id', $this->processID)
      ->condition('entity_type', 'maestro_process')
      ->sort('cid', 'DESC')
      ->execute();

    return $this->commentsIds;
  }

  public function getAbstractReviewCommentsDetails($processID) {
    try {
      if ($this->getCommentsIds($processID) && !is_null($this->commentsIds)) {
        $query = \Drupal::database()->select('comment', 'c');
        $query->leftJoin('comment__field_abstract_quality', 'cfaq', 'c.cid = cfaq.entity_id');
        $query->leftJoin('comment__field_feedback', 'cff', 'c.cid = cff.entity_id');
        $query->leftJoin('comment__field_originality', 'cfo', 'c.cid = cfo.entity_id');
        $query->leftJoin('comment__field_poster_presentation', 'cfpp', 'c.cid = cfpp.entity_id');
        $query->leftJoin('comment__field_relevance', 'cfr', 'c.cid = cfr.entity_id');
        $query->leftJoin('comment__field_scientific_rigour', 'cfsr', 'c.cid = cfsr.entity_id');
        $query->leftJoin('comment__field_webform_submission_id', 'cfwsi', 'c.cid = cfwsi.entity_id');
        $query->fields("cfaq", ["field_abstract_quality_value"]);
        $query->fields("cff", ["field_feedback_value"]);
        $query->fields("cfo", ["field_originality_value"]);
        $query->fields("cfpp", ["field_poster_presentation_value"]);
        $query->fields("cfr", ["field_relevance_value"]);
        $query->fields("cfsr", ["field_scientific_rigour_value"]);
        $query->fields("cfwsi", ["field_webform_submission_id_value"]);
        $query->condition('c.comment_type', 'abstract_review');
        $query->condition('c.cid', $this->commentsIds, "IN");
        $this->comments = $query->distinct()->execute()->fetchAll();

        return $this->comments;
      }
    } catch (InvalidPluginDefinitionException $e) {
      return NULL;
    } catch (PluginNotFoundException $e) {
      return NULL;
    }
    return NULL;
  }

  public function getGrantPeerReviewsDetails($processID) {
    try {
      if ($this->getCommentsIds($processID) && !is_null($this->commentsIds)) {
        $query = \Drupal::database()->select('comment', 'c');
        $query->leftJoin('comment__field_collaborative_approaches', 'cfca', 'c.cid = cfca.entity_id');
        $query->leftJoin('comment__field_development_relevance', 'cfdr', 'c.cid = cfdr.entity_id');
        $query->leftJoin('comment__field_innovation', 'cfi', 'c.cid = cfi.entity_id');
        $query->leftJoin('comment__field_scientific_excellence', 'cfse', 'c.cid = cfse.entity_id');
        $query->leftJoin('comment__field_translational_value', 'cftv', 'c.cid = cftv.entity_id');
        $query->leftJoin('comment__field_webform_submission_id', 'cfwsi', 'c.cid = cfwsi.entity_id');
        $query->leftJoin('comment__field_feedback', 'cff', 'c.cid = cff.entity_id');
        $query->fields("cfca", ["field_collaborative_approaches_value"]);
        $query->fields("cff", ["field_feedback_value"]);
        $query->fields("cfdr", ["field_development_relevance_value"]);
        $query->fields("cfi", ["field_innovation_value"]);
        $query->fields("cfse", ["field_scientific_excellence_value"]);
        $query->fields("cftv", ["field_translational_value_value"]);
        $query->fields("cfwsi", ["field_webform_submission_id_value"]);
        $query->condition('c.comment_type', 'grant_peer_review');
        $query->condition('c.cid', $this->commentsIds, "IN");
        $this->comments = $query->distinct()->execute()->fetchAll();

        return $this->comments;
      }
    } catch (InvalidPluginDefinitionException $e) {
      return NULL;
    } catch (PluginNotFoundException $e) {
      return NULL;
    }
    return NULL;
  }

  public function getOptionsLabel($key) {
    $options = [
      1 => $this->t('Not competitive'),
      2 => $this->t('Satisfactory'),
      3 => $this->t('Good'),
      4 => $this->t('Excellent'),
      5 => $this->t('Outstanding'),
    ];

    return isset($options[$key]) ? $options[$key] : NULL;
  }
}
