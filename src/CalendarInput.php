<?php

namespace Alvleont\CalendarInput;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use DateTime;
use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;

/**
 * Class CalendarInput
 *
 * A custom Filament form field component for selecting dates using a calendar input.
 * Supports min/max date constraints, disabled dates, custom date formats, and localization.
 *
 *
 * @property string $view The Blade view used to render the calendar input.
 * @property CarbonInterface|string|Closure|null $maxDate The maximum selectable date.
 * @property CarbonInterface|string|Closure|null $minDate The minimum selectable date.
 * @property array<DateTime|string>|Closure $disabledDates The dates that should be disabled in the calendar.
 * @property array<DateTime|string>|Closure $enabledDates The dates that should be enabled in the calendar (all other dates disabled).
 * @property bool|Closure $rangeSelection Whether to enable range selection mode.
 * @property string|Closure|null $format The date format used for parsing and displaying dates.
 * @property string|Closure|null $calendarLocale The locale used for calendar display.
 */
class CalendarInput extends Field
{
    /**
     * @var string The Blade view used to render the calendar input.
     */
    protected string $view = 'calendar-input::calendar-input';

    /**
     * @var CarbonInterface|string|Closure|null The maximum selectable date.
     */
    protected CarbonInterface | string | Closure | null $maxDate = null;

    /**
     * @var CarbonInterface|string|Closure|null The minimum selectable date.
     */
    protected CarbonInterface | string | Closure | null $minDate = null;

    /**
     * @var array<DateTime | string> | Closure The dates that should be disabled in the calendar.
     */
    protected array | Closure $disabledDates = [];

    /**
     * @var array<DateTime | string> | Closure The dates that should be enabled in the calendar (all other dates disabled).
     */
    protected array | Closure $enabledDates = [];

    /**
     * @var bool|Closure Whether to enable range selection mode.
     */
    protected bool | Closure $rangeSelection = false;

    /**
     * @var string|Closure|null The date format used for parsing and displaying dates.
     */
    protected string | Closure | null $format = null;

    /**
     * @var string|Closure|null The locale used for calendar display.
     */
    protected string | Closure | null $calendarLocale = null;

    /**
     * Set up the CalendarInput component.
     *
     * Hydrates and dehydrates the state, applies validation rules, and sets up date parsing logic.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(static function (CalendarInput $component, $state): void {
            if (blank($state)) {
                return;
            }

            // Handle range selection mode
            if ($component->isRangeSelection()) {
                if (is_array($state) && count($state) === 2) {
                    $startDate = $state[0];
                    $endDate = $state[1];

                    try {
                        if (! $startDate instanceof CarbonInterface) {
                            $startDate = Carbon::parse($startDate, config('app.timezone'));
                        }
                        if (! $endDate instanceof CarbonInterface) {
                            $endDate = Carbon::parse($endDate, config('app.timezone'));
                        }

                        $component->state([$startDate->toDateString(), $endDate->toDateString()]);
                    } catch (InvalidFormatException $exception) {
                        $component->state(null);
                    }

                    return;
                } elseif (is_string($state) && str_contains($state, ',')) {
                    $dates = explode(',', $state);
                    if (count($dates) === 2) {
                        try {
                            $startDate = Carbon::parse(trim($dates[0]), config('app.timezone'));
                            $endDate = Carbon::parse(trim($dates[1]), config('app.timezone'));
                            $component->state([$startDate->toDateString(), $endDate->toDateString()]);
                        } catch (InvalidFormatException $exception) {
                            $component->state(null);
                        }

                        return;
                    }
                }
                $component->state(null);

                return;
            }

            // Handle single date selection mode
            if (! $state instanceof CarbonInterface) {
                try {
                    $state = Carbon::createFromFormat($component->getFormat(), (string) $state, config('app.timezone'));
                } catch (InvalidFormatException $exception) {
                    try {
                        $state = Carbon::parse($state, config('app.timezone'));
                    } catch (InvalidFormatException $exception) {
                        $component->state(null);

                        return;
                    }
                }
            }

            $component->state($state->toDateString());
        });

        $this->dehydrateStateUsing(static function (CalendarInput $component, $state) {
            if (blank($state)) {
                return null;
            }

            // Handle range selection mode
            if ($component->isRangeSelection()) {
                if (is_array($state) && count($state) === 2) {
                    try {
                        $startDate = Carbon::parse($state[0]);
                        $endDate = Carbon::parse($state[1]);

                        return [$startDate->format($component->getFormat()), $endDate->format($component->getFormat())];
                    } catch (InvalidFormatException $exception) {
                        return null;
                    }
                }

                return null;
            }

            // Handle single date selection mode
            if (! $state instanceof CarbonInterface) {
                $state = Carbon::parse($state);
            }

            return $state->format($component->getFormat());
        });

        $this->rule(static function (CalendarInput $component): string {
            if ($component->isRangeSelection()) {
                return 'array|size:2';
            }

            return 'date';
        });
    }

    /**
     * Set the maximum selectable date.
     */
    public function maxDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->maxDate = $date;

        $this->rule(static function (CalendarInput $component) {
            return "before_or_equal:{$component->getMaxDate()}";
        }, static fn (CalendarInput $component): bool => (bool) $component->getMaxDate());

        return $this;
    }

    /**
     * Set the minimum selectable date.
     */
    public function minDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->minDate = $date;

        $this->rule(static function (CalendarInput $component) {
            return "after_or_equal:{$component->getMinDate()}";
        }, static fn (CalendarInput $component): bool => (bool) $component->getMinDate());

        return $this;
    }

    /**
     * Set the dates that should be disabled in the calendar.
     *
     * @param  array<DateTime | string> | Closure  $dates
     */
    public function disabledDates(array | Closure $dates): static
    {
        $this->disabledDates = $dates;

        return $this;
    }

    /**
     * Set the dates that should be enabled in the calendar (all other dates disabled).
     *
     * @param  array<DateTime | string> | Closure  $dates
     */
    public function enabledDates(array | Closure $dates): static
    {
        $this->enabledDates = $dates;

        return $this;
    }

    /**
     * Enable range selection mode.
     */
    public function rangeSelection(bool | Closure $rangeSelection = true): static
    {
        $this->rangeSelection = $rangeSelection;

        return $this;
    }

    /**
     * Set the date format for the calendar input.
     */
    public function format(string | Closure | null $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set the locale for calendar display.
     */
    public function calendarLocale(string | Closure | null $locale): static
    {
        $this->calendarLocale = $locale;

        return $this;
    }

    /**
     * Get the date format for the calendar input.
     */
    public function getFormat(): string
    {
        return $this->evaluate($this->format) ?? 'Y-m-d';
    }

    /**
     * Check if range selection mode is enabled.
     */
    public function isRangeSelection(): bool
    {
        return $this->evaluate($this->rangeSelection) ?? false;
    }

    /**
     * Get the maximum selectable date as a string.
     */
    public function getMaxDate(): ?string
    {
        $maxDate = $this->evaluate($this->maxDate);

        if ($maxDate instanceof CarbonInterface) {
            return $maxDate->toDateString();
        }

        if (is_string($maxDate)) {
            try {
                return Carbon::parse($maxDate)->toDateString();
            } catch (InvalidFormatException $exception) {
                return null;
            }
        }

        return $maxDate;
    }

    /**
     * Get the minimum selectable date as a string.
     */
    public function getMinDate(): ?string
    {
        $minDate = $this->evaluate($this->minDate);

        if ($minDate instanceof CarbonInterface) {
            return $minDate->toDateString();
        }

        if (is_string($minDate)) {
            try {
                return Carbon::parse($minDate)->toDateString();
            } catch (InvalidFormatException $exception) {
                return null;
            }
        }

        return $minDate;
    }

    /**
     * Get the disabled dates as an array of strings.
     *
     * @return array<string>
     */
    public function getDisabledDates(): array
    {
        $disabledDates = $this->evaluate($this->disabledDates);

        return collect($disabledDates)->map(function ($date) {
            if ($date instanceof CarbonInterface) {
                return $date->toDateString();
            }

            if (is_string($date)) {
                try {
                    return Carbon::parse($date)->toDateString();
                } catch (InvalidFormatException $exception) {
                    return null;
                }
            }

            return null;
        })->filter()->values()->toArray();
    }

    /**
     * Get the enabled dates as an array of strings.
     *
     * @return array<string>
     */
    public function getEnabledDates(): array
    {
        $enabledDates = $this->evaluate($this->enabledDates);

        return collect($enabledDates)->map(function ($date) {
            if ($date instanceof CarbonInterface) {
                return $date->toDateString();
            }

            if (is_string($date)) {
                try {
                    return Carbon::parse($date)->toDateString();
                } catch (InvalidFormatException $exception) {
                    return null;
                }
            }

            return null;
        })->filter()->values()->toArray();
    }

    /**
     * Check if a date range is valid (continuous with no disabled dates).
     */
    public function isValidDateRange(string $startDate, string $endDate): bool
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            // Ensure start date is before or equal to end date
            if ($start->gt($end)) {
                return false;
            }

            $enabledDates = $this->getEnabledDates();
            $disabledDates = $this->getDisabledDates();
            $minDate = $this->getMinDate();
            $maxDate = $this->getMaxDate();

            // Check each date in the range
            $current = $start->copy();
            while ($current->lte($end)) {
                $dateString = $current->toDateString();

                // Check min/max date constraints
                if ($minDate && $dateString < $minDate) {
                    return false;
                }
                if ($maxDate && $dateString > $maxDate) {
                    return false;
                }

                // Check enabled dates (if specified, only these dates are allowed)
                if (! empty($enabledDates)) {
                    if (! in_array($dateString, $enabledDates)) {
                        return false;
                    }
                } else {
                    // Check disabled dates
                    if (in_array($dateString, $disabledDates)) {
                        return false;
                    }
                }

                $current->addDay();
            }

            return true;
        } catch (InvalidFormatException $exception) {
            return false;
        }
    }

    /**
     * Get the calendar data to pass to the frontend component.
     *
     * @return array{
     *     maxDate: string|null,
     *     minDate: string|null,
     *     disabledDates: array<string>,
     *     enabledDates: array<string>,
     *     rangeSelection: bool
     * }
     */
    public function getCalendarData(): array
    {
        return [
            'maxDate' => $this->getMaxDate(),
            'minDate' => $this->getMinDate(),
            'disabledDates' => $this->getDisabledDates(),
            'enabledDates' => $this->getEnabledDates(),
            'rangeSelection' => $this->isRangeSelection(),
        ];
    }

    /**
     * Determine if the calendar input is disabled.
     */
    public function getIsDisabled(): bool
    {
        return $this->evaluate($this->isDisabled) || $this->getContainer()->isDisabled();
    }

    /**
     * Get the days of the week for the calendar, localized.
     *
     * @return array<string>
     */
    public function getDaysOfWeek(): array
    {
        $locale = $this->getCalendarLocale();
        $daysOfWeek = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->startOfWeek(\Carbon\Carbon::SUNDAY)->addDays($i);
            $daysOfWeek[] = $date->locale($locale)->translatedFormat('D');
        }

        return $daysOfWeek;
    }

    /**
     * Get the months of the year for the calendar, localized.
     *
     * @return array<string>
     */
    public function getMonthsOfYear(): array
    {
        $locale = $this->getCalendarLocale();
        $monthsOfYear = [];

        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::createFromDate(null, $i + 1, 1);
            $monthsOfYear[] = ucfirst($date->locale($locale)->translatedFormat('F'));
        }

        return $monthsOfYear;
    }

    /**
     * Get the current month and year as a formatted string, localized.
     */
    public function getCurrentMonthYear(): string
    {
        $locale = $this->getCalendarLocale();

        // If there's a selected date, use that date for the month/year
        $date = $this->getState() ? Carbon::parse($this->getState()) : now();

        return $date->locale($locale)->translatedFormat('F Y');
    }

    /**
     * Get the short names of the days of the week for the calendar, localized.
     *
     * @return array<string>
     */
    public function getDaysOfWeekShort(): array
    {
        $locale = $this->getCalendarLocale();
        $daysOfWeek = [];

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays($i);
            // Use 'D' for 3 letters (Mon), or create your own format
            $dayName = $date->locale($locale)->format('D');
            // If you want only 2 letters: substr($dayName, 0, 2)
            $daysOfWeek[] = $dayName;
        }

        return $daysOfWeek;
    }

    /**
     * Get the locale used for calendar display.
     * Falls back to app locale if no calendar locale is set.
     */
    protected function getCalendarLocale(): string
    {
        return $this->evaluate($this->calendarLocale) ?? app()->getLocale();
    }

    /**
     * Get the calendar locale for use on the frontend.
     */
    public function getCalendarLocaleForFrontend(): string
    {
        return $this->getCalendarLocale();
    }
}
