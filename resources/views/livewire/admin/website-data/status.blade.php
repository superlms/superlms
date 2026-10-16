{{-- A page's state in Website Data's list. Param: $status — filled | empty | form --}}
@if ($status === 'filled')
    <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 flex-shrink-0"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Filled</span>
@elseif ($status === 'empty')
    <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 flex-shrink-0"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>Empty</span>
@else
    <span class="text-xs text-gray-400 flex-shrink-0">Form</span>
@endif
