<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.entangle('{{ $getStatePath() }}'),
            selectedDates: [],
            adminDays: 0,

            init() {
                // Restore state jika ada (edit mode)
                if (this.state && Array.isArray(this.state)) {
                    this.selectedDates = [...this.state];
                    this.adminDays = this.selectedDates.length;
                }
            },

            toggleDate(dateStr) {
                const idx = this.selectedDates.indexOf(dateStr);
                if (idx === -1) {
                    this.selectedDates.push(dateStr);
                } else {
                    this.selectedDates.splice(idx, 1);
                }
                // Sort ascending
                this.selectedDates.sort();
                this.adminDays = this.selectedDates.length;

                // Sync ke Livewire state
                this.state = [...this.selectedDates];

                // Update admin_days field
                $wire.set('data.admin_days', this.adminDays);
            },

            isSelected(dateStr) {
                return this.selectedDates.includes(dateStr);
            },

            // Generate hari dalam sebulan untuk kalender
            getDaysInMonth(year, month) {
                return new Date(year, month + 1, 0).getDate();
            },

            getFirstDayOfMonth(year, month) {
                return new Date(year, month, 1).getDay();
            },

            formatDate(year, month, day) {
                return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            },

            // State kalender
            currentYear: new Date().getFullYear(),
            currentMonth: new Date().getMonth(),

            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
            },

            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
            },

            monthName() {
                return new Date(this.currentYear, this.currentMonth).toLocaleString('id-ID', { month: 'long', year: 'numeric' });
            }
        }"
        class="space-y-3"
    >
        <!-- Header Kalender -->
        <div class="flex items-center justify-between px-1">
            <button type="button"
                @click="prevMonth()"
                class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                ← Prev
            </button>
            <span class="font-semibold text-sm text-gray-700 dark:text-gray-200"
                x-text="monthName()">
            </span>
            <button type="button"
                @click="nextMonth()"
                class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                Next →
            </button>
        </div>

        <!-- Grid Hari -->
        <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-500 dark:text-gray-400 font-medium">
            <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div>
            <div>Kam</div><div>Jum</div><div>Sab</div>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <!-- Empty cells untuk offset hari pertama -->
            <template x-for="i in getFirstDayOfMonth(currentYear, currentMonth)" :key="'empty-' + i">
                <div></div>
            </template>

            <!-- Tanggal -->
            <template
                x-for="day in getDaysInMonth(currentYear, currentMonth)"
                :key="day"
            >
                <button
                    type="button"
                    @click="toggleDate(formatDate(currentYear, currentMonth, day))"
                    :class="{
                        'bg-primary-600 text-white font-bold ring-2 ring-primary-400': isSelected(formatDate(currentYear, currentMonth, day)),
                        'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200': !isSelected(formatDate(currentYear, currentMonth, day))
                    }"
                    class="rounded-md py-1.5 text-sm transition-all duration-100 w-full"
                    x-text="day"
                ></button>
            </template>
        </div>

        <!-- Summary tanggal terpilih -->
        <div class="mt-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400">Tanggal dipilih:</span>
                <span class="text-sm font-bold text-primary-600"
                    x-text="adminDays + ' hari'">
                </span>
            </div>

            <!-- Chips tanggal terpilih -->
            <div class="flex flex-wrap gap-1 mt-2" x-show="selectedDates.length > 0">
                <template x-for="d in selectedDates" :key="d">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                        <span x-text="d"></span>
                        <button type="button" @click="toggleDate(d)" class="hover:text-red-500">✕</button>
                    </span>
                </template>
            </div>

            <p class="text-xs text-gray-400 mt-1" x-show="selectedDates.length === 0">
                Belum ada tanggal dipilih. Klik tanggal di kalender di atas.
            </p>
        </div>
    </div>
</x-dynamic-component>