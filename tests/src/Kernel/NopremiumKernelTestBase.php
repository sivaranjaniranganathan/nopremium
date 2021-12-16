<?php

namespace Drupal\Tests\nopremium\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Provides a base class for nopremium kernel tests.
 */
abstract class NopremiumKernelTestBase extends EntityKernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'nopremium',
    'field',
    'text',
    'filter',
  ];

  /**
   * The node type to test with.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install database schemes.
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['system', 'nopremium', 'field', 'filter', 'node']);

    // Create a content type.
    $this->nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->nodeType->save();
    node_add_body_field($this->nodeType);
  }

}
