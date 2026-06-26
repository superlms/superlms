@props(['schoolId', 'viewEvent', 'editEvent', 'deleteEvent', 'showView' => true, 'showEdit' => true, 'showDelete' => true])

<div class="flex items-center space-x-2">
    @if($showView)
        <x-button 
            wire:click.stop="$dispatch('{{ $viewEvent }}', {id: {{ $schoolId }}})" 
            outline 
            primary 
            label="View"
            xs
        />
    @endif
    
    @if($showEdit)
        <x-button 
            wire:click.stop="$dispatch('{{ $editEvent }}', {id: {{ $schoolId }}})" 
            outline 
            positive 
            label="Edit"
            xs
        />
    @endif
    
    @if($showDelete)
        <x-button 
            wire:click.stop="$dispatch('{{ $deleteEvent }}', {id: {{ $schoolId }}})" 
            outline 
            negative 
            label="Delete"
            xs
        />
    @endif
</div>