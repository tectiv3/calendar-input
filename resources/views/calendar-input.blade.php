<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:ignore class="w-full">
        <div x-data="() => ({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            internalState: null,
            currentDate: new Date(),
            selectedDate: null,
            daysOfWeek: @js($getDaysOfWeek()),
            maxDate: @js($getMaxDate()),
            minDate: @js($getMinDate()),
            disabledDates: @js($getDisabledDates()),
            enabledDates: @js($getEnabledDates()),
            isDisabled: @js($getIsDisabled()),
            initialMonthYear: @js($getCurrentMonthYear()),
            monthsOfYear: @js($getMonthsOfYear()),
            locale: @js($getCalendarLocaleForFrontend()),

            getMonthYearForDate(date) {
                const year = date.getFullYear();
                const month = date.getMonth();
                return `${this.monthsOfYear[month]} ${year}`;
            },

            initializeFromState() {
                if (this.state) {
                    const date = new Date(this.state);
                    if (!isNaN(date.getTime())) {
                        this.selectedDate = date;
                        this.currentDate = new Date(date.getFullYear(), date.getMonth(), 1);
                        this.internalState = this.state;
                    }
                } else {
                    this.selectedDate = null;
                    this.currentDate = new Date();
                    this.internalState = null;
                    this.state = null;
                }
            },

            syncFromState() {
                if (this.state !== this.internalState) {
                    this.internalState = this.state;
                    if (this.state) {
                        const date = new Date(this.state);
                        if (!isNaN(date.getTime())) {
                            this.selectedDate = date;
                            this.currentDate = new Date(date.getFullYear(), date.getMonth(), 1);
                        }
                    } else {
                        this.selectedDate = null;
                    }
                }
            },

            isDateDisabled(date) {
                const dateString = date.toISOString().split('T')[0];

                // If enabled dates are specified, only allow those dates
                if (this.enabledDates && this.enabledDates.length > 0) {
                    return !this.enabledDates.includes(dateString);
                }

                // Check if date is in disabled dates array
                if (this.disabledDates && this.disabledDates.includes(dateString)) {
                    return true;
                }

                // Check if date is before min date
                if (this.minDate && dateString < this.minDate) {
                    return true;
                }

                // Check if date is after max date
                if (this.maxDate && dateString > this.maxDate) {
                    return true;
                }

                return false;
            },

            canNavigateToPreviousMonth() {
                if (this.isDisabled) return false;
                if (!this.minDate) return true;

                const minDateObj = new Date(this.minDate);
                const prevMonth = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);

                return prevMonth >= new Date(minDateObj.getFullYear(), minDateObj.getMonth(), 1);
            },

            canNavigateToNextMonth() {
                if (this.isDisabled) return false;
                if (!this.maxDate) return true;

                const maxDateObj = new Date(this.maxDate);
                const nextMonth = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);

                return nextMonth <= new Date(maxDateObj.getFullYear(), maxDateObj.getMonth(), 1);
            },

            isDateSelectable(dateObj) {
                if (this.isDisabled) return false;
                return dateObj.isCurrentMonth && !this.isDateDisabled(dateObj.date);
            },

            get calendarWeeks() {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = firstDay.getDay();

                const prevMonth = new Date(year, month - 1, 0);
                const daysInPrevMonth = prevMonth.getDate();

                const days = [];

                // Previous month days
                for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                    const day = daysInPrevMonth - i;
                    const date = new Date(year, month - 1, day);
                    days.push({
                        day,
                        date,
                        isCurrentMonth: false,
                        isSelected: this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: this.isDateDisabled(date),
                        key: `prev-${day}`
                    });
                }

                // Current month days
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isDisabled = this.isDateDisabled(date);
                    days.push({
                        day,
                        date,
                        isCurrentMonth: true,
                        isSelected: this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: isDisabled,
                        key: `current-${day}`
                    });
                }

                // Next month days
                const remainingDays = 42 - days.length;
                for (let day = 1; day <= remainingDays; day++) {
                    const date = new Date(year, month + 1, day);
                    days.push({
                        day,
                        date,
                        isCurrentMonth: false,
                        isSelected: this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: this.isDateDisabled(date),
                        key: `next-${day}`
                    });
                }

                const weeks = [];
                for (let i = 0; i < days.length; i += 7) {
                    weeks.push({
                        key: `week-${Math.floor(i / 7)}`,
                        days: days.slice(i, i + 7)
                    });
                }

                return weeks;
            },

            previousMonth() {
                if (this.canNavigateToPreviousMonth()) {
                    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
                }
            },

            nextMonth() {
                if (this.canNavigateToNextMonth()) {
                    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
                }
            },

            selectDate(dateObj) {
                if (this.isDateSelectable(dateObj)) {
                    // If already selected, deselect it
                    if (this.isSameDay(dateObj.date, this.selectedDate)) {
                        this.selectedDate = null;
                        this.internalState = null;
                        this.state = null;
                    } else {
                        // Select the new date
                        this.selectedDate = new Date(dateObj.date);
                        this.internalState = this.selectedDate.toISOString().split('T')[0];
                        this.state = this.internalState;
                    }
                }
            },

            goToToday() {
                const today = new Date();
                if (!this.isDateDisabled(today) && !this.isDisabled) {
                    this.currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    this.selectedDate = today;
                    this.internalState = today.toISOString().split('T')[0];
                    this.state = this.internalState;
                }
            },

            clearSelection() {
                if (!this.isDisabled) {
                    this.selectedDate = null;
                    this.internalState = null;
                    this.state = null;
                }
            },

            isSameDay(date1, date2) {
                return date1?.getDate() === date2?.getDate() &&
                    date1?.getMonth() === date2?.getMonth() &&
                    date1?.getFullYear() === date2?.getFullYear();
            },

            formatStateDate(dateString) {
                const date = new Date(dateString);
                return isNaN(date) ? dateString : date.toLocaleDateString(this.locale, {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            },

            formatDateLabel(date) {
                return date.toLocaleDateString(this.locale, {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            },

            getCurrentMonthYearFormatted() {
                return this.initialMonthYear || this.currentDate.toLocaleDateString(this.locale, {
                    year: 'numeric',
                    month: 'long'
                });
            },

            getPreviousMonthLabel() {
                return this.$t ? this.$t('datepicker.previous_month') : 'Previous month';
            },

            getNextMonthLabel() {
                return this.$t ? this.$t('datepicker.next_month') : 'Next month';
            }

        })"
        x-init="$nextTick(() => {
            initializeFromState();
            $watch('state', () => syncFromState());
        });"
        {{ $getExtraAttributeBag() }}>
            <div class="relative bg-white border border-gray-200 rounded-lg shadow-sm isolate"
                :class="{ 'opacity-75': isDisabled }">
                <!-- Calendar container -->
                <div class="relative flex justify-center gap-4 p-4">
                    <div>
                        <!-- Month heading with navigation -->
                        <div class="flex items-center justify-between h-10 px-2 mb-4 sm:h-8">
                            <button @click="previousMonth()" type="button" :disabled="!canNavigateToPreviousMonth()"
                                class="flex items-center justify-center transition-colors rounded-lg size-8 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-white/5 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-zinc-400"
                                :aria-label="getPreviousMonthLabel()">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="size-4">
                                    <path fill-rule="evenodd"
                                        d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>

                            <div class="text-sm font-medium text-zinc-800 dark:text-white"
                                x-text="getMonthYearForDate(currentDate)"></div>

                            <button @click="nextMonth()" type="button" :disabled="!canNavigateToNextMonth()"
                                class="flex items-center justify-center transition-colors rounded-lg size-8 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-white/5 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-zinc-400"
                                :aria-label="getNextMonthLabel()">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="size-4">
                                    <path fill-rule="evenodd"
                                        d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <table>
                            <thead>
                                <tr class="flex w-full">
                                    <template x-for="day in daysOfWeek">
                                        <th scope="col"
                                            class="flex items-center text-sm font-medium size-11 sm:size-11 text-zinc-500 dark:text-zinc-300">
                                            <div class="w-full text-center" x-text="day"></div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-for="week in calendarWeeks" :key="week.key">
                                    <tr class="flex w-full not-first-of-type:mt-1">
                                        <template x-for="date in week.days" :key="date.key">
                                            <td class="p-0 data-in-range:bg-zinc-100 dark:data-in-range:bg-white/10 first-of-type:rounded-s-lg last-of-type:rounded-e-lg"
                                                :class="{
                                                    'opacity-40': !date.isCurrentMonth || date.isDisabled
                                                }"
                                                :data-selected="date.isSelected ? '' : null"
                                                :data-today="date.isToday ? '' : null"
                                                :data-disabled="date.isDisabled ? '' : null" role="gridcell"
                                                :aria-selected="date.isSelected" :aria-disabled="date.isDisabled">
                                                <button @click="selectDate(date)" type="button"
                                                    :disabled="!isDateSelectable(date)"
                                                    class="relative flex flex-col items-center justify-center text-sm font-medium transition-colors rounded-lg size-11 sm:size-11 text-zinc-800 dark:text-white disabled:text-zinc-400 disabled:pointer-events-none disabled:cursor-default"
                                                    :class="{
                                                        'bg-primary-500 text-white hover:bg-primary-600': date
                                                            .isSelected && !date.isDisabled,
                                                        'hover:bg-zinc-800/5 dark:hover:bg-white/5': !date.isSelected &&
                                                            !date.isDisabled && !isDisabled
                                                    }"
                                                    :aria-label="formatDateLabel(date.date)"
                                                    :tabindex="isDateSelectable(date) ? 0 : -1">
                                                    <div class="relative">
                                                        <!-- Today indicator dot -->
                                                        <div x-show="date.isToday && !date.isDisabled"
                                                            class="absolute inset-x-0 bottom-[-3px] flex justify-center items-end">
                                                            <div class="rounded-full size-1 bg-zinc-800 dark:bg-white"
                                                                :class="{ 'bg-white dark:bg-zinc-800': date.isSelected }">
                                                            </div>
                                                        </div>

                                                        <div x-text="date.day"></div>
                                                    </div>
                                                </button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
