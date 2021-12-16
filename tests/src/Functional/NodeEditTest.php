<?php

namespace Drupal\Tests\nopremium\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests editing nodes.
 *
 * @group nopremium
 */
class NodeEditTest extends NopremiumBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests that an editor by default may not alter the node's premium setting.
   */
  public function testNodeFormWithoutOverridePremiumPermission() {
    // Create a user who may create/edit articles, but may *not* set premium.
    $editor = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
    ]);
    $this->drupalLogin($editor);

    // Go to node create page and assert that there's no premium field there.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('premium[value]');

    // Check also for an existing node.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet(sprintf('node/%s/edit', $node->id()));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Edit Article');
    $this->assertSession()->fieldNotExists('premium[value]');
  }

  /**
   * Tests with an editor that may set premium for any content type.
   */
  public function testNodeFormWithOverridePremiumAnyContentTypePermission() {
    // Create a user who may create/edit articles and set premium for all
    // content types.
    $editor = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'override premium option of any content type',
    ]);
    $this->drupalLogin($editor);

    // Create a node and override premium setting.
    $edit = [
      'title[0][value]' => 'Lorem ipsum',
      'premium[value]' => 1,
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');

    // Assert that a node was created and premium is enabled.
    $node = Node::load(1);
    $this->assertEquals(1, $node->premium->value);

    // Edit an existing node and enable premium there.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->assertEquals(0, $node->premium->value);
    $edit = [
      'premium[value]' => 1,
    ];
    $this->drupalPostForm(sprintf('node/%s/edit', $node->id()), $edit, 'Save');

    // Assert that the node is now premium.
    $node = $this->reloadEntity($node);
    $this->assertEquals(1, $node->premium->value);
  }

  /**
   * Tests with an editor that may only set premium for articles.
   */
  public function testNodeFormWithOverridePremiumArticlePermission() {
    // Create a second node type.
    $this->drupalCreateContentType(['type' => 'foo', 'name' => 'Foo']);

    // Create a user who may create/edit articles and foo nodes, but may only
    // set premium for articles.
    $editor = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'create foo content',
      'edit any foo content',
      'override article premium content',
    ]);
    $this->drupalLogin($editor);

    // Create an article and override premium setting.
    $edit = [
      'title[0][value]' => 'Lorem ipsum',
      'premium[value]' => 1,
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');

    // Assert that a node was created and premium is enabled.
    $node = Node::load(1);
    $this->assertEquals(1, $node->premium->value);

    // Edit an existing article and enable premium there.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->assertEquals(0, $node->premium->value);
    $edit = [
      'premium[value]' => 1,
    ];
    $this->drupalPostForm(sprintf('node/%s/edit', $node->id()), $edit, 'Save');

    // Assert that the node is now premium.
    $node = $this->reloadEntity($node);
    $this->assertEquals(1, $node->premium->value);

    // Also check if this user may *not* edit the premium setting for other node
    // types. Go to the node create page for the foo content type and assert
    // that there's no premium field there.
    $this->drupalGet('node/add/foo');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('premium[value]');

    // Check also for an existing foo node.
    $node = $this->drupalCreateNode(['type' => 'foo']);
    $this->drupalGet(sprintf('node/%s/edit', $node->id()));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Edit Foo');
    $this->assertSession()->fieldNotExists('premium[value]');
  }

}
