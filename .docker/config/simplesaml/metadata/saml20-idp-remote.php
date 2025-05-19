<?php

$idpBaseURL = getenv('SIMPLESAMLPHP_IDP_BASE_URL');
$idpEntityId = getenv('SIMPLESAMLPHP_IDP_ENTITYID') ?: $idpBaseURL;
$fallbackBinding = getenv('SIMPLESAMLPHP_IDP_DEFAULT_BINDING');

$bindingKeys = [
    'SIMPLESAMLPHP_IDP_HTTP_POST_BINDING',
    'SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING',
    'SIMPLESAMLPHP_IDP_SOAP_BINDING',
    'SIMPLESAMLPHP_IDP_HTTP_ARTIFACT',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT',
];

// Initialise bindings.
$bindings = [];

// Set bindings based on env variable or otherwise use fallback.
foreach ($bindingKeys as $key) {
    $envVar = getenv($key);

    // Special logic for logout bindings:
    // If no environment variable is set for a logout binding,
    // fall back to the corresponding non-logout binding value instead.
    if (str_contains($key, 'LOGOUT') && empty($envVar)) {
        $nonLogoutKey = str_replace('LOGOUT_', '', $key);
        $envVar = getenv($nonLogoutKey) ?: $fallbackBinding;
    }

    // Apply a default fallback binding if environment variable is not set.
    $envVar = $envVar ?: $fallbackBinding;

    // Prepend base URL if binding is not a full URL.
    $bindings[$key] = str_starts_with($envVar, 'http') ? $envVar : $idpBaseURL . $envVar;
}



$metadata[$idpEntityId] = [
  'entityid' => $idpEntityId,
  'contacts' => [],
  'metadata-set' => 'saml20-idp-remote',
  'sign.authnrequest' => filter_var(getenv('SIMPLESAMLPHP_IDP_SIGN_AUTH'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
  'SingleSignOnService' => [
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_POST_BINDING'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_ARTIFACT'],
    ],
  ],
  'SingleLogoutService' => [
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING'],
    ],
  ],
  'ArtifactResolutionService' => [
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'],
      'index' => 0,
    ],
  ],
  'NameIDFormats' => [
    'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
    'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
    'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
  ],
  'signature.algorithm' => getenv('SIMPLESAMLPHP_IDP_SIGNATURE_ALGORITHM') ?: 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
  'keys' => [
    [
      'encryption' => filter_var(getenv('SIMPLESAMLPHP_IDP_CERT_ENCRYPT'), FILTER_VALIDATE_BOOLEAN),
      'signing' => filter_var(getenv('SIMPLESAMLPHP_IDP_CERT_SIGNING'), FILTER_VALIDATE_BOOLEAN),
      'type' => getenv('SIMPLESAMLPHP_IDP_CERT_TYPE') ?: 'X509Certificate',
      'X509Certificate' => getenv('SIMPLESAMLPHP_IDP_CERT'),
    ],
  ],
];
