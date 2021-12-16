<?php

namespace Drupal\nopremium\Plugin\search_api\processor;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds premium information to the indexes.
 *
 * @SearchApiProcessor(
 *   id = "nopremium",
 *   label = @Translation("Premium content"),
 *   description = @Translation("Adds premium information to the index, so that premium nodes can be excluded from the search results."),
 *   stages = {
 *     "pre_index_save" = -10,
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class PremiumContent extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|null
   */
  protected $database;

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|null
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setLogger($container->get('logger.channel.search_api'));
    $processor->setDatabase($container->get('database'));
    $processor->setCurrentUser($container->get('current_user'));

    return $processor;
  }

  /**
   * Retrieves the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    return $this->database ?: \Drupal::database();
  }

  /**
   * Sets the database connection.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The new database connection.
   *
   * @return $this
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
    return $this;
  }

  /**
   * Retrieves the current user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::currentUser();
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      // Premium filter is only applyable on nodes.
      if ($datasource->getEntityTypeId() === 'node') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        $this->ensureField($datasource_id, 'premium', 'boolean');
        $this->ensureField($datasource_id, 'type', 'string');
        $this->ensureField($datasource_id, 'uid', 'integer');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    if (!$query->getOption('search_api_bypass_access')) {
      $account = $query->getOption('search_api_access_account', $this->getCurrentUser());
      if (is_numeric($account)) {
        $account = User::load($account);
      }
      if ($account instanceof AccountInterface) {
        try {
          $this->addPremiumAccess($query, $account);
        }
        catch (SearchApiException $e) {
          $this->logException($e);
        }
      }
      else {
        $account = $query->getOption('search_api_access_account', $this->getCurrentUser());
        if ($account instanceof AccountInterface) {
          $account = $account->id();
        }
        if (!is_scalar($account)) {
          $account = var_export($account, TRUE);
        }
        $this->getLogger()->warning('An illegal user UID was given for node access: @uid.', ['@uid' => $account]);
      }
    }
  }

  /**
   * Adds a premium access filter to a search query, if applicable.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to which a node access filter should be added, if applicable.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for whom the search is executed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if not all necessary fields are indexed on the index.
   */
  protected function addPremiumAccess(QueryInterface $query, AccountInterface $account) {
    // Don't do anything if the user can access all premium content.
    if ($account->hasPermission('view full premium content of any type')) {
      return;
    }

    // Gather the affected datasources, grouped by entity type, as well as the
    // unaffected ones.
    $affected_datasources = [];
    $unaffected_datasources = [];
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      if ($datasource->getEntityTypeId() === 'node') {
        $affected_datasources['node'][] = $datasource_id;
      }
      else {
        $unaffected_datasources[] = $datasource_id;
      }
    }

    // The filter structure we want looks like this:
    // @code
    //   [belongs to other datasource]
    //   OR
    //   (
    //     [is enabled (or was created by the user, if applicable)]
    //     AND
    //     [grants view access to one of the user's gid/realm combinations]
    //   )
    // @endcode
    // If there are no "other" datasources, we don't need the nested OR,
    // however, and can add the inner conditions directly to the query.
    if ($unaffected_datasources) {
      $outer_conditions = $query->createConditionGroup('OR', ['content_access']);
      $query->addConditionGroup($outer_conditions);
      foreach ($unaffected_datasources as $datasource_id) {
        $outer_conditions->addCondition('search_api_datasource', $datasource_id);
      }
      $access_conditions = $query->createConditionGroup('AND');
      $outer_conditions->addConditionGroup($access_conditions);
    }
    else {
      $access_conditions = $query;
    }

    // If the user does not have the permission to see any content at all, deny
    // access to all items from affected datasources.
    if (!$affected_datasources) {
      // If there were "other" datasources, the existing filter will already
      // remove all results of node or comment datasources. Otherwise, we should
      // not return any results at all.
      if (!$unaffected_datasources) {
        $query->abort($this->t('You have no access to any results in this search.'));
      }
      return;
    }

    // Authors of premium nodes may always view their own nodes.
    $premium_conditions = $query->createConditionGroup('OR');
    if ($account->isAuthenticated()) {
      $author_conditions = $query->createConditionGroup('OR');
      foreach ($affected_datasources as $entity_type => $datasources) {
        foreach ($datasources as $datasource_id) {
          if ($entity_type == 'node') {
            $author_field = $this->findField($datasource_id, 'uid', 'integer');
            if ($author_field) {
              $author_conditions->addCondition($author_field->getFieldIdentifier(), $account->id());
            }
          }
        }
      }
      $premium_conditions->addConditionGroup($author_conditions);
    }

    foreach (NodeType::loadMultiple() as $type) {
      $type_id = $type->id();
      if (!$account->hasPermission("view full $type_id premium content")) {
        // User may only view non-premium nodes of this type.
        $node_type_conditions = $query->createConditionGroup('AND');
        $node_type_conditions->addCondition('type', $type_id);
        $node_type_conditions->addCondition('premium', FALSE);

        $premium_conditions->addConditionGroup($node_type_conditions);
      }
      else {
        $premium_conditions->addCondition('type', $type_id);
      }
    }

    $access_conditions->addConditionGroup($premium_conditions);
  }

}
