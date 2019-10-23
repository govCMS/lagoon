# GovCMS Platform

Provides additional tooling and management processes for sites hosted on the govCMS platform. The module provides a number of service decorators to improve our ability to manage the platform.

## Verbose logging

```
$config['lagoon_extras.settings']['verbose_logging']= getenv('GOVCMS_VERBOSE_LOGGING') ?: FALSE;
```

Enable verbose logging, this will cause the decorated services to send more verbose logging information to the ELK stack (typically backtrace information).
