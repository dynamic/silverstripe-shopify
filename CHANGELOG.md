# Changelog

## [1.2.0](https://github.com/dynamic/silverstripe-shopify/tree/1.2.0) (2022-09-06)


## What's Changed
* Added more text options for buy button options by @mak001 in https://github.com/dynamic/silverstripe-shopify/pull/132
* COMPOSER remove/update guzzle requirements by @jsirish in https://github.com/dynamic/silverstripe-shopify/pull/134
* README credits copy by @jsirish in https://github.com/dynamic/silverstripe-shopify/pull/135
* README note on PHP7 compatibility by @jsirish in https://github.com/dynamic/silverstripe-shopify/pull/136
* CI php versions for tests, include PHP 8.0 and 8.1 by @jsirish in https://github.com/dynamic/silverstripe-shopify/pull/137


**Full Changelog**: https://github.com/dynamic/silverstripe-shopify/compare/1.1.0...1.2.0

## [1.1.0](https://github.com/dynamic/silverstripe-shopify/tree/1.1.0) (2022-01-10)

[Full Changelog](https://github.com/dynamic/silverstripe-shopify/compare/1.0.0...1.1.0)

**Merged pull requests:**

- Added config for turning off cart note [\#129](https://github.com/dynamic/silverstripe-shopify/pull/129) ([mak001](https://github.com/mak001))

## [1.0.0](https://github.com/dynamic/silverstripe-shopify/tree/1.0.0) (2021-10-27)

[Full Changelog](https://github.com/dynamic/silverstripe-shopify/compare/1.0.0-alpha1...1.0.0)

**Implemented enhancements:**

- FEATURE - Add enhanced ecommerce event tracking [\#97](https://github.com/dynamic/silverstripe-shopify/issues/97)
- FEATURE - add "Fetch from shopify" button to product pages [\#96](https://github.com/dynamic/silverstripe-shopify/issues/96)
- BUG Checkout - go full window, rather than existing small pop up [\#79](https://github.com/dynamic/silverstripe-shopify/issues/79)

**Fixed bugs:**

- BUG Shopify Task has errors as ShopifyFile checks isPublished - no longer versioned [\#120](https://github.com/dynamic/silverstripe-shopify/issues/120)

**Closed issues:**

- BUG ShopifyImportTask - newly imported products are published, but $owns \(images\) are not [\#78](https://github.com/dynamic/silverstripe-shopify/issues/78)
- BUG ShopifyImportTask - publish Virtual pages if product is set as active [\#68](https://github.com/dynamic/silverstripe-shopify/issues/68)
- FEATURE - AJAX-ify order history [\#64](https://github.com/dynamic/silverstripe-shopify/issues/64)
- FEATURE ShopifyCollection - remove many\_many to ShopifyProduct [\#61](https://github.com/dynamic/silverstripe-shopify/issues/61)
- BUG ShopifyProduct - hide from menu by default [\#54](https://github.com/dynamic/silverstripe-shopify/issues/54)
- FEATURE Currency symbol should be configurable [\#47](https://github.com/dynamic/silverstripe-shopify/issues/47)
- FEATURE Default Templates [\#46](https://github.com/dynamic/silverstripe-shopify/issues/46)
- FEATURE Customer Single Sign-On [\#44](https://github.com/dynamic/silverstripe-shopify/issues/44)
- FEATURE Order History [\#43](https://github.com/dynamic/silverstripe-shopify/issues/43)
- FEATURE Shopify Data Sync [\#41](https://github.com/dynamic/silverstripe-shopify/issues/41)
- FEATURE Shopify Admin API PHP Library to integrate [\#40](https://github.com/dynamic/silverstripe-shopify/issues/40)
- FEATURE API - update to more recent version [\#113](https://github.com/dynamic/silverstripe-shopify/issues/113)
- BUG ShopifyImportTask - task seems to error if no Collections exist \(or are in the API sales channel\) [\#94](https://github.com/dynamic/silverstripe-shopify/issues/94)
- FEATURE - Add user specifically for shopify changes [\#88](https://github.com/dynamic/silverstripe-shopify/issues/88)

**Merged pull requests:**

- api version is now set in config [\#127](https://github.com/dynamic/silverstripe-shopify/pull/127) ([mak001](https://github.com/mak001))
- ENHANCEMENT CMS user for task run [\#122](https://github.com/dynamic/silverstripe-shopify/pull/122) ([muskie9](https://github.com/muskie9))
- BUG ShopifyFile calls isPublished on itself though it's not versioned [\#121](https://github.com/dynamic/silverstripe-shopify/pull/121) ([muskie9](https://github.com/muskie9))
- CI Travis adjustments [\#118](https://github.com/dynamic/silverstripe-shopify/pull/118) ([jsirish](https://github.com/jsirish))
- fixed a bad method call to doUnpublish of files [\#117](https://github.com/dynamic/silverstripe-shopify/pull/117) ([mak001](https://github.com/mak001))
- Removed versioning from shopify files [\#116](https://github.com/dynamic/silverstripe-shopify/pull/116) ([mak001](https://github.com/mak001))
- Fixed bug with schema with no compare at price [\#114](https://github.com/dynamic/silverstripe-shopify/pull/114) ([mak001](https://github.com/mak001))
- FEATURE default templates for pages and elements [\#112](https://github.com/dynamic/silverstripe-shopify/pull/112) ([jsirish](https://github.com/jsirish))
- REFACTOR remove dynamic/silverstripe-site-tools requirement [\#111](https://github.com/dynamic/silverstripe-shopify/pull/111) ([jsirish](https://github.com/jsirish))
- DOCS README update for initial release [\#109](https://github.com/dynamic/silverstripe-shopify/pull/109) ([jsirish](https://github.com/jsirish))
- Added a way to track product impressions [\#106](https://github.com/dynamic/silverstripe-shopify/pull/106) ([mak001](https://github.com/mak001))
- made the cart toggle button trackable [\#105](https://github.com/dynamic/silverstripe-shopify/pull/105) ([mak001](https://github.com/mak001))
- fixed enhanced ecommerce erroring on first load [\#104](https://github.com/dynamic/silverstripe-shopify/pull/104) ([mak001](https://github.com/mak001))
- fixed enhanced ecommerce comments causing issues [\#103](https://github.com/dynamic/silverstripe-shopify/pull/103) ([mak001](https://github.com/mak001))
- Added "Fetch from shopify" button to products and collections [\#101](https://github.com/dynamic/silverstripe-shopify/pull/101) ([mak001](https://github.com/mak001))
- Added a schema for products [\#100](https://github.com/dynamic/silverstripe-shopify/pull/100) ([mak001](https://github.com/mak001))
- Started work on getting ecommerce tracking built [\#99](https://github.com/dynamic/silverstripe-shopify/pull/99) ([mak001](https://github.com/mak001))
- BUGFIX ElementProducts - sort issue [\#98](https://github.com/dynamic/silverstripe-shopify/pull/98) ([jsirish](https://github.com/jsirish))
- BUGFIX ShopifyExtension - enable cart notes in getCartOptions\(\) [\#95](https://github.com/dynamic/silverstripe-shopify/pull/95) ([jsirish](https://github.com/jsirish))
- BUGFIX ShopifyClient - add missing $custom\_domain static [\#93](https://github.com/dynamic/silverstripe-shopify/pull/93) ([jsirish](https://github.com/jsirish))
- fixed relative redirect urls going to shopify store page [\#91](https://github.com/dynamic/silverstripe-shopify/pull/91) ([mak001](https://github.com/mak001))
- removed debug in shopify login handler [\#90](https://github.com/dynamic/silverstripe-shopify/pull/90) ([mak001](https://github.com/mak001))
- Added back url support for multipass [\#89](https://github.com/dynamic/silverstripe-shopify/pull/89) ([mak001](https://github.com/mak001))
- fixed discount codes causing errors [\#87](https://github.com/dynamic/silverstripe-shopify/pull/87) ([mak001](https://github.com/mak001))
- Fixed no user causing bad render for order history [\#86](https://github.com/dynamic/silverstripe-shopify/pull/86) ([mak001](https://github.com/mak001))
- REFACTOR ShopifyClient - add custom domain option [\#85](https://github.com/dynamic/silverstripe-shopify/pull/85) ([jsirish](https://github.com/jsirish))
- FEATURE Checkout - set to full screen [\#84](https://github.com/dynamic/silverstripe-shopify/pull/84) ([jsirish](https://github.com/jsirish))
- BUGFIX getProductList does not account for virtual categories [\#83](https://github.com/dynamic/silverstripe-shopify/pull/83) ([muskie9](https://github.com/muskie9))
- BUGFIX ShopifyProduct - Catalog manager - disable automatic\_live\_sort [\#82](https://github.com/dynamic/silverstripe-shopify/pull/82) ([jsirish](https://github.com/jsirish))
- Fixed bug with missing data check for virtual pages [\#80](https://github.com/dynamic/silverstripe-shopify/pull/80) ([mak001](https://github.com/mak001))
- Fixed javascript for next orders only appending one instead of all [\#76](https://github.com/dynamic/silverstripe-shopify/pull/76) ([mak001](https://github.com/mak001))
- Added ajax support for order history [\#75](https://github.com/dynamic/silverstripe-shopify/pull/75) ([mak001](https://github.com/mak001))
- Fixed broken collection import when image is present [\#74](https://github.com/dynamic/silverstripe-shopify/pull/74) ([mak001](https://github.com/mak001))
- got files to import using new query [\#73](https://github.com/dynamic/silverstripe-shopify/pull/73) ([mak001](https://github.com/mak001))
- FEATURE Multipass integration [\#72](https://github.com/dynamic/silverstripe-shopify/pull/72) ([jsirish](https://github.com/jsirish))
- REFACTOR ShopifyImportTask - adjustments and refinements [\#71](https://github.com/dynamic/silverstripe-shopify/pull/71) ([jsirish](https://github.com/jsirish))
- BUGFIX ShopifyImportTask - handle publish on virtual pages [\#70](https://github.com/dynamic/silverstripe-shopify/pull/70) ([jsirish](https://github.com/jsirish))
- REFACTOR ShopifyImportTask - remove legacy importCollects\(\) [\#69](https://github.com/dynamic/silverstripe-shopify/pull/69) ([jsirish](https://github.com/jsirish))
- BUGFIX ShopifyImportTask - handle outdated Virtual pages [\#67](https://github.com/dynamic/silverstripe-shopify/pull/67) ([jsirish](https://github.com/jsirish))
- BUGFIX ShopifyProduct - adjustments to $overlay\_options [\#66](https://github.com/dynamic/silverstripe-shopify/pull/66) ([jsirish](https://github.com/jsirish))
- FEATURE ShopifyProduct - BuyButton - Modal button [\#65](https://github.com/dynamic/silverstripe-shopify/pull/65) ([jsirish](https://github.com/jsirish))
- FEATURE ShopifyClient - productMedia\(\) query [\#63](https://github.com/dynamic/silverstripe-shopify/pull/63) ([jsirish](https://github.com/jsirish))
- REFACTOR remove many\_many between ShopifyCollection and ShopifyProduct [\#62](https://github.com/dynamic/silverstripe-shopify/pull/62) ([jsirish](https://github.com/jsirish))
- FEATURE ShopifyCollectionController [\#58](https://github.com/dynamic/silverstripe-shopify/pull/58) ([jsirish](https://github.com/jsirish))
- Enhancement/order history [\#57](https://github.com/dynamic/silverstripe-shopify/pull/57) ([mak001](https://github.com/mak001))
- REFACTOR require osiset/basic-shopify-api for Shopify API [\#56](https://github.com/dynamic/silverstripe-shopify/pull/56) ([muskie9](https://github.com/muskie9))
- BUGFIX ShopifyProduct - hide from menus by default [\#55](https://github.com/dynamic/silverstripe-shopify/pull/55) ([jsirish](https://github.com/jsirish))
- FEATURE RelatedProductsExtension [\#49](https://github.com/dynamic/silverstripe-shopify/pull/49) ([jsirish](https://github.com/jsirish))

## [1.0.0-alpha1](https://github.com/dynamic/silverstripe-shopify/tree/1.0.0-alpha1) (2021-02-11)

[Full Changelog](https://github.com/dynamic/silverstripe-shopify/compare/c8b99471f90888f0c08a33e2e68847371328ec16...1.0.0-alpha1)

**Merged pull requests:**

- TESTS Additional testing [\#38](https://github.com/dynamic/silverstripe-shopify/pull/38) ([jsirish](https://github.com/jsirish))
- BUGFIX ShopifyImportTask - Variants are not versioned [\#37](https://github.com/dynamic/silverstripe-shopify/pull/37) ([jsirish](https://github.com/jsirish))
- FEATURE Add to cart - update default options [\#34](https://github.com/dynamic/silverstripe-shopify/pull/34) ([jsirish](https://github.com/jsirish))
- FEATURE Product, Collection - publish on import if ifPublished [\#33](https://github.com/dynamic/silverstripe-shopify/pull/33) ([jsirish](https://github.com/jsirish))
- FEATURE initial en.yml lang file [\#32](https://github.com/dynamic/silverstripe-shopify/pull/32) ([jsirish](https://github.com/jsirish))
- FEATURE BuyButton integration initial [\#31](https://github.com/dynamic/silverstripe-shopify/pull/31) ([jsirish](https://github.com/jsirish))
- FEATURE Product getters [\#30](https://github.com/dynamic/silverstripe-shopify/pull/30) ([jsirish](https://github.com/jsirish))
- BUGFIX update config classnames, catalog admin settings [\#29](https://github.com/dynamic/silverstripe-shopify/pull/29) ([jsirish](https://github.com/jsirish))
- FEATURE ElementProducts initial [\#28](https://github.com/dynamic/silverstripe-shopify/pull/28) ([jsirish](https://github.com/jsirish))
- Update composer.json [\#27](https://github.com/dynamic/silverstripe-shopify/pull/27) ([jsirish](https://github.com/jsirish))
- CI code coverage [\#26](https://github.com/dynamic/silverstripe-shopify/pull/26) ([jsirish](https://github.com/jsirish))
- FEATURE ShopifyMember [\#25](https://github.com/dynamic/silverstripe-shopify/pull/25) ([jsirish](https://github.com/jsirish))
- FEATURE Collection and File - CMS Design [\#24](https://github.com/dynamic/silverstripe-shopify/pull/24) ([jsirish](https://github.com/jsirish))
- FEATURE ShopifyProduct, ShopifyVariant - CMS design [\#23](https://github.com/dynamic/silverstripe-shopify/pull/23) ([jsirish](https://github.com/jsirish))
- REFACTOR Model Classname updates [\#22](https://github.com/dynamic/silverstripe-shopify/pull/22) ([jsirish](https://github.com/jsirish))
- README update config, requirements, badges [\#21](https://github.com/dynamic/silverstripe-shopify/pull/21) ([jsirish](https://github.com/jsirish))
- CI setup initial GitHub workflow [\#20](https://github.com/dynamic/silverstripe-shopify/pull/20) ([jsirish](https://github.com/jsirish))
- CI initial test setup [\#3](https://github.com/dynamic/silverstripe-shopify/pull/3) ([jsirish](https://github.com/jsirish))



\* *This Changelog was automatically generated by [github_changelog_generator](https://github.com/github-changelog-generator/github-changelog-generator)*
