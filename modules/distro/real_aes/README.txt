## Overview

Real AES provides an encryption method plugin for the
[Encrypt](https://drupal.org/project/encrypt) module. This plugin offers AES encryption
using CBC mode and HMAC authentication through the
[Defuse PHP-Encryption](https://github.com/defuse/php-encryption) library.

## Requirements

- PHP 5.4 or later, with the OpenSSL extension
- Defuse PHP-Encryption library

## Installation

Install the Drupal 8 version of Real AES using Composer, after ensuring that your
composer.json file includes packages.drupal.org/8 as a repository:

`composer require drupal/real_aes`

## Configuration

Configure your site for encryption in Drupal 8 as follows:

1. Enable Real AES, Encrypt, and Key
2. Create a key using the Key module (at /admin/config/system/keys/add)
	 - Select "Encryption" for the key type
	 - Select "256" for the key size
	 - Select your preferred key provider and enter provider-specific settings
	 - The Configuration provider is fine for use during development, but should not be
	 	used on a production website
	 - The File provider is more secure, especially if the file is stored outside of the
	 	web root directory
	 - An even more secure option would be to use an off-site key management service, such
	 	as [Lockr](https://www.drupal.org/project/lockr) or
	 	[Townsend Security's Alliance Key Manager](https://www.drupal.org/project/townsec_key)
	 - Click "Save"
3. Create an encryption profile using the Encrypt module (at
   /admin/config/system/encryption/profiles/add)
	 - Select "Authenticated AES (Real AES)" for the encryption method
	 - Select the name of the key definition you created in step 2
	 - Click "Save"
4. Test your encryption by selecting "Test" under "Operations" for the encryption
   profile on the profiles listing page (/admin/config/system/encryption/profiles)

## About Authenticated Encryption

Authenticated encryption ensures data integrity of the ciphertext. When decrypting,
integrity is checked first. Further decryption operations will only be executed when the
integrity check passes. This prevents certain ciphertext attacks on AES in CBC mode.

## Credits

This module was created by [LimoenGroen](https://limoengroen.nl/) after carefully
considering the various encryption modules and libraries available.

The port to Drupal 8 was performed by [Sven Decabooter](/u/svendecabooter), supported by
[Acquia](https://www.acquia.com/).

The library doing the actual work,
[Defuse PHP-Encryption](https://github.com/defuse/php-encryption), is maintained by
Taylor Hornby and Scott Arciszewski