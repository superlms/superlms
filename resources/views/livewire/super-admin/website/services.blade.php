<div class="min-h-screen bg-gray-50">
    @include('livewire.super-admin.website.partials.topbar', [
        'heading'     => 'Services',
        'description' => 'Manage the services listed on the website.',
        'url'         => 'web/services',
    ])

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        @include('livewire.super-admin.website.partials.header-fields')

        @include('livewire.super-admin.website.partials.repeater', [
            'key'      => 'items',
            'label'    => 'Service Cards',
            'singular' => 'Service',
            'cols'     => 2,
            'fields'   => [
                ['name' => 'icon',  'label' => 'Icon (emoji)', 'placeholder' => '🎓'],
                ['name' => 'title', 'label' => 'Title',        'placeholder' => 'School Management System'],
                ['name' => 'desc',  'label' => 'Description',   'type' => 'textarea', 'full' => true, 'placeholder' => 'Short description for this service...'],
            ],
        ])
    </div>

    @include('livewire.super-admin.website.partials.delete-modal')
</div>
