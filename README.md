# SilverStripe Shopify

A Shopify Store module for Silverstripe.

## Requirements

* SilverStripe ^4.2
* guzzlehttp/guzzle ^7.2
* littlegiant/silverstripe-catalogmanager ^5.2.0

## Installation

```
composer require dynamic/silverstripe-shopify
```

## License

See [License](license.md)

## Example configuration

```yaml

Dynamic\Shopify\Client\ShopifyClient:
  api_key: 'YOUR_API_KEY'
  api_password: 'YOUR_API_PASSWORD'
  shopify_domain: 'YOUR_SHOPIFY_DOMAIN' # mydomain.myshopify.com
  shared_secret: 'YOUR_API_SHARED_SECRET'

```

## Maintainers

 *  [Dynamic](http://www.dynamicagency.com) (<dev@dynamicagency.com>)

## Bugtracker

Bugs are tracked in the issues section of this repository. Before submitting an issue please read over
existing issues to ensure yours is unique.

If the issue does look like a new bug:

 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots
 and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version,
 Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.
