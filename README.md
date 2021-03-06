# DMS Importer Plugin

A WordPress DMS Importer

## Installation


## Changelog
## 1.0.15
#### Release Date - 2022-06-23
* Add Image fallback configuration
* Image fallback should be shown on detail and overview page
* Bugfix: Empty image should now return an empty result instead of aws data
## 1.0.14
#### Release Date - 2022-06-22
* Add Sku Filter to show Master or Article number
* Add multiple notifications
* Add Reset Transients button on Importer Tab
* Bugfix: Add several object property checks on getter methods to prevent fatal errors
## 1.0.13
#### Release Date - 2022-03-21

* Use product detail page image as og:image

## 1.0.12
#### Release Date - 2022-03-21

* Overwrite product thumbnail through post thumbnail hook
* Add og:image meta tags from aws data sources
## 1.0.11
#### Release Date - 2022-03-04

* Bugfix: SiTi & operating instructions aws link issue solved
## 1.0.10
#### Release Date - 2022-02-28

* Improve select and multiselect attribute function
* Reset order number and order quantity before import
## 1.0.9
#### Release Date - 2022-02-25

* Bugfix: Downloads HTML typo
## 1.0.8
#### Release Date - 2022-02-25

* Text-domain typo
## 1.0.7
#### Release Date - 2022-02-25

* Translate some text-domain values
## 1.0.6
#### Release Date - 2022-02-25

* Add missing text domain translations
## 1.0.5
#### Release Date - 2022-02-23

* Remove packaging type from product detail page (also for variants)
* Add sds and operating instructions also for variants
* Save brand to taxonomy tax_products_brands
## 1.0.4
#### Release Date - 2022-02-22

* Remove packaging type from product detail page
* Cradle to Cradle certificate rework
## 1.0.3
#### Release Date - 2022-02-22

* Bugfix: multiselect fields return an empty array in case of empty values
## 1.0.2
#### Release Date - 2022-02-21

* Add product categories
## 1.0.1
#### Release Date - 2022-02-18

* Bugfix: Minor guzzle http call
## 1.0.0
#### Release Date - 2022-02-18

* Production release
* Update operating instructions DE
* Update downloads html field
* Bugfix: settings on local environment
## 0.0.1
#### Release Date - 2021-08-11

* First Iteration
* Possibility to connect to the DMS with REST API and a specific Token
* Filter import by language and category
* Hooks for detail and overview page