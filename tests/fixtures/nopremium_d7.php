<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add premium column to the node table.
$connection->schema()->addField('node', 'premium', [
  'type' => 'int',
  'size' => 'tiny',
  'unsigned' => TRUE,
  'not null' => TRUE,
  'default' => 0,
]);

// Insert a premium node.
$connection->insert('node')
  ->fields([
    'nid',
    'vid',
    'type',
    'language',
    'title',
    'uid',
    'status',
    'created',
    'changed',
    'comment',
    'promote',
    'sticky',
    'tnid',
    'translate',
    'premium',
  ])
  ->values([
    'nid' => '129',
    'vid' => '183',
    'type' => 'article',
    'language' => 'en',
    'title' => 'Premium node',
    'uid' => '2',
    'status' => '1',
    'created' => '1421727515',
    'changed' => '1441032132',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
    'tnid' => '0',
    'translate' => '0',
    'premium' => '1',
  ])
  ->execute();

$connection->insert('node_revision')
  ->fields([
    'nid',
    'vid',
    'uid',
    'title',
    'log',
    'timestamp',
    'status',
    'comment',
    'promote',
    'sticky',
  ])
  ->values([
    'nid' => '129',
    'vid' => '183',
    'uid' => '2',
    'title' => 'Premium node',
    'log' => '',
    'timestamp' => '1441032132',
    'status' => '1',
    'comment' => '2',
    'promote' => '1',
    'sticky' => '0',
  ])
  ->execute();
