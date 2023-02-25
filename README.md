# Laravel Invoice Package 📮

![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/dnj/dnj/laravel-invoice)
![GitHub all releases](https://img.shields.io/github/downloads/dnj/laravel-invoice/total)
![GitHub](https://img.shields.io/github/license/dnj/laravel-invoice)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/dnj/laravel-invoice/ci.yml)

## Introduction

This package provides a simple and flexible interface for managing invoices and payments in your Laravel application.

* Feature Include:
    * `Flexible Product Definition:`

      The package allows for defining a product in a flexible way by specifying its price, discount, currency, count,
      distribution plan, and localized details. This feature enables the user to create and manage different types of
      products for invoicing.

    * `Payment Processing:`

      The package allows adding payments to an invoice, approving or rejecting a payment, and getting the payment
      status.
      This feature makes it easy to track and manage payments for an invoice.

    * `Multi-language Support:`

      The package supports multiple languages by allowing the user to provide localized details for a product or an
      invoice.
      This feature enables users to create invoices in different languages for a global audience.

    * `Multiple currency support:`

      The package allows the creation of invoices in multiple currencies. Each invoice can have a different currency, 
      and the system will automatically calculate the conversion rates based on the current exchange rates.

  * `Customizable templates:`
    
  * The package provides customizable invoice templates that can be easily modified to match your
    company's branding. You can change the layout, add your logo, and customize the content of the invoices.


* Latest versions of PHP and PHPUnit and PHPCSFixer
* Best practices applied:
    * [`README.md`](https://github.com/dnj/laravel-invoice/blob/master/README.md) (badges included)
    * [`LICENSE`](https://github.com/dnj/laravel-invoice/blob/master/LICENSE)
    * [`composer.json`](https://github.com/dnj/laravel-invoice/blob/master/composer.json)
    * [`phpunit.xml`](https://github.com/dnj/laravel-invoice/blob/master/phpunit.xml)
    * [`.gitignore`](https://github.com/dnj/laravel-invoice/blob/master/.gitignore)
    * [`.php-cs-fixer.php`](https://github.com/dnj/laravel-invoice/blob/master/.php-cs-fixer.php)

## Installation

Require this package with [composer](https://getcomposer.org/).

```shell
composer require dnj/laravel-invoice
```

Laravel uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.

#### Copy the package config to your local config with the publish command:

```shell
php artisan vendor:publish --provider="dnj\Invoice\InvoiceServiceProvider" --tag="config"
```

#### Config file

config/account.php

```php
<?php

return [
    'user_model' => null, // set user model 
];

```

config/invoice.php

```php
<?php

return [
    // Define your user model class for connect invoice to users.
    'user_model' => null,
];
```

## Usage

This package provides several interfaces that you can use to interact with invoices, payments,
and payment methods in your Laravel application.

### Invoice Management

The `IInvoiceManager` interface provides methods for creating, updating, and deleting invoices,
as well as adding products and payments to existing invoices. Here's an example of how to create a new invoice:

```php
use dnj\Invoice\Contracts\IInvoiceManager;

class MyController extends Controller
{
    private $invoiceManager;

    public function __construct(IInvoiceManager $invoiceManager)
    {
        $this->invoiceManager = $invoiceManager;
    }

    public function createInvoice()
    {
        $userId = 123;
        $currencyId = 1;
        $products = [
            [
                'price' => 10.00,
                'discount' => 0.00,
                'currencyId' => 1,
                'count' => 1,
                'distributionPlan' => [100],
                'localizedDetails' => [
                    'en' => ['title' => 'Product 1'],
                    'fr' => ['title' => 'Produit 1'],
                ],
                'meta' => [
                    'size' => 'large',
                    'color' => 'blue',
                ],
            ],
            [
                'price' => 20.00,
                'discount' => 5.00,
                'currencyId' => 1,
                'count' => 2,
                'distributionPlan' => [60, 40],
                'localizedDetails' => [
                    'en' => ['title' => 'Product 2'],
                    'fr' => ['title' => 'Produit 2'],
                ],
            ],
        ];
        $localizedDetails = [
            'en' => ['title' => 'Invoice'],
            'fr' => ['title' => 'Facture'],
        ];
        $meta = [
            'notes' => 'Some notes about this invoice',
        ];

        $invoice = $this->invoiceManager->create($userId, $currencyId, $products, $localizedDetails, $meta);
    }
}
```

### Payment Methods

The `IPaymentMethod` interface provides a simple interface for defining and using payment methods
in your Laravel application. Here's an example of how to define a payment method:

```php
use dnj\Invoice\Contracts\IPaymentMethod;

class MyPaymentMethod implements IPaymentMethod
{
    public function charge($amount, $currency)
    {
        // Charge the specified amount using this payment method
    }
}
```

### Models

This package provides several models that represent invoices, payments, and products:

* `IInvoice`: represents an invoice
* `IPayment`: represents a payment
* `IProduct`: represents a product in an invoice

**NOTICE**:
These models provide methods for retrieving and updating the properties of the corresponding entities in your Laravel
application.
---

## Testing

You can run unit tests with PHP Unit:

```php
./vendor/bin/phpunit
```

## Contribution

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any
contributions you make are greatly appreciated.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also
simply open an issue with the tag "enhancement". Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security

If you discover any security-related issues, please email [security@dnj.co.ir](mailto:security@dnj.co.ir) instead of
using the issue tracker.

## License

The MIT License (MIT). Please
see [License File](https://github.com/dnj/laravel-invoice/blob/master/LICENSE) for more information.
