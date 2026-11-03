{{-- Fields of the Super Admin login and forgot-password pages: pill shaped
     (radius 100px), the field's name sitting on the top border, soft grey
     while empty and blue once something is typed — no extra ring while
     typing; red with its own message when that field is wrong, until it is
     typed in again. The OTP boxes carry the same thin border and colours.
     Every button on these pages is a pill too (rounded-full).

     .sa-field   wrapper; .is-filled (Alpine) once it has a value,
                 [data-invalid] (server) while its error stands
     .sa-otp     the row of code boxes; [data-invalid] while the code's error
                 stands. Each box: .sa-otp-box, .is-filled once it has a digit --}}
<style>
    .sa-field { position: relative; margin-top: .5rem; }
    .sa-field input {
        width: 100%; height: 3.25rem; padding: 0 1.375rem;
        border: 1.5px solid #9ca3af; border-radius: 100px;
        background: #fff; color: #111827; font-size: .9375rem;
        outline: none; box-shadow: none; transition: border-color .15s;
    }
    .sa-field input:focus { outline: none; box-shadow: none; }
    .sa-field input::placeholder { color: #9ca3af; }
    .sa-field.has-eye input { padding-right: 3.25rem; }
    .sa-field label {
        position: absolute; top: 0; left: 1.125rem; transform: translateY(-50%);
        padding: 0 .375rem; background: #fff; line-height: 1;
        font-size: .75rem; font-weight: 600; color: #6b7280;
        pointer-events: none; transition: color .15s;
    }

    .sa-field.is-filled input { border-color: #2563eb; }
    .sa-field.is-filled label { color: #2563eb; }
    /* Filled in by the browser before Alpine hears of it. */
    .sa-field input:-webkit-autofill { border-color: #2563eb; -webkit-box-shadow: 0 0 0 1000px #fff inset; }

    .sa-field[data-invalid] input { border-color: #dc2626; }
    .sa-field[data-invalid] label { color: #dc2626; }

    .sa-eye {
        position: absolute; right: 1.125rem; top: 50%; transform: translateY(-50%);
        color: #9ca3af; line-height: 0;
    }
    .sa-eye:hover { color: #4b5563; }

    .sa-field-error { margin: .375rem 0 0 1.375rem; font-size: .75rem; line-height: 1.35; color: #dc2626; }

    /* OTP boxes: the fields' 1.5px border instead of a heavy 2px one. */
    .sa-otp-box {
        border: 1.5px solid #9ca3af; background: #fff; color: #111827;
        outline: none; box-shadow: none; transition: border-color .15s;
    }
    .sa-otp-box:focus { border-color: #2563eb; outline: none; box-shadow: none; }
    .sa-otp-box.is-filled { border-color: #2563eb; }
    .sa-otp[data-invalid] .sa-otp-box { border-color: #dc2626; }
</style>
