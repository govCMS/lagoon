<?php

$idpBaseURL = getenv('SIMPLESAMLPHP_IDP_BASE_URL');
$idpEntityId = getenv('SIMPLESAMLPHP_IDP_ENTITYID') ?: $idpBaseURL;
$fallbackBinding = getenv('SIMPLESAMLPHP_IDP_DEFAULT_BINDING');

$bindingKeys = [
    'SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING',
    'SIMPLESAMLPHP_IDP_HTTP_POST_BINDING',
    'SIMPLESAMLPHP_IDP_SOAP_BINDING',
    'SIMPLESAMLPHP_IDP_HTTP_ARTIFACT',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT',
];

// Initialise bindings.
$bindings = [];

// Set bindings based on their associated env variables.
foreach ($bindingKeys as $key) {
    $envVar = getenv($key);

    // Special for LOGOUT: fallback to non-logout sibling if present.
    if (str_contains($key, 'LOGOUT') && empty($envVar)) {
        $nonLogoutKey = str_replace('LOGOUT_', '', $key);
        $envVar = getenv($nonLogoutKey);
    }

    // Special for HTTP-Redirect: still attempt a fallback if empty.
    // This is because SimpleSAMLphp uses HTTP-Redirect binding by default.
    if ($key === 'SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING' && empty($envVar)) {
        $envVar = $fallbackBinding;
    }

    // Skip if empty.
    if (empty($envVar)) {
        continue;
    }

    // Binding locations need to be fully qualified URLs.
    // Prepend base URL if necessary.
    $bindings[$key] = str_starts_with($envVar, 'http') ? $envVar : $idpBaseURL . $envVar;
}

$metadata[$idpEntityId] = [
  'entityid' => $idpEntityId,
  'contacts' => [],
  'errorURL' => getenv('SIMPLESAMLPHP_SP_ERROR_URL') ?: null,
  'metadata-set' => 'saml20-idp-remote',
  'sign.authnrequest' => filter_var(getenv('SIMPLESAMLPHP_IDP_SIGN_AUTH'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
  'SingleSignOnService' => [],
  'SingleLogoutService' => [],
  'ArtifactResolutionService' => [],
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

// Conditionally add SSO bindings.
if (!empty($bindings['SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING'])) {
    $metadata[$idpEntityId]['SingleSignOnService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING'],
    ];
}

if (!empty($bindings['SIMPLESAMLPHP_IDP_HTTP_POST_BINDING'])) {
    $metadata[$idpEntityId]['SingleSignOnService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_POST_BINDING'],
    ];
}

if (!empty($bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'])) {
    $metadata[$idpEntityId]['SingleSignOnService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'],
    ];
}

if (!empty($bindings['SIMPLESAMLPHP_IDP_HTTP_ARTIFACT'])) {
    $metadata[$idpEntityId]['SingleSignOnService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_ARTIFACT'],
    ];
}

// Conditionally add SLO bindings.
if (!empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING'])) {
    $metadata[$idpEntityId]['SingleLogoutService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING'],
    ];
}

if (!empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING'])) {
    $metadata[$idpEntityId]['SingleLogoutService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING'],
    ];
}

if (!empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING'])) {
    $metadata[$idpEntityId]['SingleLogoutService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING'],
    ];
}

if (!empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT'])) {
    $metadata[$idpEntityId]['SingleLogoutService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT'],
    ];
}

// Add ArtifactResolutionService only if SOAP present.
if (!empty($bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'])) {
    $metadata[$idpEntityId]['ArtifactResolutionService'][] = [
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
        'Location' => $bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'],
        'index' => 0,
    ];
}
