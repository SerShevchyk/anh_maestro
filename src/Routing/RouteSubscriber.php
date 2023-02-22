<?php

namespace Drupal\anh_maestro\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('maestro_taskconsole.taskconsole')) {
      $route->setDefault('_controller', '\Drupal\anh_maestro\Controller\ANHMaestroTaskConsoleController::getTasks');
    }
  }
}
