<?php

namespace Drupal\Tests\nopremium\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group nopremium
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Module handler to ensure installed modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Reloads services used by this test.
   */
  protected function reloadServices() {
    $this->rebuildContainer();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests that the module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('nopremium'));
    $this->assertTrue($this->moduleInstaller->install(['nopremium']));
    $this->reloadServices();
    $this->assertTrue($this->moduleHandler->moduleExists('nopremium'));
  }

  /**
   * Tests if viewing a node after installation doesn't cause errors.
   */
  public function testInstallationWithExistingNode() {
    // First, install the modules 'node' and 'views'.
    $this->assertTrue($this->moduleInstaller->install(['node', 'views']));

    // Create a content type and a node.
    $this->drupalCreateContentType(['type' => 'foo']);
    $node = $this->drupalCreateNode([
      'type' => 'foo',
      'body'      => [
        [
          'value' => 'Lorem ipsum',
          'format' => filter_default_format(),
        ],
      ],
      'promote' => TRUE,
    ]);

    // Install nopremium.
    $this->assertTrue($this->moduleInstaller->install(['nopremium']));

    // View the node.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('Lorem ipsum');
    $this->assertSession()->pageTextNotContains('The full content of this page is available to premium users only.');

    // View the node in teaser mode.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains('Lorem ipsum');
    $this->assertSession()->pageTextNotContains('The full content of this page is available to premium users only.');
  }

}
