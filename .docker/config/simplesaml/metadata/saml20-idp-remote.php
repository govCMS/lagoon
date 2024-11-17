<?php

$idpBaseURL = getenv('SIMPLESAMLPHP_IDP_BASE_URL');
$idpEntityId = getenv('SIMPLESAMLPHP_IDP_ENTITYID') ?: $idpBaseURL;
$fallbackBinding = getenv('SIMPLESAMLPHP_IDP_DEFAULT_BINDING');

$bindings = [
  'SIMPLESAMLPHP_IDP_HTTP_POST_BINDING' => $fallbackBinding,
  'SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING' => $fallbackBinding,
  'SIMPLESAMLPHP_IDP_SOAP_BINDING' => $fallbackBinding,
  'SIMPLESAMLPHP_IDP_HTTP_ARTIFACT' => $fallbackBinding,
];

$bindings = array_map(function($fallback, $binding) use ($idpBaseURL) {
  $envVar = getenv($binding);
  
  if (empty($envVar)) {
    // Return the fallback binding if the environment variable is not set.
    return $idpBaseURL . $fallback;
  }

  // If the environment variable is a full URL, use it as is. Otherwise,
  // prepend the base URL.
  return str_starts_with($envVar, 'http') ? $envVar : $idpBaseURL . $envVar;
}, $bindings, array_keys($bindings));

$metadata[$idpEntityId] = [
  'entityid' => $idpEntityId,
  'contacts' => [],
  'metadata-set' => 'saml20-idp-remote',
  'sign.authnrequest' => !empty(getenv('SIMPLESAMLPHP_IDP_SIGN_AUTH')),
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
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_POST_BINDING'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_ARTIFACT'],
    ],
    [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'],
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
  'keys' => [
    [
      'encryption' => getenv('SIMPLESAMLPHP_IDP_CERT_ENCRYPT') ?: FALSE,
      'signing' => getenv('SIMPLESAMLPHP_IDP_CERT_SIGNING') ?: TRUE,
      'type' => getenv('SIMPLESAMLPHP_IDP_CERT_TYPE') ?: 'X509Certificate',
      'X509Certificate' => getenv('SIMPLESAMLPHP_IDP_CERT'),
    ],
  ],
];
