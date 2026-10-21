{{--
    An enquiry-style form (Admission, Query, Career, Contact, Appointment). The
    layout's script posts it to the school's enquiry inbox — see there.
    Params: $kind (the subject it arrives under), $type (the enquiry's type
    in the admin's Website Enquiries — SchoolWebsiteEnquiry::TYPES), $fields —
    each [name, label, type: text|email|tel|date|select|textarea, required,
    options, wide, subject (its answer is added to the subject), placeholder] —
    and optional $button.

    The trap for bots is a field no one sees (display: none) and with a name
    autofill does not know: one named "company" was filled by the browser's
    autofill along with the visitor's name and phone, and the enquiry was
    then dropped as spam while the visitor was thanked.
--}}
<form class="sform" data-org="{{ (int) $site->organization_id }}" data-kind="{{ $kind }}" data-type="{{ $type ?? '' }}" novalidate>
    <div class="hp" aria-hidden="true"><input type="text" name="sf_check" tabindex="-1" autocomplete="off"></div>
    <div class="sform-grid">
        @foreach ($fields as $f)
            @php $f += ['type' => 'text', 'required' => false, 'options' => [], 'wide' => false, 'subject' => false, 'placeholder' => '']; @endphp
            <label class="{{ $f['wide'] || $f['type'] === 'textarea' ? 'wide' : '' }}">
                <span>{{ $f['label'] }}@if ($f['required']) <b>*</b>@endif</span>
                @if ($f['type'] === 'select')
                    <select name="{{ $f['name'] }}" data-label="{{ $f['label'] }}" @if ($f['subject']) data-subject="1" @endif @required($f['required'])>
                        <option value="">Select</option>
                        @foreach ($f['options'] as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif ($f['type'] === 'textarea')
                    <textarea name="{{ $f['name'] }}" rows="4" data-label="{{ $f['label'] }}" placeholder="{{ $f['placeholder'] }}" maxlength="3000" @required($f['required'])></textarea>
                @else
                    <input type="{{ $f['type'] }}" name="{{ $f['name'] }}" data-label="{{ $f['label'] }}" placeholder="{{ $f['placeholder'] }}"
                           @if ($f['subject']) data-subject="1" @endif
                           maxlength="{{ $f['type'] === 'tel' ? 15 : 200 }}" @required($f['required'])>
                @endif
            </label>
        @endforeach
    </div>
    <div class="sform-foot">
        <button class="btn" type="submit">{{ $button ?? 'Send' }}</button>
        <p class="sform-msg" role="status"></p>
    </div>
</form>
