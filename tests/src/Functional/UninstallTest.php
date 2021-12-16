<?php

namespace Drupal\Tests\nopremium\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group nopremium
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['nopremium'];

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
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that the Node Option Premium module has been installed.
    $this->assertTrue($this->moduleHandler->moduleExists('nopremium'));

    // Create a content type.
    $this->drupalCreateContentType([
      'type' => 'foo',
    ]);
    // Set default value for premium to "TRUE" for this content type.
    $fields = $this->container->get('entity_field.manager')->getFieldDefinitions('node', 'foo');
    $fields['premium']->getConfig('foo')->setDefaultValue(TRUE)->save();

    // The config ID that is expected to exist now.
    $config_id = 'core.base_field_override.node.foo.premium';

    // Assert that base_field_override config was created.
    $this->assertTrue($this->container->get('config.storage')->exists($config_id), "Config $config_id has been created.");

    // Uninstall Node Option Premium.
    $this->moduleInstaller->uninstall(['nopremium']);
    $this->assertFalse($this->moduleHandler->moduleExists('nopremium'));

    // Assert that config is cleaned up as well.
    $this->assertFalse($this->container->get('config.storage')->exists($config_id), "Config $config_id no longer exists.");
  }

  /**
   * Tests that the module can be reinstalled.
   */
  public function testReinstall() {
    $this->moduleInstaller->uninstall(['nopremium']);
    $this->assertTrue($this->moduleInstaller->install(['nopremium']));
    $this->reloadServices();
    $this->assertTrue($this->moduleHandler->moduleExists('nopremium'));
  }

}
