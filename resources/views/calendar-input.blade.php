<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:ignore class="w-full">
        <div x-data="() => ({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            internalState: null,
            currentDate: new Date(),
            selectedDate: null,
            selectedStartDate: null,
            selectedEndDate: null,
            isSelectingRange: false,
            daysOfWeek: @js($getDaysOfWeek()),
            maxDate: @js($getMaxDate()),
            minDate: @js($getMinDate()),
            disabledDates: @js($getDisabledDates()),
            enabledDates: @js($getEnabledDates()),
            rangeSelection: @js($isRangeSelection()),
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
                    if (this.rangeSelection && Array.isArray(this.state) && this.state.length === 2) {
                        // Handle range selection
                        const startDate = new Date(this.state[0]);
                        const endDate = new Date(this.state[1]);
                        if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                            this.selectedStartDate = startDate;
                            this.selectedEndDate = endDate;
                            this.selectedDate = null;
                            this.currentDate = new Date(startDate.getFullYear(), startDate.getMonth(), 1);
                            this.internalState = this.state;
                            this.isSelectingRange = false;
                        }
                    } else if (!this.rangeSelection) {
                        // Handle single date selection
                        const date = new Date(this.state);
                        if (!isNaN(date.getTime())) {
                            this.selectedDate = date;
                            this.selectedStartDate = null;
                            this.selectedEndDate = null;
                            this.currentDate = new Date(date.getFullYear(), date.getMonth(), 1);
                            this.internalState = this.state;
                        }
                    }
                } else {
                    this.selectedDate = null;
                    this.selectedStartDate = null;
                    this.selectedEndDate = null;
                    this.currentDate = new Date();
                    this.internalState = null;
                    this.state = null;
                    this.isSelectingRange = false;
                }
            },

            syncFromState() {
                if (this.state !== this.internalState) {
                    this.internalState = this.state;
                    if (this.state) {
                        if (this.rangeSelection && Array.isArray(this.state) && this.state.length === 2) {
                            // Handle range selection
                            const startDate = new Date(this.state[0]);
                            const endDate = new Date(this.state[1]);
                            if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                                this.selectedStartDate = startDate;
                                this.selectedEndDate = endDate;
                                this.selectedDate = null;
                                this.currentDate = new Date(startDate.getFullYear(), startDate.getMonth(), 1);
                                this.isSelectingRange = false;
                            }
                        } else if (!this.rangeSelection) {
                            // Handle single date selection
                            const date = new Date(this.state);
                            if (!isNaN(date.getTime())) {
                                this.selectedDate = date;
                                this.selectedStartDate = null;
                                this.selectedEndDate = null;
                                this.currentDate = new Date(date.getFullYear(), date.getMonth(), 1);
                            }
                        }
                    } else {
                        this.selectedDate = null;
                        this.selectedStartDate = null;
                        this.selectedEndDate = null;
                        this.isSelectingRange = false;
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

            isValidDateRange(startDate, endDate) {
                if (!startDate || !endDate) return false;
                
                // Ensure start is before or equal to end
                if (startDate > endDate) return false;
                
                // Check each date in the range
                const current = new Date(startDate);
                const end = new Date(endDate);
                
                while (current <= end) {
                    if (this.isDateDisabled(current)) {
                        return false;
                    }
                    current.setDate(current.getDate() + 1);
                }
                
                return true;
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

            isDateInRange(date) {
                if (!this.selectedStartDate || !this.selectedEndDate) return false;
                return date >= this.selectedStartDate && date <= this.selectedEndDate;
            },

            isRangeStart(date) {
                return this.selectedStartDate && this.isSameDay(date, this.selectedStartDate);
            },

            isRangeEnd(date) {
                return this.selectedEndDate && this.isSameDay(date, this.selectedEndDate);
            },

            isRangeSelecting(date) {
                return this.isSelectingRange && this.selectedStartDate && this.isSameDay(date, this.selectedStartDate);
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
                        isSelected: this.rangeSelection ? false : this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: this.isDateDisabled(date),
                        isInRange: this.rangeSelection ? this.isDateInRange(date) : false,
                        isRangeStart: this.rangeSelection ? this.isRangeStart(date) : false,
                        isRangeEnd: this.rangeSelection ? this.isRangeEnd(date) : false,
                        isRangeSelecting: this.rangeSelection ? this.isRangeSelecting(date) : false,
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
                        isSelected: this.rangeSelection ? false : this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: isDisabled,
                        isInRange: this.rangeSelection ? this.isDateInRange(date) : false,
                        isRangeStart: this.rangeSelection ? this.isRangeStart(date) : false,
                        isRangeEnd: this.rangeSelection ? this.isRangeEnd(date) : false,
                        isRangeSelecting: this.rangeSelection ? this.isRangeSelecting(date) : false,
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
                        isSelected: this.rangeSelection ? false : this.isSameDay(date, this.selectedDate),
                        isToday: this.isSameDay(date, new Date()),
                        isDisabled: this.isDateDisabled(date),
                        isInRange: this.rangeSelection ? this.isDateInRange(date) : false,
                        isRangeStart: this.rangeSelection ? this.isRangeStart(date) : false,
                        isRangeEnd: this.rangeSelection ? this.isRangeEnd(date) : false,
                        isRangeSelecting: this.rangeSelection ? this.isRangeSelecting(date) : false,
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
                    if (this.rangeSelection) {
                        // Handle range selection
                        if (!this.isSelectingRange && !this.selectedStartDate) {
                            // Start new range selection
                            this.selectedStartDate = new Date(dateObj.date);
                            this.selectedEndDate = null;
                            this.isSelectingRange = true;
                            this.selectedDate = null;
                            this.internalState = null;
                            this.state = null;
                        } else if (this.isSelectingRange) {
                            // Complete range selection
                            const startDate = this.selectedStartDate;
                            const endDate = new Date(dateObj.date);
                            
                            // Ensure proper order
                            const actualStart = startDate <= endDate ? startDate : endDate;
                            const actualEnd = startDate <= endDate ? endDate : startDate;
                            
                            // Validate the range
                            if (this.isValidDateRange(actualStart, actualEnd)) {
                                this.selectedStartDate = actualStart;
                                this.selectedEndDate = actualEnd;
                                this.isSelectingRange = false;
                                const startStr = actualStart.toISOString().split('T')[0];
                                const endStr = actualEnd.toISOString().split('T')[0];
                                this.internalState = [startStr, endStr];
                                this.state = [startStr, endStr];
                            } else {
                                // Invalid range, restart selection
                                this.selectedStartDate = new Date(dateObj.date);
                                this.selectedEndDate = null;
                                this.isSelectingRange = true;
                                this.internalState = null;
                                this.state = null;
                            }
                        } else {
                            // Reset and start new range
                            this.selectedStartDate = new Date(dateObj.date);
                            this.selectedEndDate = null;
                            this.isSelectingRange = true;
                            this.selectedDate = null;
                            this.internalState = null;
                            this.state = null;
                        }
                    } else {
                        // Handle single date selection
                        if (this.isSameDay(dateObj.date, this.selectedDate)) {
                            this.selectedDate = null;
                            this.internalState = null;
                            this.state = null;
                        } else {
                            this.selectedDate = new Date(dateObj.date);
                            this.selectedStartDate = null;
                            this.selectedEndDate = null;
                            this.internalState = this.selectedDate.toISOString().split('T')[0];
                            this.state = this.internalState;
                        }
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
                                            <td class="p-0 first-of-type:rounded-s-lg last-of-type:rounded-e-lg"
                                                :class="{
                                                    'opacity-40': !date.isCurrentMonth || date.isDisabled,
                                                    'bg-blue-50 dark:bg-blue-900/20': date.isInRange && !date.isRangeStart && !date.isRangeEnd,
                                                    'bg-blue-100 dark:bg-blue-900/30': date.isRangeSelecting
                                                }"
                                                :data-selected="date.isSelected ? '' : null"
                                                :data-range-start="date.isRangeStart ? '' : null"
                                                :data-range-end="date.isRangeEnd ? '' : null"
                                                :data-in-range="date.isInRange ? '' : null"
                                                :data-today="date.isToday ? '' : null"
                                                :data-disabled="date.isDisabled ? '' : null" role="gridcell"
                                                :aria-selected="date.isSelected || date.isRangeStart || date.isRangeEnd" :aria-disabled="date.isDisabled">
                                                <button @click="selectDate(date)" type="button"
                                                    :disabled="!isDateSelectable(date)"
                                                    class="relative flex flex-col items-center justify-center text-sm font-medium transition-colors rounded-lg size-11 sm:size-11 text-zinc-800 dark:text-white disabled:text-zinc-400 disabled:pointer-events-none disabled:cursor-default"
                                                    :class="{
                                                        'bg-primary-500 text-white hover:bg-primary-600': (date.isSelected || date.isRangeStart || date.isRangeEnd) && !date.isDisabled,
                                                        'bg-primary-300 text-white': date.isRangeSelecting && !date.isDisabled,
                                                        'hover:bg-zinc-800/5 dark:hover:bg-white/5': !date.isSelected && !date.isRangeStart && !date.isRangeEnd && !date.isRangeSelecting && !date.isDisabled && !isDisabled
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
