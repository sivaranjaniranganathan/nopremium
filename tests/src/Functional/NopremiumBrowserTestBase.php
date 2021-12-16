<?php

namespace Drupal\Tests\nopremium\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for Node Option Premium functional tests.
 */
abstract class NopremiumBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'nopremium',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The account that may configure Node Option Premium.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->admin = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
    ]);
  }

  /**
   * Reloads an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reload.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ $storageEntityStorageInterface */
    $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    return $storage->load($entity->id());
  }

}
