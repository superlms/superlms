<div class="container mx-auto">
    {{-- read-only: accounts sees the school calendar but does not run it, so
         there is no adding, editing or deleting an event from this panel. --}}
    <livewire:admin.time-table-calendar :initial-year="2025" :initial-month="6" :read-only="true"
        :key="'timetable-' . now()->format('Y-m')" />
</div>
