<?php

namespace Drush\Commands\drush_tool;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drush\Commands\DrushCommands;

/**
 * Drush command file.
 */
class CustomExportCommand extends DrushCommands
{
  /**
   * Tracks the valid config entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $definitions = [];

  /**
   * A custom Drush command to displays the given text.
   *
   * @command drush-tool:custom-export
   * @aliases ceccex,cec-custom-export
   */
  public function customExport($config_type = '', $config_name = '', $sitename = '')
  {
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type => $definition) {
      if ($definition->entityClassImplements(ConfigEntityInterface::class)) {
        $this->definitions[$entity_type] = $definition;
      }
    }
    if ($config_type != 'system.simple') {
      // Load the config type except the simple configuration.
      $entity_storage = \Drupal::entityTypeManager()->getStorage($config_type);
      foreach ($entity_storage->loadMultiple() as $entity) {
        $entity_id = $entity->id();
        if ($label = $entity->label()) {
          $names[$entity_id] = new TranslatableMarkup('@label (@id)', ['@label' => $label, '@id' => $entity_id]);
        } else {
          $names[$entity_id] = $entity_id;
        }
      }
      $definition = \Drupal::entityTypeManager()->getDefinition($config_type);
      $name = $definition->getConfigPrefix() . '.' . $config_name;
      $data = ['websitename' => $sitename];
      $input = json_encode(\Drupal::service('config.storage')->read($name));
      $tempArray[] = json_decode($input);
      array_push($tempArray, $data);
      $jsonData = json_encode($tempArray);
      return $jsonData;
    } else {
      // Gather the config entity prefixes.
      $config_prefixes = array_map(function (EntityTypeInterface $definition) {
        return $definition->getConfigPrefix() . '.';
      }, $this->definitions);

      // Find all config, and then filter our anything matching a config prefix.
      $names = \Drupal::service('config.storage')->listAll();
      $names = array_combine($names, $names);
      foreach ($names as $config_name) {
        foreach ($config_prefixes as $config_prefix) {
          if (str_starts_with($config_name, $config_prefix)) {
            unset($names[$config_name]);
          }
        }
      }
      $data = ['websitename' => $sitename];
      $input = json_encode(\Drupal::service('config.storage')->read($config_name));
      $tempArray[] = json_decode($input);
      array_push($tempArray, $data);
      $jsonData = json_encode($tempArray);
      return $jsonData;
    }
  }

}
