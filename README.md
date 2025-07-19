# CalendarInput

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alvleont/calendar-input.svg?style=flat-square)](https://packagist.org/packages/alvleont/calendar-input)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/alvleont/calendar-input/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/alvleont/calendar-input/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/alvleont/calendar-input/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/alvleont/calendar-input/actions?query=workflow%3A)
[![Total Downloads](https://img.shields.io/packagist/dt/alvleont/calendar-input.svg?style=flat-square)](https://packagist.org/packages/alvleont/calendar-input)

A simple calendar input for FilamentPHP.

![Example](calendar-input_example.png)

## Installation

You can install the package via composer:

```bash
composer require alvleont/calendar-input
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="calendar-input-views"
```

Usage

```php
use Alvleont\CalendarInput\CalendarInput;

class ProductResource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Single date selection (default)
            CalendarInput::make('date')
                ->name('Calendar')
		->minDate('2025-06-01') //You can use the date you want, or null (optional method)
		->maxDate('2029-09-30') //You can use the date you want, or null (optional method)
		->disabledDates([]) //Optional Method - disables specific dates
		->enabledDates(['2025-12-25', '2025-12-31']) //Optional Method - only enables specific dates (disables all others)
		->disabled(), //In case It'll be disabled or for the view page.
		
            // Range selection
            CalendarInput::make('date_range')
                ->name('Date Range')
                ->rangeSelection() //Enable range selection mode
                ->enabledDates(['2025-12-25', '2025-12-26', '2025-12-27', '2025-12-28']) //Only continuous ranges within these dates are allowed
                ->minDate('2025-12-01')
                ->maxDate('2025-12-31'),
        ]);
    }
}
```

### Range Selection Features

- **Continuous Validation**: Ensures no disabled dates exist between start and end dates
- **Enabled Dates Support**: When `enabledDates()` is used, ranges can only be selected within those dates
- **Visual Feedback**: 
  - Range start/end dates are highlighted in primary color
  - Dates in range are highlighted with background color
  - During selection, the start date shows a different color until range is completed
- **State Format**: Range selection returns an array: `['2025-12-25', '2025-12-27']`

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

You can contribute to the package. Just PR your code and It will be reviewed.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alvaro León Torres](https://github.com/alvleont)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
