<?php

namespace Drupal\Tests\nopremium\Kernel\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\search_api\Kernel\Processor\ProcessorTestBase;
use Drupal\Tests\search_api\Kernel\ResultsTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the "Premium content" processor.
 *
 * @group premium
 *
 * @see \Drupal\nopremium\Plugin\search_api\processor\PremiumContent
 */
class PremiumContentTest extends ProcessorTestBase {

  use ResultsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'node',
    'field',
    'search_api',
    'search_api_db',
    'search_api_test',
    'comment',
    'text',
    'action',
    'system',
    'nopremium',
  ];

  /**
   * The nodes created for testing.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp('nopremium');

    $this->installConfig(['nopremium']);

    // Create a node type for testing.
    $type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $type->save();

    // Create anonymous user role.
    $role = Role::create([
      'id' => 'anonymous',
      'label' => 'anonymous',
    ]);
    $role->save();

    // Create a premium node.
    $values = [
      'status' => NodeInterface::PUBLISHED,
      'type' => 'article',
      'title' => 'Premium item',
      'premium' => TRUE,
    ];
    $this->nodes[0] = Node::create($values);
    $this->nodes[0]->save();

    // Create a non-premium node.
    $values = [
      'status' => NodeInterface::PUBLISHED,
      'type' => 'article',
      'title' => 'Free item',
      'premium' => FALSE,
    ];
    $this->nodes[1] = Node::create($values);
    $this->nodes[1]->save();

    // Create a node index.
    $datasources = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createDatasourcePlugins($this->index, [
        'entity:node',
      ]);
    $this->index->setDatasources($datasources);
    $this->index->save();

    // And index items.
    \Drupal::getContainer()->get('search_api.index_task_manager')->addItemsAll($this->index);
    $index_storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $index_storage->resetCache([$this->index->id()]);
    $this->index = $index_storage->load($this->index->id());
  }

  /**
   * Tests searching when premium content is accessible to all.
   */
  public function testQueryAccessAll() {
    $permissions = ['access content', 'view full premium content of any type'];
    user_role_grant_permissions('anonymous', $permissions);
    $this->index->reindex();
    $this->indexItems();
    $this->assertEquals(2, $this->index->getTrackerInstance()->getIndexedItemsCount(), '2 items indexed, as expected.');

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $result = $query->execute();

    $expected = [
      'node' => [0, 1],
    ];
    $this->assertResults($result, $expected);
  }

  /**
   * Tests searching when there's no access to premium nodes.
   */
  public function testQueryAccessNonPremium() {
    $permissions = ['access content'];
    user_role_grant_permissions('anonymous', $permissions);
    $this->index->reindex();
    $this->indexItems();
    $this->assertEquals(2, $this->index->getTrackerInstance()->getIndexedItemsCount(), '2 items indexed, as expected.');

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $result = $query->execute();

    $expected = [
      'node' => [1],
    ];
    $this->assertResults($result, $expected);
  }

  /**
   * Tests searching for own premium content.
   */
  public function testQueryAccessOwn() {
    // Create the user that will be passed into the query.
    $permissions = [
      'access content',
    ];
    $authenticated_user = $this->createUser($permissions);
    $uid = $authenticated_user->id();

    // Create a premium node owned by the created user.
    $values = [
      'status' => NodeInterface::PUBLISHED,
      'type' => 'article',
      'title' => 'foo',
      'uid' => $uid,
      'premium' => TRUE,
    ];
    $this->nodes[2] = Node::create($values);
    $this->nodes[2]->save();
    $this->indexItems();
    $this->assertEquals(3, $this->index->getTrackerInstance()->getIndexedItemsCount(), '3 items indexed, as expected.');

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $query->setOption('search_api_access_account', $authenticated_user);
    $result = $query->execute();

    $expected = [
      'node' => [1, 2],
    ];
    $this->assertResults($result, $expected);
  }

  /**
   * Creates a new user account.
   *
   * @param string[] $permissions
   *   The permissions to set for the user.
   *
   * @return \Drupal\user\UserInterface
   *   The new user object.
   */
  protected function createUser(array $permissions) {
    $role = Role::create(['id' => 'role', 'name' => 'Role test']);
    $role->save();
    user_role_grant_permissions($role->id(), $permissions);

    $values = [
      'uid' => 2,
      'name' => 'Test',
      'roles' => [$role->id()],
    ];
    $authenticated_user = User::create($values);
    $authenticated_user->enforceIsNew();
    $authenticated_user->save();

    return $authenticated_user;
  }

}
