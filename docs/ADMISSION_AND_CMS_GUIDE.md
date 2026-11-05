# Admissions and Website CMS: Guide

This guide covers what works, the web addresses, how each part works, how to set it up, and how to test it.

Related: [ADMISSIONS_API.md](ADMISSIONS_API.md) (endpoint reference), [DECISIONS.md](DECISIONS.md) (why things are built this way), [postman_collection.json](postman_collection.json) (API tests).

---

## 1. What is built

| Area | Status |
| :--- | :--- |
| Applicant journey: verify mobile, apply, guardians, documents, submit, pay application fee | Built and tested |
| Document review, assessment scheduling and scoring | Built and tested |
| Council decision with capacity rule, waitlist, rejection reasons | Built and tested |
| Admission fee, admission pass (QR), enrolment, Student ID | Built and tested |
| Roles and permissions for every staff action | Built and tested |
| Payment callbacks: signed, idempotent, amount-checked, logged | Built and tested with the mock gateway |
| Withdraw, expiry (daily command), deadline and eligibility rules | Built and tested |
| Audit log for sensitive actions | Built and tested |
| Staff sign-in API with 12-hour tokens | Built and tested |
| Website CMS: notices, gallery, governing body, teachers, staff, editable pages | Built and tested |
| Admission content in the CMS: programme titles, descriptions, fees, seats, dates and open/closed; admission page text | Built and tested |
| Public read-only CMS API for the mobile app | Built and tested |

**Not finished. Read this before going live:**

| Gap | Effect |
| :--- | :--- |
| **OTP is not sent by SMS yet.** In local development the code is shown on screen. In production nobody receives it. | Applicants cannot verify their number, so they cannot apply in production |
| SSLCommerz is not connected | Real payments cannot be taken. The mock gateway is off on live servers by default, so no fee can be paid until SSLCommerz is set up |
| Set-password link and welcome email/SMS | New students and guardians get a random password nobody knows, and no link to set one |
| Enrolment letter PDF and onboarding checklist | Not generated |
| MinIO / S3 storage, Docker, Redis queues | Not set up. Documents are on the local private disk |
| Vite build for CSS and JavaScript | The admin and portal pages still load Tailwind and Alpine from a CDN |
| Browser (end-to-end) tests | Not written. Tests are HTTP and service level |
| Legacy `POST /api/admission/submit` | Still public and open. It creates accounts with a generated password and no OTP. Needs to be turned off once the app moves to v1 |
| New students in the old student screens | `/api/user_details` returns 500 for a new student, because they have no row in the old enrolment table |
| Teacher and guardian screens in the old mobile API | Return 500 for accounts with no enrolment (existing issue) |

---

## 2. Web addresses

Local server: `php artisan serve` gives `http://127.0.0.1:8000`. Replace the host for your server.

### Public website
| Page | Address |
| :--- | :--- |
| Home | `/` |
| About | `/about` (editable in CMS, page slug `about`) |
| Notice board | `/notice` |
| Gallery | `/gallery` |
| Governing body | `/governing-body` |
| Teachers | `/teacher-information` |
| Staff | `/staff-information` |
| Chairman's message | `/message-of-chairman` (CMS slug `message-chairman`) |
| Principal's message | `/message-of-principal` (CMS slug `message-principal`) |
| Academic calendar, programmes, contact, admission info | `/academic-calendar`, `/programs`, `/contact`, `/admission` |

### Admissions: applicant
| Page | Address |
| :--- | :--- |
| Programmes | `/admissions` |
| Apply for a programme | `/admissions/apply/{programme-id}`. Verify the mobile first; the form appears after that |
| Track an application | `/admissions/track`. Enter the application number and phone, then the code |
| Application page and pass | `/admissions/applications/{application-id}`. Opens only in the browser that verified the application |
| Pass check (public) | `/admissions/qr/{token}`. Shows valid or not valid, and nothing personal |
| Mock payment page | `/admissions/payments/mock/{payment-id}`. Only when the mock gateway is enabled (local and testing); otherwise 404 |

Get programme IDs from `GET /api/v1/admissions/programs`.

### Admissions: council and desk (log in first at `/login`)
| Page | Address | Who |
| :--- | :--- | :--- |
| Pipeline dashboard | `/admin/admissions` | Anyone with `admission.report.view` |
| Application evaluation | `/admin/admissions/applications/{id}` | `admission.application.view`. Each action needs its own permission |

### CMS admin (log in first)
| Page | Address |
| :--- | :--- |
| Overview | `/admin/cms` |
| Notices | `/admin/cms/notices` (new: `/admin/cms/notices/create`) |
| Gallery | `/admin/cms/gallery` |
| Governing body, teachers and staff | `/admin/cms/staff` |
| Editable pages | `/admin/cms/pages` |
| Admission programmes (content only) | `/admin/cms/admission-programs` (Super Admin only: needs `admission.program.manage`) |

Only users with `cms.manage` (Super Admin and Principal) can open these.

### API (mobile app and tools)
Base: `/api/v1/admissions` for admissions and `/api/v1/cms` for public content. Full list in [ADMISSIONS_API.md](ADMISSIONS_API.md).

---

## 3. How it works

### 3.1 The applicant's journey
1. **Verify the phone number.** The applicant enters an 11-digit number and gets a 6-digit code. Entering the code signs them in for the admissions part.
2. **Apply.** Personal details, current address, guardians, and documents (photo and birth certificate for school, photo and transcript for college).
3. **Submit.** The system checks: the programme is still open, eligibility (age, GPA, subjects), required documents, and a guardian. Then it asks for the application fee.
4. **Pay the application fee.** The amount is set by the programme. Only a signed, matching callback from the gateway marks it paid.
5. **Wait for review.** Staff check each document. When all are verified, the application is under review.
6. **Assessment (where the programme requires one).** Staff schedule a written test, viva or interview; the council records the score.
7. **Decision.** The council approves, waitlists or rejects. Approval issues the admission pass (QR). A full programme makes approval into a waitlist.
8. **Pay the admission fee.** Only after approval, while the pass is valid.
9. **Enrolment.** The desk officer picks a section with a free seat. The system creates the Student ID (for example `MCC26S000001`), the student account, linked guardian accounts, and the enrolment record.

Application states: `DRAFT`, `PENDING_APP_FEE`, `SUBMITTED`, `UNDER_REVIEW`, `EXAM_SCHEDULED`, `ASSESSED`, `APPROVED`, `WAITLISTED`, `REJECTED`, `ADMISSION_FEE_PAID`, `ENROLLED`, `WITHDRAWN`, `EXPIRED`. The server only allows the moves in the brief's table, and each move is recorded with who did it and when.

### 3.2 Who can do what

| Role | Can do | Cannot do |
| :--- | :--- | :--- |
| Applicant (anyone with a verified number) | Apply, upload, pay, track own applications | See anyone else's application |
| Admissions Officer | See applications, verify and reject documents, schedule assessments, enrol | Make the council decision |
| Admission Council Member | See applications, record scores, decide | Enrol (no permission). The person who decided can never enrol the same candidate, even with the right permission |
| Principal | See everything, see reports, manage CMS, override capacity with a written reason | Enrol (no permission) |
| Super Admin | Everything above, including enrolment | Nothing is excluded |
| Student or guardian (existing accounts) | Their own mobile app features | Admissions staff features |

Permissions: `admission.application.view`, `admission.document.verify`, `admission.assessment.record`, `admission.decide`, `admission.payment.view`, `admission.enroll`, `admission.report.view`, `admission.override_capacity`, `cms.manage`, `admission.program.manage`.

### 3.3 Security behaviour
- Applicants only see their own applications. Another person's application returns "not found", so numbers cannot be guessed.
- Tracking needs a code sent to the phone number, and the code is locked after 5 wrong tries.
- Payments: the amount comes from the programme. A callback needs a valid signature (mock) or confirmation from the gateway (SSLCommerz). Rejected callbacks are logged with the reason.
- Document type is checked by content. Files are stored on a private disk and given out only through links that expire after 10 minutes.
- The admission pass is shown only after approval, and only to the owner and to staff.
- Every sensitive action writes an audit row: documents, decisions, payments, enrolment, CMS changes.

### 3.4 Website CMS
- Staff with `cms.manage` edit content in `/admin/cms`. Changes show on the site at once.
- **Notices** have a publish date and an expiry date. A notice is visible only on the publish-and-expiry window, and only if marked as published.
- **Gallery** images are stored on the public disk. Upload JPG, PNG or WEBP up to 4 MB.
- **Governing body, teachers and staff** are groups of people with designation, department and optional photo. Teacher and governing-body lists were seeded from the old pages.
- **Admission programmes** can be edited for title, description, fees, seats, deadlines and open/closed. They cannot be created or deleted in the CMS, because their class, group and eligibility rules and their applications depend on them. Those are set by the admissions seeder.
- **Editable pages** replace the built-in text for About, Chairman's message, Principal's message and the admission page (slug `admission`). Only formatting tags are kept; scripts and event handlers are removed when saved and again when shown.
- Every change is audited.
- The mobile app can read the same published content from `/api/v1/cms/...`.

---

## 4. Setting it up

### 4.1 Environment (`.env`)
```
ADMISSIONS_PAYMENT_PROVIDER=sslcommerz   # use mock only on a laptop
ADMISSIONS_MOCK_PAYMENTS=false           # the mock gateway is on only when this is true (local and testing default to true)
ADMISSIONS_MOCK_SECRET=<a long random string, shared with the test tools>
ADMISSIONS_OTP_DEBUG=false          # true only on a laptop: shows the code on screen
SSL_STORE_ID=                       # SSLCommerz, when you have the sandbox details
SSL_STORE_PASSWORD=
SSL_IS_SANDBOX=true
```

### 4.2 Database and accounts
Run these from the project root. **Back up the live database first.** The new migrations add columns and the CMS tables.

```bash
php artisan migrate                              # applies the new migrations, including the programme description column
php artisan db:seed --class=AdmissionsRbacSeeder # roles, permissions, staff accounts
php artisan db:seed --class=AdmissionSeeder      # 2026-27 classes, groups, sections, 16 programmes
php artisan db:seed --class=CmsSeeder            # current notices, governing body and teachers
php artisan storage:link                         # needed for gallery and staff photos
```

For a fresh database, `php artisan migrate:fresh --seed` runs everything.

Development staff accounts (**change these before any shared server**):

| Email | Password | Role |
| :--- | :--- | :--- |
| council@mcc.edu.bd | Council@123 | Admission Council Member |
| officer@mcc.edu.bd | Officer@123 | Admissions Officer |
| principal@mcc.edu.bd | Principal@123 | Principal |
| admin@mcc.edu.bd | Admin@123 | Super Admin |

### 4.3 Scheduler (expiry)
Add this one cron entry on the server. It runs the daily expiry of applications past their deadline.
```
* * * * * cd /path/to/College-Management && php artisan schedule:run >> /dev/null 2>&1
```
You can also run it by hand: `php artisan admissions:expire-applications`.

### 4.4 Running it
```bash
php artisan serve
```
Open `http://127.0.0.1:8000/admissions`.

---

## 5. Testing

### 5.1 Test database
Tests write to a separate database. They refuse to start against `mcc_db_live`. Create and seed it once:

```bash
DB_DATABASE=mcc_admissions_test php artisan migrate:fresh --seed --force
```

`phpunit.xml` already points tests at `mcc_admissions_test`. Your database user needs access to it.

### 5.2 Run
```bash
php artisan test --testsuite=Feature --no-coverage
```

The suite has 112 tests (458 assertions). They cover:

| Area | Test file |
| :--- | :--- |
| Full journey, with and without assessment | `ApplicationLifecycleTest` |
| Same journey over HTTP, as the app and desk use it | `HttpJourneyTest` |
| API responses and the applicant token | `ApiEndpointsTest` |
| Who can call what, and applicant ownership | `AccessControlTest` |
| Payments: unsigned, wrong amount, replay, failure, cancel, unknown gateway | `PaymentSecurityTest` |
| Enrolment rules: fee, full section, wrong class, expired pass, double enrolment, siblings | `EnrollmentRulesTest` |
| Separation of duties | `SeparationOfDutiesTest` |
| Submission rules: documents, deadline, eligibility, duplicates, withdraw, expiry | `SubmissionRulesTest` |
| Waitlist, rejection reasons, upload checks, signed document links | `BusinessRulesTest` |
| Web pages: OTP-gated form, track, mock checkout, admin pages | `WebPortalTest` |
| Every public, applicant, staff and CMS page loads | `SmokePagesTest` |
| CMS: publishing, sanitising, uploads, public API, audit | `tests/Feature/Cms/CmsTest` |

### 5.3 Postman
Import `docs/postman_collection.json`. Run folders **A, B, C** in order. The payment callback step signs the request automatically with `mock_secret`, which must equal `ADMISSIONS_MOCK_SECRET` in your `.env`.

---

## 6. Using it: brief guidelines

**Applicant (mobile app or website)**
1. Enter your mobile number and the code you receive.
2. Fill the form. Have the child's birth certificate and photo ready (school) or the transcript (college).
3. Submit, then pay the application fee.
4. Use **Track** with the application number, your phone and a code to see progress.
5. After approval, pay the admission fee and show the pass QR code at the desk.

**Admissions officer**
1. Sign in at `/login`, then open `/admin/admissions`.
2. Open an application. Check each document; reject with a reason where needed (for example "blurry scan").
3. When every document is verified, the application moves to review.
4. For programmes with an assessment: schedule it, and enter the score once it is done.
5. After the admission fee is paid, choose a section with a free seat and enrol. The Student ID appears on the result.

**Admission council**
1. Open the application and review the documents and assessment.
2. Choose Approve, Waitlist or Reject. Waitlist and Reject need a reason.
3. A full programme turns Approve into Waitlist automatically.

**Principal**
- Review the dashboard. Override a full programme only with a written reason.
- Manage website content in `/admin/cms`.

**Website editor (CMS)**
1. Sign in and open `/admin/cms`.
2. To publish a notice: **Notices → New item**. Add a publish date and an expiry if needed, then tick Published.
3. To add a teacher: **Governing body, teachers & staff → New item**, choose the group, and tick Published.
4. To change a page (About or messages): **Editable pages → Edit**. Write HTML using only paragraphs (`<p>`), headings (`<h2>`–`<h4>`), bold, italics, lists, links and quotes. Scripts are removed.
5. Check the public page afterwards. A draft (unticked) item stays hidden.

---

## 7. Known rough edges
- The notice's date filter uses the server's date. Check the server clock.
- Document upload is limited to 2 MB by PHP's default `upload_max_filesize`, so files up to 5 MB will fail on a default PHP install. Raise `upload_max_filesize` and `post_max_size` in the production PHP settings.
- Rejected documents keep their file until replaced. Failed submissions can leave a file behind; a cleanup job is not written yet.
- The CMS editor is a plain HTML text box, not a rich-text editor.
