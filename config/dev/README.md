## Development overrides.

Configuration files placed here are treated as development environment overrides. They will be imported in non-production environments *only*.

For example if you place a field named 'shield.settings.yml' in this folder with the following contents:

```
credential_provider: shield
credentials:
  shield:
    user: example
    pass: password
print: Hello!
allow_cli: true
_core:
  default_config_hash: c1dcnGFDXTeMq2-Z8e7H6Qxp6TTJe-ZhSA126E3bQJ4
```

The shield configuration will be imported automatically in non-production Lagoon environments, enabling Shield authentication.
