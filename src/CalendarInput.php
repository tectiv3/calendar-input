<?php

namespace Alvleont\CalendarInput;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use DateTime;
use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;

class CalendarInput extends Field
{
    protected string $view = 'filament.forms.components.calendar-picker';

    protected CarbonInterface | string | Closure | null $maxDate = null;

    protected CarbonInterface | string | Closure | null $minDate = null;

    /**
     * @var array<DateTime | string> | Closure
     */
    protected array | Closure $disabledDates = [];

    protected string | Closure | null $format = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(static function (CalendarInput $component, $state): void {
            if (blank($state)) {
                return;
            }

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

            if (! $state instanceof CarbonInterface) {
                $state = Carbon::parse($state);
            }

            return $state->format($component->getFormat());
        });

        $this->rule('date');
    }

    public function maxDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->maxDate = $date;

        $this->rule(static function (CalendarInput $component) {
            return "before_or_equal:{$component->getMaxDate()}";
        }, static fn (CalendarInput $component): bool => (bool) $component->getMaxDate());

        return $this;
    }

    public function minDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->minDate = $date;

        $this->rule(static function (CalendarInput $component) {
            return "after_or_equal:{$component->getMinDate()}";
        }, static fn (CalendarInput $component): bool => (bool) $component->getMinDate());

        return $this;
    }

    /**
     * @param  array<DateTime | string> | Closure  $dates
     */
    public function disabledDates(array | Closure $dates): static
    {
        $this->disabledDates = $dates;

        return $this;
    }

    public function format(string | Closure | null $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->evaluate($this->format) ?? 'Y-m-d';
    }

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
     * Get data to pass to the frontend component
     */
    public function getCalendarData(): array
    {
        return [
            'maxDate' => $this->getMaxDate(),
            'minDate' => $this->getMinDate(),
            'disabledDates' => $this->getDisabledDates(),
        ];
    }
}
