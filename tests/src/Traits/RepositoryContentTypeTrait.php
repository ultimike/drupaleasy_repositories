<?php

namespace Drupal\Tests\drupaleasy_repositories\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Provides a helper method for creating a repository content type with fields.
 */
trait RepositoryContentTypeTrait {

  /**
   * Creates a repository content type with fields.
   */
  protected function createRepositoryContentType() {
    NodeType::create(['type' => 'repository', 'name' => 'Repository'])->save();

    // Create Description field.
    FieldStorageConfig::create([
      'field_name' => 'field_description',
      'type' => 'text_long',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_description',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Description',
    ])->save();

    // Create Hash field.
    FieldStorageConfig::create([
      'field_name' => 'field_hash',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_hash',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Hash',
    ])->save();

    // Create Machine name field.
    FieldStorageConfig::create([
      'field_name' => 'field_machine_name',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_machine_name',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Machine name',
    ])->save();

    // Create Number of open issues field.
    FieldStorageConfig::create([
      'field_name' => 'field_number_of_issues',
      'type' => 'integer',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_number_of_issues',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Number of open issues',
    ])->save();

    // Create Source field.
    FieldStorageConfig::create([
      'field_name' => 'field_source',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_source',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Source',
    ])->save();

    // Create URL field.
    FieldStorageConfig::create([
      'field_name' => 'field_url',
      'type' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_url',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'URL',
    ])->save();
  }

  /**
   * Returns sample repository info.
   *
   * @return array
   *   The sample repository info.
   */
  protected function getAquamanRepo() {
    // The order of elements of this array matters when calculating the hash.
    $repo['aquaman-repository'] = [
      'label' => 'The Aquaman repository',
      'description' => 'This is where Aquaman keeps all his crime-fighting code.',
      'num_open_issues' => 6,
      'source' => 'yml',
      'url' => 'http://example.com/aquaman-repo.yml',
    ];
    return $repo;
  }

}
