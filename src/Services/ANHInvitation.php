<?php

namespace Drupal\anh_maestro\Services;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Class SendInvitation
 *
 * @package Drupal\anh_maestro
 */
class ANHInvitation {

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  protected $userEmail;

  protected $userId = NULL;

  protected $userLangcode;

  protected $nodeId = NULL;

  protected $webformSubmissionId = NULL;

  /**
   * @var string
   */
  private $token;

  public function __construct(MailManagerInterface $mailManager, EntityTypeManager $entityTypeManager) {
    $this->mailManager = $mailManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  public function setData($userId, $webformSubmissionId) {
    $this->webformSubmissionId = $webformSubmissionId;
    $this->userId = $userId;

    $user = $this->entityTypeManager->getStorage('user')->load($this->userId);
    $webformSubmission = WebformSubmission::load($this->webformSubmissionId);
    $webformSubmissionBundle = $webformSubmission->bundle();
    $webformSubmissionData = $webformSubmission->getData();

    switch ($webformSubmissionBundle) {
      case "academy_weekly_abstract" :
        if (isset($webformSubmissionData["academy_week"]) && !is_null($webformSubmissionData["academy_week"]) && !empty($webformSubmissionData["academy_week"])) {
          $this->nodeId = $webformSubmissionData["academy_week"];
        }
        break;
      case "grant_concept_memo_submission":
        if (isset($webformSubmissionData["parent_node"]) && !is_null($webformSubmissionData["parent_node"]) && !empty($webformSubmissionData["parent_node"])) {
          $this->nodeId = $webformSubmissionData["parent_node"];
        }
        break;
    }

    if (!is_null($this->nodeId) && $user && $webformSubmission) {
      $this->userEmail = $user->getEmail();
      $this->userId = $user->id();
      $this->userLangcode = $user->getPreferredLangcode();

      $this->generateToken();
      return TRUE;
    }

    return FALSE;
  }

  private function generateToken() {
    if (!is_null($this->userId) && !is_null($this->webformSubmissionId) && !is_null($this->nodeId)) {
      $data = $this->userId . '-' . $this->webformSubmissionId . '-' . $this->nodeId;
      return $this->token = base64_encode($data);
    }
    else {
      return NULL;
    }
  }

  public function getToken() {
    return $this->token;
  }

  /**
   * Decode token
   *
   * @param $token
   *
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function decodeToken($token) {
    $data = base64_decode($token);
    list($userId, $webformSubmissionId, $nodeId) = explode('-', $data);
    $user = $this->entityTypeManager->getStorage('user')->load($userId);
    $webformSubmission = WebformSubmission::load($webformSubmissionId);
    $node = $this->entityTypeManager->getStorage('node')->load($nodeId);

    if ($user && $webformSubmission && $node) {
      return [
        "user"              => $user,
        "webformSubmission" => $webformSubmission,
        "node"              => $node,
      ];
    }
    return FALSE;
  }

  /**
   * @param $title
   * @param $message
   *
   * @return bool
   */
  public function sendMail($title, $message) {
    if (!is_null($this->userId) && !is_null($this->token)) {

      $params['title'] = $title;
      $params['message'] = $message;

      try {
        $result = $this->mailManager->mail('anh_maestro', 'send_invitation', $this->userEmail, $this->userLangcode, $params, NULL, TRUE);
        $message = t('An email notification has been sent to @email ', ['@email' => $this->userEmail]);
        \Drupal::logger('anh_maestro')->notice($message);
        return TRUE;
      } catch (\Exception $e) {
        \Drupal::logger('anh_maestro')->error($e->getMessage());
      }
    }
    // todo: Add throw Error
  }
}
