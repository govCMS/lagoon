<?php
$env_type = getenv('LAGOON_ENVIRONMENT_TYPE') ?: 'development';
$sp_name = getenv('LAGOON_PROJECT') . '-' . $env_type;
$cert_dir = getenv('SIMPLESAMLPHP_CERT_DIR') ?: '/app/web/sites/default/files/private';
$idp = getenv('SIMPLESAMLPHP_IDP_ENTITYID') ?: getenv('SIMPLESAMLPHP_IDP_BASE_URL');

$config = [
    /*
     * When multiple authentication sources are defined, you can specify one to use by default
     * in order to authenticate users. In order to do that, you just need to name it "default"
     * here. That authentication source will be used by default then when a user reaches the
     * SimpleSAMLphp installation from the web browser, without passing through the API.
     *
     * If you already have named your auth source with a different name, you don't need to change
     * it in order to use it as a default. Just create an alias by the end of this file:
     *
     * $config['default'] = &$config['your_auth_source'];
     */

    // This is a authentication source which handles admin authentication.
    'admin' => [
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ],


    // An authentication source which can authenticate against SAML 2.0 IdPs.
    $sp_name => [
        'saml:SP',

        // The entity ID of this SP.
        'entityID' => getenv('LAGOON_ROUTE'),

        // The entity ID of the IdP this SP should contact.
        // Can be NULL/unset, in which case the user will be shown a list of available IdPs.
        'idp' => $idp,

        // The format of the NameID we request from the IdP in the AuthnRequest:
        // an array in the form of [ 'Format' => the format, 'AllowCreate' => true or false ]
        // Set to an empty array [] to omit sending any specific NameIDPolicy element in the AuthnRequest.
        // When the entire option or either array key is unset, the defaults are transient and true respectively.
        // As the service provider desires the IdP have the flexibility to generate a new identifier for the user should one not already exist,
        // the SP sets the AllowCreate attribute on the NameIDPolicy element to 'true”.
        'NameIDPolicy' => [],

        // The URL to the discovery service.
        // Can be NULL/unset, in which case a builtin discovery service will be used.
        'discoURL' => null,

        /*
         * If SP behind the SimpleSAMLphp in IdP/SP proxy mode requests
         * AuthnContextClassRef, decide whether the AuthnContextClassRef will be
         * processed by the IdP/SP proxy or if it will be passed to the original
         * IdP in front of the IdP/SP proxy.
         */
        'proxymode.passAuthnContextClassRef' => false,

        /*
         * The NameIDFormat this SP should receive. This may be specified as either a string or an array.
         * The three most commonly used values are:
         *   urn:oasis:names:tc:SAML:2.0:nameid-format:transient
         *   urn:oasis:names:tc:SAML:2.0:nameid-format:persistent
         *   urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress
         */
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',

        /*
         * Location of certificate data for this SP. 
         * The certificate is used to verify the signature of messages received from the SP (if redirect.validate is set to TRUE ), 
         * and to encrypting assertions (if assertion.encryption is set to TRUE and sharedkey is unset.)
         */
        'certificate' => $cert_dir . '/saml.crt',
        'privatekey' => $cert_dir . '/saml.pem',

        /*
         * Whether logout requests and logout responses sent to this SP should be signed. The default is FALSE .
         */
        'redirect.sign' => filter_var(getenv('SIMPLESAMLPHP_SP_SIGN_AUTH'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,

        /* 
         * Whether authentication requests, logout requests and logout responses received from this SP should be validated.
         * The default is FALSE 
         */
        'redirect.validate' => getenv('SIMPLESAMLPHP_SP_VALIDATE_AUTH') ?: true,

        /*
         * Whether we require signatures on authentication requests sent from this SP. Set it to:
         *   - true: authnrequest must be signed (and signature will be validated)
         *   - null: authnrequest may be signed, if it is, signature will be validated
         *   - false: authnrequest signature is never checked
         */
        'validate.authnrequest' => false,

        /*
         * The attributes parameter must contain an array of desired attributes by the SP.
         * The attributes can be expressed as an array of names or as an associative array
         * in the form of 'friendlyName' => 'name'. This feature requires 'name' to be set.
         * The metadata will then be created as follows:
         * <md:RequestedAttribute FriendlyName="friendlyName" Name="name" />
         */
    ],
];
