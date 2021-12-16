<?php

namespace Drupal\Tests\nopremium\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\nopremium\NodeOptionPremiumHelper;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\nopremium\NodeOptionPremiumHelper
 *
 * @group nopremium
 */
class NodeOptionPremiumHelperTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\nopremium\NodeOptionPremiumHelper
   */
  protected $helper;

  /**
   * The entity to test with.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The account to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the service to test.
    $this->helper = new NodeOptionPremiumHelper();

    // Create the entity to test with.
    $this->entity = $this->prophesize(ContentEntityInterface::class);
    // The entity's bundle is 'foo'.
    $this->entity->bundle()->willReturn('foo');

    // Create the account to test with.
    $this->account = $this->prophesize(AccountInterface::class);
  }

  /**
   * Creates a prophesized node.
   *
   * @return \Prophecy\Prophecy\ProphecyInterface|\Drupal\node\NodeInterface
   *   The mocked node.
   */
  protected function createPremiumNode() {
    // Pick an entity type that implements both ContentEntityInterface and
    // EntityOwnerInterface.
    $node = $this->prophesize(NodeInterface::class);

    // Configure that the entity does have a premium field.
    $node->hasField('premium')->willReturn(TRUE);

    // The entity's bundle is 'foo'.
    $node->bundle()->willReturn('foo');

    return $node;
  }

  /**
   * Tests full access on an entity without a premium field.
   *
   * @covers ::hasFullAccess
   */
  public function testHasFullAccessOnEntityWithNoPremiumField() {
    // Configure that the entity has *no* premium field.
    $this->entity->hasField('premium')->willReturn(FALSE);

    $this->assertTrue($this->helper->hasFullAccess($this->entity->reveal(), $this->account->reveal()));
  }

  /**
   * Tests full access on an entity that is not premium.
   *
   * @covers ::hasFullAccess
   */
  public function testHasFullAccessOnNonPremiumEntity() {
    // Configure that the entity does have a premium field.
    $this->entity->hasField('premium')->willReturn(TRUE);

    // Configure the entity to be non-premium.
    $entity = $this->entity->reveal();
    $entity->premium = new \stdClass();
    $entity->premium->value = FALSE;

    $this->assertTrue($this->helper->hasFullAccess($entity, $this->account->reveal()));
  }

  /**
   * Tests full access on a premium entity with certain permissions.
   *
   * @param bool $expected
   *   Whether or not access is expected.
   * @param string $permission
   *   The permission to check.
   *
   * @covers ::hasFullAccess
   * @dataProvider accessPermissionsProvider
   */
  public function testHasFullAccessWithPermissions($expected, $permission) {
    // Configure that the entity does have a premium field.
    $this->entity->hasField('premium')->willReturn(TRUE);

    // The account has no update access for this entity.
    $this->entity->access('update', $this->account->reveal())->willReturn(FALSE);

    // Configure the entity to be premium.
    $entity = $this->entity->reveal();
    $entity->premium = new \stdClass();
    $entity->premium->value = TRUE;

    $this->account->hasPermission(Argument::type('string'))->will(function ($args) use ($permission) {
      return ($args[0] === $permission);
    });

    $this->assertSame($expected, $this->helper->hasFullAccess($entity, $this->account->reveal()));
  }

  /**
   * Data provider for ::testHasFullAccessWithPermissions().
   *
   * @return array
   *   A list of cases.
   */
  public function accessPermissionsProvider() {
    return [
      [FALSE, 'access content'],
      [TRUE, 'administer nodes'],
      [TRUE, 'view full premium content of any type'],
      [TRUE, 'view full foo premium content'],
      [FALSE, 'view full bar premium content'],
    ];
  }

  /**
   * Tests that an user who may edit the entity, has access.
   *
   * @covers ::hasFullAccess
   */
  public function testHasFullAccessAsEditor() {
    // Configure that the entity does have a premium field.
    $this->entity->hasField('premium')->willReturn(TRUE);

    // The account has none of the permissions.
    $this->account->hasPermission(Argument::type('string'))->wilLReturn(FALSE);

    // The account does have update access for this entity.
    $this->entity->access('update', $this->account->reveal())->willReturn(TRUE);

    // Configure the entity to be premium.
    $entity = $this->entity->reveal();
    $entity->premium = new \stdClass();
    $entity->premium->value = TRUE;

    $this->assertTrue($this->helper->hasFullAccess($entity, $this->account->reveal()));
  }

  /**
   * Tests that the owner of an entity always has access.
   *
   * @covers ::hasFullAccess
   */
  public function testHasFullAccessAsOwner() {
    $node = $this->createPremiumNode();

    // The account has none of the permissions.
    $this->account->hasPermission(Argument::type('string'))->wilLReturn(FALSE);

    // The account is authenticated.
    $this->account->isAuthenticated()->willReturn(TRUE);

    // The entity's owner ID and the account ID are the same.
    $node->getOwnerId()->willReturn(4);
    $this->account->id()->willReturn(4);

    // The account has no update access for this entity.
    $node->access('update', $this->account->reveal())->willReturn(FALSE);

    // Configure the entity to be premium.
    $node = $node->reveal();
    $node->premium = new \stdClass();
    $node->premium->value = TRUE;

    $this->assertTrue($this->helper->hasFullAccess($node, $this->account->reveal()));
  }

  /**
   * Tests that non-owners have no access.
   *
   * @covers ::hasFullAccess
   */
  public function testNoAccessAsNonOwner() {
    $node = $this->createPremiumNode();

    // The account has none of the permissions.
    $this->account->hasPermission(Argument::type('string'))->wilLReturn(FALSE);

    // The account is authenticated.
    $this->account->isAuthenticated()->willReturn(TRUE);

    // The entity's owner ID and the account ID aren't the same.
    $node->getOwnerId()->willReturn(3);
    $this->account->id()->willReturn(4);

    // The account has no update access for this entity.
    $node->access('update', $this->account->reveal())->willReturn(FALSE);

    // Configure the entity to be premium.
    $node = $node->reveal();
    $node->premium = new \stdClass();
    $node->premium->value = TRUE;

    $this->assertFalse($this->helper->hasFullAccess($node, $this->account->reveal()));
  }

}
