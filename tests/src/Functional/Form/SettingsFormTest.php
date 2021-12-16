<?php

namespace Drupal\Tests\nopremium\Functional\Form;

use Drupal\Tests\nopremium\Functional\NopremiumBrowserTestBase;

/**
 * Tests configuring the module.
 *
 * @group nopremium
 */
class SettingsFormTest extends NopremiumBrowserTestBase {

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an user with admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);
  }

  /**
   * Tests that an anonymous user cannot access the settings page.
   */
  public function testNoAccess() {
    $this->drupalGet('/admin/config/content/nopremium');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests saving settings form without changing anything.
   */
  public function testNew() {
    $this->drupalLogin($this->adminUser);

    // Test submitting form without changing anything.
    $this->drupalPostForm('/admin/config/content/nopremium', [], 'Save configuration');

    $expected = [
      'default_message' => 'The full content of this page is available to premium users only.',
      'view_modes' => [],
      'teaser_view_mode' => 'teaser',
    ];
    $config = $this->config('nopremium.settings')->getRawData();
    unset($config['_core']);
    $this->assertSame($expected, $config);
  }

  /**
   * Tests changing configuration.
   */
  public function testSetSettings() {
    $this->drupalLogin($this->adminUser);

    // Change some config.
    $edit = [
      'default_message' => 'This is premium content.',
      'view_modes[full]' => 'full',
      'view_modes[rss]' => 'rss',
      'view_modes[teaser]' => '',
      'teaser_view_mode' => 'rss',
    ];
    $this->drupalPostForm('/admin/config/content/nopremium', $edit, 'Save configuration');

    $expected = [
      'default_message' => 'This is premium content.',
      'view_modes' => [
        'full' => 'full',
        'rss' => 'rss',
      ],
      'teaser_view_mode' => 'rss',
    ];
    $config = $this->config('nopremium.settings')->getRawData();
    unset($config['_core']);
    $this->assertSame($expected, $config);
  }

  /**
   * Tests saving settings form with a few content types.
   */
  public function testNewWithContentTypes() {
    $this->drupalLogin($this->adminUser);

    // Create two content types.
    $this->drupalCreateContentType(['type' => 'bar']);
    $this->drupalCreateContentType(['type' => 'foo']);

    // Test submitting form without changing anything.
    $this->drupalPostForm('/admin/config/content/nopremium', [], 'Save configuration');

    $expected = [
      'default_message' => 'The full content of this page is available to premium users only.',
      'view_modes' => [],
      'teaser_view_mode' => 'teaser',
      'messages' => [
        'bar' => '',
        'foo' => '',
      ],
    ];
    $config = $this->config('nopremium.settings')->getRawData();
    unset($config['_core']);
    $this->assertSame($expected, $config);
  }

  /**
   * Tests changing configuration.
   */
  public function testSetSettingsWithContentTypes() {
    $this->drupalLogin($this->adminUser);

    // Create two content types.
    $this->drupalCreateContentType(['type' => 'bar']);
    $this->drupalCreateContentType(['type' => 'foo']);

    // Change some config.
    $edit = [
      'default_message' => 'This is premium content.',
      'message_bar' => 'This is the message for the bar content type.',
      'message_foo' => 'This is the message for the foo content type.',
    ];
    $this->drupalPostForm('/admin/config/content/nopremium', $edit, 'Save configuration');

    $expected = [
      'default_message' => 'This is premium content.',
      'view_modes' => [],
      'teaser_view_mode' => 'teaser',
      'messages' => [
        'bar' => 'This is the message for the bar content type.',
        'foo' => 'This is the message for the foo content type.',
      ],
    ];
    $config = $this->config('nopremium.settings')->getRawData();
    unset($config['_core']);
    $this->assertSame($expected, $config);
  }

  /**
   * Tests editing existing configuration.
   */
  public function testEditSettings() {
    $this->drupalLogin($this->adminUser);

    // Create two content types.
    $this->drupalCreateContentType(['type' => 'bar']);
    $this->drupalCreateContentType(['type' => 'foo']);

    // Change some configuration programmatically.
    $this->config('nopremium.settings')
      ->set('default_message', 'The default premium message.')
      ->set('messages', [
        'foo' => 'The foo message',
      ])
      ->set('view_modes', [
        'full' => 'full',
        'rss' => 'rss',
      ])
      ->set('teaser_view_mode', 'rss')
      ->save();

    // Test submitting form without changing anything.
    $this->drupalPostForm('/admin/config/content/nopremium', [], 'Save configuration');

    // Assert that the configuration stayed the same.
    $expected = [
      'default_message' => 'The default premium message.',
      'view_modes' => [
        'full' => 'full',
        'rss' => 'rss',
      ],
      'teaser_view_mode' => 'rss',
      'messages' => [
        'foo' => 'The foo message',
        'bar' => '',
      ],
    ];
    $config = $this->config('nopremium.settings')->getRawData();
    unset($config['_core']);
    $this->assertSame($expected, $config);
  }

  /**
   * Tests settings form with token module enabled.
   */
  public function testWithTokenModule() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/nopremium');
    $this->assertSession()->pageTextContains('Enable the Token module to view the available token browser.');
    $this->assertSession()->linkExists('Token module');

    // Now install the token module.
    $this->container->get('module_installer')->install(['token'], TRUE);
    $this->drupalGet('/admin/config/content/nopremium');
    $this->assertSession()->linkExists('Browse available tokens.');
  }

}
