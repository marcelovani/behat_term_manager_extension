# Behat Term Manager Extension

Provides a suite to test Term manager.

## Installation
Install using composer as usual i.e.
`composer require dennisdigital/drupal_console_commands:dev-master`

Add the extension the behat.yml on your site.
```
default:
  extensions:
    Behat\TermManagerExtension
```

See examples of tests in https://github.com/dennisinteractive/behat_term_manager_extension/tree/master/features

### Dependencies:
- Behat
- Symfony DPI
- Mink
- Term Manager
