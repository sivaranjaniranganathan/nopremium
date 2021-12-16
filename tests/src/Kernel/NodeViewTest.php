<?php

namespace Drupal\Tests\nopremium\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;

/**
 * Tests displaying nodes.
 *
 * @group nopremium
 */
class NodeViewTest extends NopremiumKernelTestBase {

  /**
   * The node view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $content_admin_user = $this->createUser(['uid' => 2], ['administer nodes']);

    // Don't show body on teaser.
    $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.article.teaser')
      ->removeComponent('body')
      ->save();

    $this->viewBuilder = $this->entityTypeManager->getViewBuilder('node');
    $this->renderer = $this->container->get('renderer');
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
      'type' => 'article',
      'body'      => [
        [
          'value' => $body,
          'format' => filter_default_format(),
        ],
      ],
      'uid' => 2,
    ];
    return $this->createNode($values);
  }

  /**
   * Creates a view mode and display.
   *
   * @param string $view_mode
   *   The view mode to create.
   */
  protected function createViewModeAndDisplay($view_mode) {
    EntityViewMode::create([
      'id' => 'node.' . $view_mode,
      'targetEntityType' => 'node',
    ])->save();
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => $view_mode,
      'status' => TRUE,
    ])->setComponent('body')
      ->save();
  }

  /**
   * Tests if premium message is shown on full view mode by default.
   */
  public function testViewPremiumNode() {
    // Create a premium node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum', [
      'premium' => TRUE,
    ]);

    $build = $this->viewBuilder->view($node, 'full');
    $output = (string) $this->renderer->renderPlain($build);
    $this->assertStringContainsString('The full content of this page is available to premium users only.', $output);
    $this->assertStringNotContainsString('Lorem ipsum', $output);
  }

  /**
   * Tests if premium message is shown on custom view mode by default.
   */
  public function testWithCustomViewMode() {
    $this->createViewModeAndDisplay('foo');

    // Create a premium node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum', [
      'premium' => TRUE,
    ]);

    $build = $this->viewBuilder->view($node, 'foo');
    $output = (string) $this->renderer->renderPlain($build);
    $this->assertStringContainsString('The full content of this page is available to premium users only.', $output);
    $this->assertStringNotContainsString('Lorem ipsum', $output);
  }

  /**
   * Tests that a read more link is shown in teasers for premium nodes.
   */
  public function testViewPremiumNodeInTeaserViewMode() {
    // Create a premium node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum', [
      'premium' => TRUE,
    ]);

    $build = $this->viewBuilder->view($node, 'teaser');
    $output = (string) $this->renderer->renderPlain($build);

    // And ensure that there is a read more link.
    $this->assertStringContainsString('Read more', $output);
  }

  /**
   * Tests displaying a non-premium node on custom view mode.
   */
  public function testViewNonPremiumNodeWithCustomViewMode() {
    $this->createViewModeAndDisplay('foo');

    // Create a public node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum');

    $build = $this->viewBuilder->view($node, 'foo');
    $output = (string) $this->renderer->renderPlain($build);
    $this->assertStringNotContainsString('The full content of this page is available to premium users only.', $output);
    $this->assertStringContainsString('Lorem ipsum', $output);
  }

  /**
   * Tests view modes setting.
   *
   * Tests if enabling premium can be disabled for some view modes.
   */
  public function testViewModesSetting() {
    // Create two custom view modes and displays.
    $this->createViewModeAndDisplay('foo');
    $this->createViewModeAndDisplay('bar');

    // Disable premium for 'bar' view mode.
    $this->config('nopremium.settings')
      ->set('view_modes', [
        'foo' => 'foo',
      ])
      ->save();

    // Create a premium node.
    $node = $this->createNodeWithBodyValue('Lorem ipsum', [
      'premium' => TRUE,
    ]);

    // Ensure that the premium message is still displayed on the 'foo' view
    // mode.
    $build = $this->viewBuilder->view($node, 'foo');
    $output = (string) $this->renderer->renderPlain($build);
    $this->assertStringContainsString('The full content of this page is available to premium users only.', $output);
    $this->assertStringNotContainsString('Lorem ipsum', $output);

    // But *not* on the 'bar' view mode.
    $build = $this->viewBuilder->view($node, 'bar');
    $output = (string) $this->renderer->renderPlain($build);
    $this->assertStringNotContainsString('The full content of this page is available to premium users only.', $output);
    $this->assertStringContainsString('Lorem ipsum', $output);
  }

}
