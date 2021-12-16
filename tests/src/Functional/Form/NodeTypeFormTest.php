<?php

namespace Drupal\Tests\nopremium\Functional\Form;

use Drupal\Tests\nopremium\Functional\NopremiumBrowserTestBase;

/**
 * Tests configuring the settings on the node type form.
 *
 * @group nopremium
 */
class NodeTypeFormTest extends NopremiumBrowserTestBase {

  /**
   * Tests setting premimum for new content type.
   */
  public function testWithNewContentType() {
    $this->drupalLogin($this->admin);

    // Enable premium on new content type.
    $edit = [
      'name' => 'Foo',
      'type' => 'foo',
      'options[premium]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/add', $edit, 'Save content type');

    // Assert that premium was enabled for this content type.
    $config = $this->config('core.base_field_override.node.foo.premium');
    $this->assertEquals(TRUE, $config->get('default_value')[0]['value']);
  }

  /**
   * Tests setting premium for existing content type.
   */
  public function testWithExistingContentType() {
    $this->drupalLogin($this->admin);
    $this->drupalCreateContentType(['type' => 'foo']);

    // Assert that the content type is not premium yet.
    $config = $this->config('core.base_field_override.node.foo.premium');
    $this->assertNull($config->get('default_value'));

    // Enable premium on this content type.
    $edit = [
      'options[premium]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/foo', $edit, 'Save content type');

    // Assert that premium was enabled for this content type.
    $config = $this->config('core.base_field_override.node.foo.premium');
    $this->assertEquals(TRUE, $config->get('default_value')[0]['value']);
  }

}
