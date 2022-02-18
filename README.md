# kevin. Magento 2 module

Payments collection solution to your Magento 2 e-commerce platform.

BEFORE YOU START MAKE SURE YOU HAVE RECEIVED CLIENT ID AND CLIENT SECRET FROM KEVIN.

## Prerequisites

- Magento 2.x
- PHP 5.6 or later

## Installation
```
composer require getkevin/kevin-magento2
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
php bin/magento setup:di:compile
php bin/magento cache:clean
```
**Please note that FTP installation will not work as this module has requirements that will be auto installed when using composer**
