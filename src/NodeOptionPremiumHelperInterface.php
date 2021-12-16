<?php

namespace Drupal\nopremium;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for API and helper methods.
 */
interface NodeOptionPremiumHelperInterface {

  /**
   * Checks if the given user has full access to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check for premium access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The operating account.
   */
  public function hasFullAccess(ContentEntityInterface $entity, AccountInterface $account);

}
