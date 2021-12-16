<?php

namespace Drupal\Tests\nopremium\Functional;

/**
 * Tests displaying nodes.
 *
 * @group nopremium
 */
class NodeViewTest extends NopremiumBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a content type and enable premium for this type.
    $this->drupalCreateContentType(['type' => 'foo']);

    // Don't show body on teaser.
    $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.foo.teaser')
      ->removeComponent('body')
      ->save();
  }

  /**
   * Creates a node with body.
   *
   * @param string $body
   *   The body text.
   * @param array $values
   *   (optional) An associative array of values for the node.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createNodeWithBodyValue($body, array $values = []) {
    $values += [
      'type' => 'foo',
      'body'      => [
        [
          'value' => $body,
          'format' => filter_default_format(),
        ],
      ],
    ];
    return $this->drupalCreateNode($values);
  }

  /**
   * Tests that the premium message is displayed for a premium node.
   */
  public function testViewPremiumNode() {
    // Create a premium node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum', [
      'premium' => TRUE,
    ]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextNotContains('Lorem ipsum');
    $this->assertSession()->pageTextContains('The full content of this page is available to premium users only.');

    // Ensure that there is no read more link, since we're on the full content
    // page already.
    $this->assertSession()->pageTextNotContains('Read more');

    // And ensure that there's no link to the node.
    $this->assertSession()->linkNotExists($node->label());
  }

  /**
   * Tests that the full content is displayed for a non-premium node.
   */
  public function testViewNonPremiumNode() {
    // Create a public node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum');

    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('Lorem ipsum');
    $this->assertSession()->pageTextNotContains('The full content of this page is available to premium users only.');
  }

  /**
   * Tests with specific content type message.
   */
  public function testSpecificContentTypeMessage() {
    // Change message for 'foo' content type.
    $this->config('nopremium.settings')
      ->set('messages', [
        'foo' => 'The foo message',
      ])
      ->save();

    // Create a premium node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum', [
      'premium' => TRUE,
    ]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextNotContains('Lorem ipsum');
    $this->assertSession()->pageTextContains('The foo message');
  }

}
