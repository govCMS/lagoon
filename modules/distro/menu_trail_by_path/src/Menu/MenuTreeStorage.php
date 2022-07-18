<?php

namespace Drupal\menu_trail_by_path\Menu;

use Drupal\Core\Menu\MenuTreeStorage AS CoreMenuTreeStorage;
use Drupal\Core\Menu\MenuTreeStorageInterface;

class MenuTreeStorage extends CoreMenuTreeStorage implements MenuTreeStorageInterface {
  /**
   * Same as parent, but with ordering
   *
   * {@inheritdoc}
   */
  public function loadByProperties(array $properties) {
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table, $this->definitionFields());
    foreach ($properties as $name => $value) {
      if (!in_array($name, $this->definitionFields(), TRUE)) {
        $fields = implode(', ', $this->definitionFields());
        throw new \InvalidArgumentException("An invalid property name, $name was specified. Allowed property names are: $fields.");
      }
      $query->condition($name, $value);
    }
    // Make the ordering deterministic.
    $query->orderBy('depth');
    $query->orderBy('weight');
    $query->orderBy('id');
    $loaded = $this->safeExecuteSelect($query)->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    foreach ($loaded as $id => $link) {
      $loaded[$id] = $this->prepareLink($link);
    }
    return $loaded;
  }
}
