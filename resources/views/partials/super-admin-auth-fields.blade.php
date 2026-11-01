{{-- Fields of the Super Admin login and forgot-password pages: pill shaped
     (radius 100px), the field's name sitting on the top border, black while
     empty and blue once something is typed; red with its own message when
     that field is wrong, until it is typed in again.

     .sa-field          wrapper; .is-filled (Alpine) once it has a value,
                        [data-invalid] (server) while its error stands
     .sa-otp-row/.sa-otp  the six code boxes, round, same colours --}}
<style>
    .sa-field { position: relative; margin-top: .5rem; }
    .sa-field input {
        width: 100%; height: 3.25rem; padding: 0 1.375rem;
        border: 1.5px solid #111827; border-radius: 100px;
        background: #fff; color: #111827; font-size: .9375rem;
        outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .sa-field input::placeholder { color: #9ca3af; }
    .sa-field.has-eye input { padding-right: 3.25rem; }
    .sa-field label {
        position: absolute; top: 0; left: 1.125rem; transform: translateY(-50%);
        padding: 0 .375rem; background: #fff; line-height: 1;
        font-size: .75rem; font-weight: 600; color: #111827;
        pointer-events: none; transition: color .15s;
    }
    .sa-field input:focus { box-shadow: 0 0 0 3px rgba(17, 24, 39, .12); }

    .sa-field.is-filled input { border-color: #2563eb; }
    .sa-field.is-filled label { color: #2563eb; }
    .sa-field.is-filled input:focus { box-shadow: 0 0 0 3px rgba(37, 99, 235, .18); }
    /* Filled in by the browser before Alpine hears of it. */
    .sa-field input:-webkit-autofill { border-color: #2563eb; -webkit-box-shadow: 0 0 0 1000px #fff inset; }

    .sa-field[data-invalid] input { border-color: #dc2626; }
    .sa-field[data-invalid] label { color: #dc2626; }
    .sa-field[data-invalid] input:focus { box-shadow: 0 0 0 3px rgba(220, 38, 38, .15); }

    .sa-eye {
        position: absolute; right: 1.125rem; top: 50%; transform: translateY(-50%);
        color: #6b7280; line-height: 0;
    }
    .sa-eye:hover { color: #111827; }

    .sa-field-error { margin: .375rem 0 0 1.375rem; font-size: .75rem; line-height: 1.35; color: #dc2626; }

    .sa-otp {
        width: 2.75rem; height: 2.75rem; border: 1.5px solid #111827; border-radius: 100px;
        background: #fff; color: #111827; text-align: center; font-size: 1.125rem; font-weight: 700;
        outline: none; transition: border-color .15s, box-shadow .15s, color .15s;
    }
    @media (min-width: 640px) { .sa-otp { width: 3rem; height: 3rem; font-size: 1.25rem; } }
    .sa-otp:focus { box-shadow: 0 0 0 3px rgba(17, 24, 39, .12); }
    .sa-otp.is-filled { border-color: #2563eb; color: #2563eb; }
    .sa-otp.is-filled:focus { box-shadow: 0 0 0 3px rgba(37, 99, 235, .18); }
    .sa-otp-row[data-invalid] .sa-otp:not(.is-filled) { border-color: #dc2626; }
</style>
