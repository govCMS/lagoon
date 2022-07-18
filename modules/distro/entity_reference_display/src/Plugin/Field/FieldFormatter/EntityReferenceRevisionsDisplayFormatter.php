<?php

namespace Drupal\entity_reference_display\Plugin\Field\FieldFormatter;

use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;

/**
 * Extends the 'entity_reference_display_default' formatter by support of revisions.
 *
 * @see entity_reference_display_field_formatter_info_alter()
 */
class EntityReferenceRevisionsDisplayFormatter extends EntityReferenceRevisionsEntityFormatter {

  use EntityReferenceDisplayFormatterTrait;

}
