<?php

namespace Drupal\context\Reaction\Blocks\Form;

/**
 * Provides a form to edit a block in the Block reaction.
 */
class BlockEditForm extends BlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_reaction_blocks_edit_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitValue() {
    return $this->t('Update block');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($block_id) {
    return $this->reaction->getBlock($block_id);
  }

}
