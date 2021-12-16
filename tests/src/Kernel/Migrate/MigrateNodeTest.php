<?php

namespace Drupal\Tests\nopremium\Kernel\Migrate\d7;

use Drupal\Tests\file\Kernel\Migrate\d7\FileMigrationSetupTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\Node;

/**
 * Tests node migration.
 *
 * @group nopremium
 */
class MigrateNodeTest extends MigrateDrupal7TestBase {

  use FileMigrationSetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'comment',
    'datetime',
    'image',
    'language',
    'link',
    'menu_ui',
    'node',
    'nopremium',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add Nopremium D7 data.
    $this->loadFixture(__DIR__ . '/../../../fixtures/nopremium_d7.php');

    $this->fileMigrationSetup();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['nopremium']);

    $this->migrateUsers();
    $this->migrateFields();
    $this->executeMigrations([
      'd7_nopremium_node',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo() {
    return [
      'path' => 'public://sites/default/files/cube.jpeg',
      'size' => '3620',
      'base_path' => 'public://',
      'plugin_id' => 'd7_file',
    ];
  }

  /**
   * Test node migration from Drupal 7 to 8.
   */
  public function testMigrateNode() {
    // Assert that node 129 is premium.
    $node = Node::load(129);
    $this->assertEquals(1, $node->premium->value);

    // And assert that node 1 isn't.
    $node = Node::load(1);
    $this->assertEquals(0, $node->premium->value);
  }

}
