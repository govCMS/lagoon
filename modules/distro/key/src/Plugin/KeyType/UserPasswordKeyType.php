<?php

namespace Drupal\key\Plugin\KeyType;

/**
 * Defines a key that combines a user name and password.
 *
 * @KeyType(
 *   id = "user_password",
 *   label = @Translation("User/password"),
 *   description = @Translation("A key type to store a user/password pair."),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "textarea_field"
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *       "username" = @Translation("User name"),
 *       "password" = @Translation("Password")
 *     }
 *   }
 * )
 */
class UserPasswordKeyType extends AuthenticationMultivalueKeyType {}
