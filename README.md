# SilverStripe Shopify

A Shopify Store module for Silverstripe.

![CI](https://github.com/dynamic/silverstripe-shopify/workflows/CI/badge.svg)
[![Build Status](https://travis-ci.com/dynamic/silverstripe-shopify.svg?token=hFT1sXd4nNmguE972zHN&branch=master)](https://travis-ci.com/dynamic/silverstripe-shopify)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dynamic/silverstripe-shopify/badges/quality-score.png?b=master&s=6602bc588bf7da4a15e9ae4e061c92781c87caf5)](https://scrutinizer-ci.com/g/dynamic/silverstripe-shopify/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/dynamic/silverstripe-shopify/badges/build.png?b=master&s=d0c33738b6be129105fa8f507591359fcf4f40ae)](https://scrutinizer-ci.com/g/dynamic/silverstripe-shopify/build-status/master)
[![codecov](https://codecov.io/gh/dynamic/silverstripe-shopify/branch/master/graph/badge.svg?token=8qD1GBbxzV)](https://codecov.io/gh/dynamic/silverstripe-shopify)

[![Latest Stable Version](https://poser.pugx.org/dynamic/silverstripe-shopify/v/stable)](https://packagist.org/packages/dynamic/silverstripe-shopify)
[![Total Downloads](https://poser.pugx.org/dynamic/silverstripe-shopify/downloads)](https://packagist.org/packages/dynamic/silverstripe-shopify)
[![Latest Unstable Version](https://poser.pugx.org/dynamic/silverstripe-shopify/v/unstable)](https://packagist.org/packages/dynamic/silverstripe-shopify)
[![License](https://poser.pugx.org/dynamic/silverstripe-shopify/license)](https://packagist.org/packages/dynamic/silverstripe-shopify)


## Requirements

* silverstripe/recipe-cms ^4.5
* guzzlehttp/guzzle ^7.2
* littlegiant/silverstripe-catalogmanager ^5.2

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
  storefront_access_token: 'YOUR_ACCESS_TOKEN' # for buy button
  shopify_domain: 'YOUR_SHOPIFY_DOMAIN' # mydomain.myshopify.com
  custom_domain: 'YOUR_CUSTOM_DOMAIN' # checkout.example.com
  shared_secret: 'YOUR_API_SHARED_SECRET'

```

## Product impression tracking
Product impressions can be tracked by adding data attributes to html tags rendered with products.
`data-sku` is the only required data attribute, but `data-title`, `data-category`, and `data-vendor` can also be added.

```html
<div class="product__content" data-sku="$SKU" data-title="$Title" data-category="$Category.Title" data-vendor="$Vendor"></div>
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
