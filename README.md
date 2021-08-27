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
* symbiote/silverstripe-gridfieldextensions ^3.0
* osiset/basic-shopify-api ^10.0
* bramdeleeuw/silverstripe-schema ^2.0

## Installation

```
composer require dynamic/silverstripe-shopify
```

## License

See [License](license.md)

## Example configuration

### Basic configuration

```yaml

Dynamic\Shopify\Client\ShopifyClient:
  api_key: 'YOUR_API_KEY'
  api_password: 'YOUR_API_PASSWORD'
  shared_secret: 'YOUR_API_SHARED_SECRET'
  storefront_access_token: 'YOUR_ACCESS_TOKEN' # for buy button
  shopify_domain: 'YOUR_SHOPIFY_DOMAIN' # mydomain.myshopify.com
  custom_domain: 'YOUR_CUSTOM_DOMAIN' # optional - checkout.example.com

```

### Using Multipass

```yaml

Dynamic\Shopify\Client\ShopifyMultipass:
  multipass_secret: 'YOUR_MULTIPASS_SECRET'
```  

## Usage

### Create a private app

In your Shopify Admin, click `Apps` from the left column navigation. Once the page loads, scroll to the bottom and click on the link in the following line:

`Working with a developer on your shop? Manage private apps`

If no private apps exist, click `Create new private app`. Otherwise, click on the link to the existing private app you'd like to use for your Silverstripe website.

### Obtaining API Keys



### Importing products

Once the basic configuration above is setup, you can import Shopify products and collections using the ShopifyImportTask:

```yaml
vendor/bin/sake dev/tasks/ShopifyImportTask
```

or by running the task in the browser at `/dev/tasks/ShopifyImportTask`

## Theme

### Cart Include

In your top-level Page.ss template, include the following just before the `</body>` tag:

```
<% include Cart %>
```

### Display Buy Button

Out of the box, there are 3 includes to display different variations of the Shopify Buy Button:

* BuyButton - just a simple add to cart button with no other product info
* BuyForm - a typical add to cart form, ideal for a ShopifyProduct page
* BuyOverlay - an add to cart button that opens an overlay containing product info from Shopify

To display the Buy Button, just include one of the files above in your template.


## Advanced

### Product impression tracking
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
