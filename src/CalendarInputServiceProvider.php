<?php

namespace Alvleont\CalendarInput;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CalendarInputServiceProvider extends PackageServiceProvider
{
    public static string $name = 'calendar-input';

    public static string $viewNamespace = 'calendar-input';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasViews();

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void {}

    protected function getAssetPackageName(): ?string
    {
        return 'alvleont/calendar-input';
    }
}
