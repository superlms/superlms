# Push Notifications (backend)

How the Laravel API delivers push notifications to the mobile app
(`edyonelmsapp`). The app handles **display** (Notifee) and keeps its own
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

1. Firebase Console → project **edyone-lms-57e8c** → ⚙ Project settings →
   **Service accounts** → **Generate new private key** (downloads a JSON).
2. Put it on the server at `storage/app/firebase/service-account.json`
   (this path is git-ignored — never commit the key).
3. In `.env`: `FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json`
4. `php artisan config:clear`

On the EC2 Docker box files are bind-mounted at `/var/www/html`, so the relative
path resolves to `/var/www/html/storage/app/firebase/service-account.json`.
