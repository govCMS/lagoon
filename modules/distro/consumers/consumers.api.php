<?php

/**
 * @file
 * Documentation for Consumers module APIs.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the list builder.
 *
 * @param array $data
 *   The data to alter. It's either the header or a data row.
 * @param array $context
 *   Contains a key 'type' that can be either 'header' or 'row'. It can also
 *   contain a key 'entity' containing the consumer entity in the row.
 */
function hook_consumes_list_alter(array &$data, array $context) {
  if ($context['type'] === 'header') {
    $data['scopes'] = t('Foo');
  }
  elseif ($context['type'] === 'row') {
    $entity = $context['entity'];
    $data['confidential'] = $entity->get('foo')->value;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
