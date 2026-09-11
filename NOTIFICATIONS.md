# Push Notifications (backend)

How the Laravel API delivers push notifications to the mobile app
(`superlmsapp`). The app handles **display** (Notifee) and keeps its own
in-app inbox — the backend only **delivers data**.

## Pieces

| Piece | Location |
| --- | --- |
| Service account key | `storage/app/firebase/service-account.json` + `FIREBASE_CREDENTIALS` in `.env` (see setup below) |
| Token storage | `user_fcm_tokens` table (`user_id`, `token`, `platform`) |
| Token endpoints | `POST /api/v1/device-token`, `POST /api/v1/device-token/remove` |
| Sender | `App\Services\FirebaseNotificationService` |
| Controller | `App\Http\Controllers\SendNotificationController` |

## Data-only contract

We send **data-only** FCM messages (no `notification` block) so Android doesn't
double-post a tray banner. Every value is a string:

```
type    : a catalog key, e.g. "marks_uploaded"   (required)
title?  : string
body?   : string
screen? : route name to deep-link on tap
params? : JSON-encoded object
```

`type` values are defined in the app's `src/notifications/catalog.ts`
(e.g. `exam_scheduled`, `result_published`, `marks_uploaded`, `copy_uploaded`,
`attendance_marked`, `attendance_low`, `fee_due`, `fee_paid`,
`homework_assigned`, `homework_graded`, `announcement`, `leave_request`,
`leave_approved`, `general`).

## Sending — use this from event rules

Inject `FirebaseNotificationService` (or `app(FirebaseNotificationService::class)`)
and call:

```php
// One user, all their devices:
$firebase->notifyUser($student->user, 'marks_uploaded', [
    'title'  => 'Marks Uploaded',
    'body'   => "Your {$subject->name} marks have been uploaded.",
    'screen' => 'Marks',
    'params' => ['examId' => $exam->id, 'subjectId' => $subject->id],
]);

// Many users (e.g. a whole class):
$firebase->notifyUsers($classUsers, 'announcement', [
    'title' => 'New Announcement',
    'body'  => $announcement->title,
    'screen'=> 'ViewAnnouncement',
    'params'=> ['id' => $announcement->id],
]);
```

`title`/`body` are optional — if omitted, the app falls back to the catalog's
default template for that `type`. Invalid/expired tokens are auto-pruned.

## When each notification fires ("konsa notification kab, kisko")

Implemented rules (dispatched via `App\Services\AppPushNotifier`, wired in
`AppServiceProvider::bootAppPushNotifications()` + the attendance call sites):

| Event | type | Recipients | Screen / params |
| --- | --- | --- | --- |
| Admin posts an announcement (`Announcement` created) | `announcement` | org students + teachers, narrowed by the announcement's `type` (`all`/`user`/`teacher`) | `ViewAnnouncement` / `{item:{id}}` — body carries the content |
| About App changed (`AboutApp` saved) | `general` | all students + teachers (global) | `AboutAppMore` |
| Privacy Policy changed (`PrivacyPolicy` saved) | `general` | all students + teachers (global) | `PrivacyPolicyMore` |
| Terms of Use changed (`TermOfUse` saved) | `general` | all students + teachers (global) | `TermsOfUseMore` |
| Terms & Conditions changed (`TermAndCondition` saved) | `general` | all students + teachers (global) | `TermsConditionsMore` |
| Rules & Regulations changed (`RulesAndRegulation` saved) | `general` | the org's students + teachers | `RulesRegulationsMore` |
| School Info changed (`SchoolInfo` saved) | `general` | the org's students + teachers | `SchoolInfoMore` |
| Teacher/Admin marks attendance (API bulk submit + admin Livewire) | `attendance_marked` | each marked student | `Attendance` — body carries the status |
| Teacher/Admin adds homework (`HomeWork` created) | `homework_assigned` | students of that class + section | `Homework` / `{homeworkId}` |

> Attendance is wired at its call sites (`AttendanceController::bulkSubmitAttendance`
> + `Admin/Attendance::submitStudentAttendance`) because the bulk API path uses a
> raw `insert()` that doesn't fire model events. Everything else hangs off model
> `created`/`saved` events so it fires no matter who edits (API, admin, super-admin).

## One-time credentials setup

1. Firebase Console → project **superlms-lms-57e8c** → ⚙ Project settings →
   **Service accounts** → **Generate new private key** (downloads a JSON).
2. Put it on the server at `storage/app/firebase/service-account.json`
   (this path is git-ignored — never commit the key).
3. In `.env`: `FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json`
4. `php artisan config:clear`

On the EC2 Docker box files are bind-mounted at `/var/www/html`, so the relative
path resolves to `/var/www/html/storage/app/firebase/service-account.json`.

---

# In-app notifications (the navbar bell)

Separate from FCM push above: these are Laravel **database** notifications
(`App\Notifications\ActivityNotification` → the `notifications` table) read by
the navbar bell (`App\Livewire\Components\Notification`) on every panel.

| Who writes them | Class | Recipients |
| --- | --- | --- |
| Every meaningful model create/update/delete, app-wide | `App\Support\ActivityNotifier` | the org's `admin` + `sub-admin` |
| The money + messages an accountant has to know about | `App\Support\AccountsNotifier` | the org's `accounts` users |

## Accounts desk rules ("konsa notification kab")

The accounts panel carries the same fee screens the admin panel does, so these
fire **whoever** made the change — an accountant saving a fee cycle and an admin
saving the same fee cycle both reach the fee desk.

| Event | type | Fired from |
| --- | --- | --- |
| Fee structure added / updated / deleted | `fee_structure` | `HandlesFeeStructures::saveStructure()` + `doDeleteGroup()` |
| Fee cycle created / added / updated / re-balanced / installment removed / token fee updated | `fee_cycle` | every save path in `HandlesFeeCycles` |
| Fee collected — counter, admin panel, or a student paying online | `fee_payment` | `FeePayment::created` + `TransportFeePayment::created` (`AppServiceProvider::bootAccountsNotifications()`) |
| Concession granted / updated, and penalty waivers | `concession` | `HandlesFeeConcessions::saveConcession()`, `Admin\Fee::waivePenalty()` |
| A message arrives from an admin, teacher or student | `message` | `Chat\Message::created` — sent to the conversation's other participants, whatever their role |

Two deliberate choices:

- **One notification per action, not per row.** Saving a fee structure writes a
  row per fee head and generating a monthly cycle writes twelve. The bell gets
  one readable line ("Fee cycle re-balanced — Quarterly · academic fee ·
  2026-27 · 4 installments · 100%") instead of twelve.
- **Most of these writes never fire a model event.** Editing a cycle or a
  concession, or deleting a fee-structure group, all go through the query
  builder, which Eloquent's global `updated`/`deleted` listeners never see —
  that is why they announce themselves from their own save methods rather than
  hanging off model events like the payment rules do.

Chat is grouped like a phone: while a conversation's last bell entry is still
unread, further messages in it don't add new ones.

`AccountsNotifier` skips console runs with no logged-in user (seeders,
migrations, artisan) — an online payment settling from a gateway callback has no
actor either, but that runs over HTTP, so it still gets through.
