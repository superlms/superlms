# EdyoneLMS — API Reference

All endpoints in this app are prefixed with `/api`. Production base URL:

```
https://edyonelms.in/api
```

For local Docker development:

```
http://localhost:8080/api
```

This document contains a `curl` example for every endpoint, grouped by feature.

---

## Table of Contents

- [Setup](#setup)
- [1. Public Endpoints](#1-public-endpoints-no-auth)
- [2. Authentication](#2-authentication--get-a-token)
- [3. Authenticated Endpoints](#3-authenticated-endpoints)
  - [Dashboard & Analytics](#dashboard--analytics)
  - [User / Profile](#user--profile)
  - [Contact (Student/Teacher ↔ Admin)](#contact-studentteacher--admin)
  - [Announcement / Library / Subject](#announcement--library--subject)
  - [Content (Chapters / Topics)](#content-chapters--topics)
  - [Homework](#homework)
  - [Quiz](#quiz)
  - [Attendance](#attendance)
  - [Syllabus](#syllabus)
  - [Filter](#filter)
  - [Performance / Exam Copies](#performance--exam-copies)
  - [Exams](#exams)
  - [TimeTable / Calendar](#timetable--calendar)
  - [ID Card / Admit Card](#id-card--admit-card)
  - [Books / Instructors](#books--instructors)
  - [Fees (student)](#fees-student)
  - [Transport](#transport)
  - [Seating Plan / Report Card (student)](#seating-plan--report-card-student)
  - [Switch Account / Notifications](#switch-account--notifications)
- [Notes & Caveats](#notes--caveats)

---

## Setup

Paste these once per terminal session — every curl below uses `$BASE` and (for authenticated calls) `$TOKEN`:

```bash
export BASE="https://edyonelms.in/api"
export TOKEN=""           # filled in after a successful login (see section 2)
```

All requests must include:

- `Accept: application/json` — otherwise Laravel returns HTML error pages instead of JSON.
- For POST/PUT/PATCH with a JSON body: `Content-Type: application/json`.
- For authenticated endpoints: `Authorization: Bearer $TOKEN`.

---

## 1. Public Endpoints (no auth)

```bash
# Website stats / lists
curl -s "$BASE/website/stats"            -H "Accept: application/json"
curl -s "$BASE/website/schools"          -H "Accept: application/json"
curl -s "$BASE/website/testimonials"     -H "Accept: application/json"
curl -s "$BASE/website/privacy-policy"   -H "Accept: application/json"
curl -s "$BASE/website/terms-conditions" -H "Accept: application/json"
curl -s "$BASE/website/terms-of-use"     -H "Accept: application/json"

# Contact form
curl -s -X POST "$BASE/website/contact" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"full_name":"John Doe","email":"john@example.com","phone_number":"9876543210","school_name":"ABC School","subject":"Hello","description":"Interested in your platform"}'

# Demo request
curl -s -X POST "$BASE/website/demo" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"full_name":"John Doe","school_name":"ABC School","phone":"9876543210","email":"john@example.com","city":"Mumbai","no_of_students":"500","role":"Principal"}'

# About app
curl -s "$BASE/v1/about-app" -H "Accept: application/json"

# Test endpoints (debug)
curl -s -X POST "$BASE/test"      -H "Accept: application/json" -H "Content-Type: application/json" -d '{}'
curl -s -X POST "$BASE/save-data" -H "Accept: application/json" -H "Content-Type: application/json" -d '{}'

# Admit card verify (public lookup)
curl -s "$BASE/admit-card/verify/ADMIT123" -H "Accept: application/json"
```

---

## 2. Authentication — get a token

### Unified login (recommended)

One endpoint for **every** user type. Send an `identifier` (student admission
number **or** an email for teacher / admin / sub-admin / accounts) plus a
`password`. The role is auto-detected from the identifier — there is no
"select user type" step.

```bash
# Student (admission number)
curl -s -X POST "$BASE/v1/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"identifier":"STU001","password":"yourpassword"}'

# Teacher / Admin / Sub-admin / Accounts (email)
curl -s -X POST "$BASE/v1/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"identifier":"admin@school.com","password":"yourpassword"}'
```

Response `data` includes `user`, `token`, `token_type`, plus `role`,
`user_type` and `dashboard` (∈ `student | teacher | admin | accounts`) so the
client can route straight to the right dashboard. Back-compat: `admission_number`
or `email` are also accepted in place of `identifier`.

### Legacy per-role login (still supported)

```bash
# Student
curl -s -X POST "$BASE/v1/user/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"admission_number":"STU001","password":"yourpassword"}'

# Teacher
curl -s -X POST "$BASE/v1/teacher/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"teacher@school.com","password":"yourpassword"}'

# Admin / Accounts
curl -s -X POST "$BASE/v1/admin/login"    -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"admin@school.com","password":"yourpassword"}'
curl -s -X POST "$BASE/v1/accounts/login" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"accounts@school.com","password":"yourpassword"}'
```

### Capture the token automatically (requires `jq`)

```bash
TOKEN=$(curl -s -X POST "$BASE/v1/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"identifier":"STU001","password":"yourpassword"}' \
  | jq -r '.data.token // .token // empty')
echo "Token: $TOKEN"
```

Adjust the `jq` path (`.data.token`) to match your actual login response shape.

### OTP / Password recovery

```bash
curl -s -X POST "$BASE/v1/resend-otp"       -H "Accept: application/json" -H "Content-Type: application/json" -d '{"mobile_number":"9876543210"}'
curl -s -X POST "$BASE/v1/forgot-password"  -H "Accept: application/json" -H "Content-Type: application/json" -d '{"mobile_number":"9876543210"}'
curl -s -X POST "$BASE/v1/verify-otp"       -H "Accept: application/json" -H "Content-Type: application/json" -d '{"mobile_number":"9876543210","otp":"1234"}'
curl -s -X POST "$BASE/v1/change-password"  -H "Accept: application/json" -H "Content-Type: application/json" -d '{"mobile_number":"9876543210","password":"newpass","password_confirmation":"newpass"}'
```

---

## 3. Authenticated Endpoints

> Every call below requires `-H "Authorization: Bearer $TOKEN"`.

### Dashboard & Analytics

Aggregated home-screen + analytics data in a single call (attendance, marks,
exams, homework, announcements), scoped to the logged-in role.

```bash
curl -s "$BASE/v1/student/dashboard"       -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/teacher/dashboard"       -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### User / Profile

```bash
curl -s "$BASE/user"                       -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/user/profile"            -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/teacher/profile"         -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/teacher/subject"         -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/school-info"             -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/rules-and-regulation"    -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s -X POST "$BASE/v1/update-password" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"old_password":"old","password":"new","password_confirmation":"new"}'

curl -s -X POST "$BASE/v1/save-fcm-token"  \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"fcm_token":"abc123..."}'
```

### Contact (Student/Teacher ↔ Admin)

```bash
# Student → Admin
curl -s -X POST "$BASE/v1/user/admin/contact" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"subject":"Question","description":"Need help"}'

curl -s "$BASE/v1/user/admin/contact-list" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s -X POST "$BASE/v1/user/admin/contact-reply" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"contact_id":1,"reply":"thanks"}'

# Teacher → Admin
curl -s -X POST "$BASE/v1/teacher/admin/contact" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"subject":"Question","description":"Need help"}'

curl -s "$BASE/v1/teacher/admin/contact-list" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s -X POST "$BASE/v1/teacher/admin/contact-reply" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"contact_id":1,"reply":"thanks"}'
```

### Announcement / Library / Subject

```bash
curl -s -X POST "$BASE/v1/announcement"   -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{"page":1}'
curl -s "$BASE/v1/announcement/1"         -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s -X POST "$BASE/v1/library"        -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{"page":1}'
curl -s "$BASE/v1/library/1"              -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s "$BASE/v1/subject"                -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Content (Chapters / Topics)

```bash
curl -s -X POST "$BASE/v1/content/upload" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"subject_id":1,"chapter_name":"Algebra","topic_name":"Equations"}'

curl -s -X POST "$BASE/v1/content/get" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"subject_id":1}'

# Create a single chapter (no topics required)
curl -s -X POST "$BASE/v1/content/chapter" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"standard_id":1,"section_id":1,"subject_id":1,"name":"Thermodynamics","description":"Heat & energy"}'

# Create a topic — Syllabus (name + order only)
curl -s -X POST "$BASE/v1/content/topic" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"chapter_id":1,"topic_name":"Newton'\''s Laws","order":1}'

# Create a topic — Study Content (content text + image, multipart with -F)
curl -s -X POST "$BASE/v1/content/topic" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "chapter_id=1" -F "topic_name=Newton's Laws" -F "topic_content=Full study notes..." \
  -F "image=@/path/to/diagram.png"

# List returns each topic with topic_content, image_url and pdf_url (or null)

curl -s -X POST "$BASE/v1/content/chapter/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"chapter_name":"New Name"}'

curl -s -X DELETE "$BASE/v1/content/chapter-delete/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

# Add study material INTO an existing topic (text + link, JSON)
curl -s -X POST "$BASE/v1/content/topic/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"topic_content":"Study notes...","link":"https://drive.google.com/..."}'

# Add study material INTO an existing topic (with image, multipart with -F)
curl -s -X POST "$BASE/v1/content/topic/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "topic_content=Study notes..." -F "link=https://youtu.be/..." -F "image=@/path/to/diagram.png"

curl -s -X DELETE "$BASE/v1/content/topic-delete/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Homework

```bash
# Upload (multipart — note -F instead of -d)
curl -s -X POST "$BASE/v1/homework/upload" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "subject_id=1" -F "title=HW1" -F "description=Solve all" -F "due_date=2026-06-01" \
  -F "file=@/path/to/file.pdf"

curl -s -X POST "$BASE/v1/homework/get" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"subject_id":1}'

curl -s -X POST "$BASE/v1/homework/update/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"title":"Updated"}'

curl -s -X DELETE "$BASE/v1/homework/delete/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s "$BASE/v1/homework/get/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s -X POST "$BASE/v1/homework/student" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{}'
```

### Quiz (MCQ)

Each MCQ is one question with its options (one flagged `is_correct`) and a per-question
`time_limit` (seconds). Questions are scoped to a class (`standard_id`+`section_id`) and a
`chapter_id`/`topic_id`, so teacher edits, student attempts and the admin panel all stay in sync.

```bash
# Create a question (teacher) — options[].is_correct marks the right one
curl -s -X POST "$BASE/v1/quiz/upload" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"question_text":"Newtons first law is?","standard_id":1,"section_id":1,"chapter_id":1,"topic_id":1,"time_limit":60,"options":[{"option_text":"Inertia","is_correct":true},{"option_text":"Force","is_correct":false},{"option_text":"Energy","is_correct":false},{"option_text":"Mass","is_correct":false}]}'

# List questions (with options) for a chapter/topic
curl -s -X POST "$BASE/v1/quiz/get" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"standard_id":1,"section_id":1,"chapter_id":1,"topic_id":1,"per_page":200}'

# Update / delete a question (teacher)
curl -s -X POST "$BASE/v1/quiz/update/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"question_text":"Updated?","time_limit":45,"options":[{"option_text":"A","is_correct":true},{"option_text":"B","is_correct":false}]}'
curl -s -X DELETE "$BASE/v1/quiz/delete/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

# Submit an answer (student) — persisted for the report + admin sync
curl -s -X POST "$BASE/v1/quiz/submit-answer" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"mcq_question_id":1,"mcq_option_id":2,"time_taken":12}'

# Student's answers + correctness (report)
curl -s -X POST "$BASE/v1/quiz/get/user-answer" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"mcq_question_id":1}'
```

### Attendance

```bash
curl -s -X POST "$BASE/v1/attendance" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"date":"2026-05-13","records":[]}'

curl -s -X POST "$BASE/v1/attendance/get-student-for-attendance" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"standard_id":1,"section_id":1}'

curl -s -X POST "$BASE/v1/attendance/summary" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"month":5,"year":2026}'

curl -s -X POST "$BASE/v1/attendance/teacher" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"month":5,"year":2026}'

curl -s "$BASE/v1/attendance/today-teacher" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Syllabus

```bash
curl -s -X POST "$BASE/v1/syllabus" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"subject_id":1}'

# Upload (multipart)
curl -s -X POST "$BASE/v1/syllabus/upload" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "subject_id=1" -F "file=@/path/to/syllabus.pdf"

curl -s -X POST "$BASE/v1/syllabus/update/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"title":"Updated"}'

curl -s -X DELETE "$BASE/v1/syllabus/delete/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

# Downloads a binary PDF
curl -s -X POST "$BASE/v1/syllabus/download/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -o syllabus.pdf
```

### Filter

```bash
curl -s "$BASE/v1/filter/all" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Performance / Exam Copies

```bash
# Read
curl -s "$BASE/v1/performance/exam-copies"                         -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/performance/exam-copies/1"                       -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/performance/filters"                             -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/performance/sections/1"                          -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/performance/teacher-subjects"                    -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/performance/teacher-classes"                     -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

# Delete
curl -s -X DELETE "$BASE/v1/performance/exam-copies/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

# POST queries
curl -s -X POST "$BASE/v1/performance/student-performance-by-teacher" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"student_id":1}'

curl -s -X POST "$BASE/v1/performance/students" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"standard_id":1,"section_id":1}'

curl -s -X POST "$BASE/v1/performance/student-performance" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{}'

curl -s -X POST "$BASE/v1/performance/check-exists" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"student_id":1,"exam_id":1}'

# Uploads (multipart)
curl -s -X POST "$BASE/v1/performance/upload-exam-copies" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "student_id=1" -F "exam_id=1" -F "file=@/path/to/exam.pdf"

curl -s -X POST "$BASE/v1/performance/update-exam-copies/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "file=@/path/to/exam.pdf"

curl -s -X POST "$BASE/v1/performance/bulk-upload" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "files[]=@/path/to/exam1.pdf" -F "files[]=@/path/to/exam2.pdf"

# Downloads
curl -s "$BASE/v1/performance/download/exam-copy/1" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -o exam-copy-1.pdf

curl -s -X POST "$BASE/v1/performance/download/multiple-exam-copies" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"ids":[1,2,3]}' \
  -o exam-copies.pdf
```

### Exams

```bash
curl -s "$BASE/v1/exams"    -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/exams/1"  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### TimeTable / Calendar

```bash
curl -s -X POST "$BASE/v1/time-table" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{}'

curl -s -X POST "$BASE/v1/calendar/events" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"month":5,"year":2026}'

curl -s "$BASE/v1/calendar/events/today" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/calendar/events/1"     -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### ID Card / Admit Card

```bash
curl -s "$BASE/v1/id-card/student"     -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/id-card/teacher"     -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/id-card/admit-card"  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

`id-card/student` and `id-card/teacher` return the **same flat card structure** the admin
ID-card design renders (`IdCardService::cardViewData`), so the app shows a card identical to
the admin print/preview. `data` contains:

```jsonc
{
  "type": "student",                  // or "teacher"
  "name": "Aman Verma",
  "subtitle": "Class 10 - A",         // teacher → "Teacher"
  "photo": "https://.../photo.jpg",   // null if none
  "card_number": "IDSTU1261234567",
  "issue_date": "11 Jun 2026",        // d M Y
  "expiry_date": "11 Jun 2027",       // d M Y
  "status": "active",
  "qr_code": "<base64-png>",          // raw base64 (no data: prefix), null if none
  "school": { "name", "logo", "address", "website", "email", "phone" },
  "front_rows": {                     // ordered key → value rows on the card front
    "Reg No": "ADM-001", "Class": "10", "Section": "A",
    "Father Name": "...", "Mobile": "...", "Address": "..."
  },
  "back_mode": "transport",           // student → transport, teacher → terms
  "transport": null,                  // student: { Route, Pickup, Drop, ... } when assigned
  "days_remaining": 364,              // app convenience
  "is_expired": false                 // app convenience
}
```

Teacher `front_rows` are: Employee ID, Designation, Qualification, Mobile, Joining Date, Address.
403 if the caller's role doesn't match the endpoint; 404 if no active, non-expired card exists.

### Books / Instructors

```bash
curl -s "$BASE/v1/books"          -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/books/1"        -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s "$BASE/v1/instructors"    -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/instructors/1"  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Fees (student)

```bash
curl -s "$BASE/v1/fees/summary"   -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/fees/structure" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/fees/payments"  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Transport

```bash
curl -s "$BASE/v1/transport/my-route" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/transport/routes"   -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Seating Plan / Report Card (student)

```bash
curl -s "$BASE/v1/seating-plan"     -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/seating-plan/all" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s "$BASE/v1/report-card"   -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/v1/report-card/1" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Switch Account / Notifications

```bash
curl -s "$BASE/v1/switch-account/schools" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

curl -s -X POST "$BASE/v1/switch-account/switch" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"organization_id":1}'

curl -s -X POST "$BASE/v1/notifications/send-to-me" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"title":"Hi","body":"Test"}'
```

---

## Notes & Caveats

1. **Sample request bodies are educated guesses** based on endpoint names and Model `$fillable` arrays. The exact required fields live in each controller's `Validator::make(...)` rules. If a call returns `422`, the response body lists what's missing or invalid.
2. **File uploads use `-F` (multipart)**, not `-d` (JSON). That's why endpoints like homework/syllabus/exam-copy uploads look different.
3. **Add `-i` to any curl** to see response headers — useful when debugging `401` (auth) or `419` (CSRF / wrong route) issues.
4. **Pretty-print JSON output** by piping through `jq`:
   ```bash
   curl ... | jq
   ```
   Install with `brew install jq` on macOS.
5. **Using Postman instead?** Set environment variables `base_url` and `token`, then replace `$BASE` → `{{base_url}}` and `$TOKEN` → `{{token}}`. Login requests can auto-save the token in their "Tests" tab:
   ```javascript
   const res = pm.response.json();
   if (res.data && res.data.token) pm.environment.set("token", res.data.token);
   ```
6. **Get the authoritative live route list** straight from your running app:
   ```bash
   docker compose exec app php artisan route:list --path=api
   # JSON version:
   docker compose exec app php artisan route:list --path=api --json > api-routes.json
   ```
   This is the source of truth — if anything in this README drifts, that command wins.

---

## Common Error Codes

| Status | Likely Cause | Fix |
|---|---|---|
| HTML returned (not JSON) | Missing `Accept: application/json` header | Add the header |
| `401 Unauthorized` | Missing / invalid / expired Bearer token | Re-login, copy fresh token |
| `419 Page Expired` | Hitting a `web` route instead of `api`, or session/CSRF mismatch | Make sure URL starts with `/api/...` |
| `404 Not Found` | Wrong path or trailing slash | Drop trailing slash, double-check the route file |
| `422 Unprocessable Entity` | Request body failed validation | Response body lists the bad fields |
| `500 Server Error` | Backend exception | `docker compose logs app` on the server to see the stack trace |
