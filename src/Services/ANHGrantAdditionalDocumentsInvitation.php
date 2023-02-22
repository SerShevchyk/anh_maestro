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
class ANHGrantAdditionalDocumentsInvitation extends ANHInvitation {

  /**
   * @var string
   */
  private $token;

  public function __construct(MailManagerInterface $mailManager, EntityTypeManager $entityTypeManager) {
    $this->mailManager = $mailManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  public function setData($userId, $parentNodeId) {
    $this->nodeId = $parentNodeId;
    $this->userId = $userId;
    $user = $this->entityTypeManager->getStorage('user')->load($this->userId);

    if (!is_null($this->nodeId) && $user) {
      $this->userEmail = $user->getEmail();
      $this->userId = $user->id();
      $this->userLangcode = $user->getPreferredLangcode();

      $this->generateToken();
      return TRUE;
    }

    return FALSE;
  }

  private function generateToken() {
    if (!is_null($this->userId) && !is_null($this->nodeId)) {
      $data = $this->userId . '-' . $this->nodeId;
      return $this->token = base64_encode($data);
    }
    else {
      return NULL;
    }
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
    list($userId, $nodeId) = explode('-', $data);

    if ($userId && $nodeId && $userId == \Drupal::currentUser()->id()) {
      $track = $this->entityTypeManager->getStorage('anh_track')->loadByProperties(['user' => $userId, "grant" => $nodeId]);

      if (!empty($track)) {
        $user = $this->entityTypeManager->getStorage('user')->load($userId);
        $node = $this->entityTypeManager->getStorage('node')->load($nodeId);

        if ($user && $node) {
          return [
            "user"              => $user,
            "node"              => $node,
          ];
        }
      }
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

  public function getToken() {
    return $this->token;
  }
}
