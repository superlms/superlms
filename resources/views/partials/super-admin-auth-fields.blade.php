{{-- Fields of the Super Admin login and forgot-password pages: pill shaped
     (radius 100px), the field's name sitting on the top border, soft grey
     while empty and blue once something is typed — no extra ring while
     typing; red with its own message when that field is wrong, until it is
     typed in again. The OTP boxes carry the same thin border and colours.
     Every button on these pages is a pill too (rounded-full).

     .sa-field   wrapper; .is-filled (Alpine) once it has a value,
                 [data-invalid] (server) while its error stands
     .sa-otp     the row of code boxes; [data-invalid] while the code's error
                 stands. Each box: .sa-otp-box, .is-filled once it has a digit
     .sa-title   a screen's heading (and the picture's caption): Plus Jakarta
                 Sans, semibold — softer than a heavy bold
     .sa-sub     the line under a heading: at most two lines, both about as
                 long, with room at the sides --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&display=swap">
<style>
    /* Hidden until Alpine starts: on a reload the eye's "hide" icon must not
       show beside the "show" one while the page's scripts are still loading. */
    [x-cloak] { display: none !important; }

    .sa-title {
        font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        font-weight: 600; letter-spacing: -0.025em; color: #1f2937;
    }
    .sa-sub {
        max-width: 19rem; margin-left: auto; margin-right: auto;
        line-height: 1.5; text-wrap: balance;
        max-height: 3em; overflow: hidden; /* never a third line */
    }

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

    /* A password's rules under its field: grey dot while unmet, green tick
       (with a little pop) once met, red cross for the ones a try left unmet. */
    .sa-field.is-bad input { border-color: #dc2626; }
    .sa-field.is-bad label { color: #dc2626; }
    .sa-rules { margin: .5rem 0 0 1.375rem; display: grid; gap: .3rem; }
    .sa-rule { display: flex; align-items: center; gap: .5rem; font-size: .75rem; line-height: 1.25; color: #6b7280; transition: color .2s; }
    .sa-rule-ico {
        width: 1rem; height: 1rem; border-radius: 9999px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #f3f4f6; color: #9ca3af; transition: background-color .2s, color .2s;
    }
    .sa-rule.is-ok { color: #16a34a; }
    .sa-rule.is-ok .sa-rule-ico { background: #dcfce7; color: #16a34a; animation: sa-rule-pop .3s ease-out; }
    .sa-rule.is-bad { color: #dc2626; }
    .sa-rule.is-bad .sa-rule-ico { background: #fee2e2; color: #dc2626; }
    @keyframes sa-rule-pop { 0% { transform: scale(.5); } 60% { transform: scale(1.2); } 100% { transform: scale(1); } }
    @media (prefers-reduced-motion: reduce) { .sa-rule.is-ok .sa-rule-ico { animation: none; } }

    /* OTP boxes: the fields' 1.5px border instead of a heavy 2px one. */
    .sa-otp-box {
        border: 1.5px solid #9ca3af; background: #fff; color: #111827;
        outline: none; box-shadow: none; transition: border-color .15s;
    }
    .sa-otp-box:focus { border-color: #2563eb; outline: none; box-shadow: none; }
    .sa-otp-box.is-filled { border-color: #2563eb; }
    .sa-otp[data-invalid] .sa-otp-box { border-color: #dc2626; }
</style>
