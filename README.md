# kevin. Magento 2 module

Payments collection solution to your Magento 2 e-commerce platform.

BEFORE YOU START MAKE SURE YOU HAVE RECEIVED CLIENT ID AND CLIENT SECRET FROM KEVIN.

## Prerequisites

- Magento 2.x
- PHP 7.0 or later

## Installation
```
composer require getkevin/kevin-magento2
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
php bin/magento setup:di:compile
php bin/magento cache:clean
```
**Please note that FTP installation will not work as this module has requirements that will be auto installed when using composer**

## Contributing
We are using PHP CS Fixer GitHub action to conduct code style checks for each commit and pull request. Make sure to run `composer fix-style` before
committing changes to make sure your code matches our style standards. Pull requests with failed style checks will not
be approved.

*WARNING*: we use risky rules so make sure to check for breaking style fixes.

Style errors and violated rules log can be viewed by running `composer check-style` command.