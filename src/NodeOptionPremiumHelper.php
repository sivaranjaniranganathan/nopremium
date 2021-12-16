<?php

namespace Drupal\nopremium;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * API and helper methods.
 */
class NodeOptionPremiumHelper implements NodeOptionPremiumHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function hasFullAccess(ContentEntityInterface $entity, AccountInterface $account) {
    if (!$entity->hasField('premium')) {
      // Entity has no premium field, so no restricted access.
      return TRUE;
    }

    if (empty($entity->premium->value)) {
      // This is not a premium entity. Full access granted.
      return TRUE;
    }

    // Check permissions.
    if ($account->hasPermission('administer nodes')
      || $account->hasPermission('view full premium content of any type')
      || $account->hasPermission('view full ' . $entity->bundle() . ' premium content')
      || $entity->access('update', $account)
    ) {
      return TRUE;
    }

    // Check if the account owns the entity.
    if ($entity instanceof EntityOwnerInterface
      && $account->isAuthenticated()
      && $account->id() == $entity->getOwnerId()
    ) {
      return TRUE;
    }

    // In all other cases, the user hasn't full access to the entity.
    return FALSE;
  }

}
