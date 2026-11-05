# Admissions API v1 (reference)

Base URL (local): `http://127.0.0.1:8000/api`. Every route below is prefixed with `/v1/admissions` unless noted.

Full setup, URLs and how-to: [ADMISSION_AND_CMS_GUIDE.md](ADMISSION_AND_CMS_GUIDE.md). Postman: `docs/postman_collection.json`, folders A to D.

## Conventions

- Send `Accept: application/json`. Requests with a body also send `Content-Type: application/json`, or `multipart/form-data` for uploads.
- **Success envelope:** `{ "success": true, "message": "...", "data": ..., "meta": { "timestamp": "..." }, "errors": null }`
- **Error envelope:** `{ "success": false, "message": "...", "data": null, "meta": {...}, "errors": { "field": ["message"] } }`
- Validation failure: `422`. Not signed in: `401`. Signed in but not allowed: `403`. Not found, or not yours: `404` (the same answer for "not yours" and "does not exist", so IDs cannot be probed).
- Money is in BDT with two decimals. Amounts always come from the programme, never from the request.
- Mobile numbers: 11 digits starting `01` (for example `01712345678`).

## Authentication

| Who | How | Token ability |
| :--- | :--- | :--- |
| Applicant | OTP to mobile, then `applicants/otp/verify` returns a token | `applicant` |
| Staff | `auth/login` with email and password | `staff` plus permissions |
| Existing app users | `/api/app_login` (legacy) | all abilities; these do **not** get admissions staff rights |

Send the token as `Authorization: Bearer <token>`.

## Applicant endpoints

| Method | Path | Auth | Purpose |
| :--- | :--- | :--- | :--- |
| GET | `/programs` | none | Active programmes. Optional `section_type=SCHOOL|COLLEGE` |
| GET | `/programs/{id}` | none | One programme |
| POST | `/applicants/otp/request` | none | Send a code. Body: `phone`, `purpose` (`APPLICATION_AUTH` or `TRACK`) |
| POST | `/applicants/otp/verify` | none | Body: `phone`, `code`. Returns `token` for `APPLICATION_AUTH` |
| POST | `/apply` (alias `/applications`) | applicant | Create a draft. Body: `program_id`, `candidate_name`, `phone` (must be the verified number), `candidate_dob`, `gender` (`MALE`, `FEMALE`, `OTHER`), `current_address`, optional `email`, `blood_group`, `permanent_address`, `previous_school`, `previous_gpa`, `academic_history` |
| GET | `/applications/{id}` (alias `/status`) | owner or staff | Full view. The pass (`qr`) shows only after approval, and only to the owner or staff |
| PUT | `/applications/{id}` | owner | Edit a draft and set guardians. `guardians[]`: `relation` (`FATHER`, `MOTHER`, `GUARDIAN`), `full_name`, `phone`, optional `email`, `occupation`, `nid_passport`, `is_primary` |
| POST | `/applications/{id}/documents` | owner | Upload. Form fields: `doc_type` (`PHOTO`, `BIRTH_CERT`, `PREVIOUS_TRANSCRIPT`, `NID`, `OTHER`), `file`. Limits below |
| GET | `/applications/{id}/documents` | owner or staff | List documents with 10-minute signed links |
| POST | `/applications/{id}/submit` | owner | Checks everything, then moves to `PENDING_APP_FEE` (or `SUBMITTED` if the fee is 0) |
| POST | `/applications/{id}/withdraw` | owner | Withdraw (not after enrolment) |
| POST | `/applications/{id}/pay` | owner | Start a payment. Body: `payment_type` (`APPLICATION_FEE` or `ADMISSION_FEE`), `provider` (`mock` in local and testing only, or `sslcommerz`). Returns `redirect_url` |
| GET | `/payments/{ref}` | owner or staff | Payment status |
| POST | `/track` | none | Body: `application_no`, `phone`, `code` (from `applicants/otp/request` with `purpose=TRACK`). Returns the status without the pass |

### Document rules
- PDF, JPG, PNG or WEBP. The file's content is checked, not the file name.
- At most 5 MB per file, 1 MB for photos, 10 documents per application.
- Re-uploading a type replaces the old file.
- Required before submit: School programmes (Playgroup to Class 10) need `PHOTO` and `BIRTH_CERT`. College programmes (Class XI) need `PHOTO` and `PREVIOUS_TRANSCRIPT`.

### Application states
`DRAFT` → `PENDING_APP_FEE` → `SUBMITTED` → `UNDER_REVIEW` → (`EXAM_SCHEDULED` →) `ASSESSED` → `APPROVED` / `WAITLISTED` / `REJECTED` → `ADMISSION_FEE_PAID` → `ENROLLED`. `WITHDRAWN` and `EXPIRED` can end an application early.

## Payment callbacks

| Method | Path | Notes |
| :--- | :--- | :--- |
| POST | `/payments/callback` or `/payments/callback/{provider}` | Server-to-server. Body: `transaction_ref`, `status`, `amount`, `currency`, `signature` (mock). A wrong, missing or altered signature returns `422` and is logged. A repeat of a success changes nothing |

Mock signature: `HMAC-SHA256(transaction_ref|STATUS|amount, ADMISSIONS_MOCK_SECRET)` in hex.

SSLCommerz (`provider: sslcommerz`) needs `SSL_STORE_ID` and `SSL_STORE_PASSWORD` in `.env` and refuses to run without them. It is not yet verified against the live gateway.

Mock payments are enabled only when the app runs in `local` or `testing`. On a live server a `mock` request is refused.

## Aliases (kept for the Flutter app)

| Path | Same as |
| :--- | :--- |
| `POST /apply` | `POST /applications` |
| `GET /applications/{id}/status` | `GET /applications/{id}` |
| `POST /auth/otp/send`, `POST /auth/otp/verify` | `applicants/otp/request`, `applicants/otp/verify` |
| `POST /payments/initiate` (body: `application_id`, `payment_type`, `provider`) | `POST /applications/{id}/pay` |
| `POST /council/decision` (body: `application_id`) | `POST /council/applications/{id}/decision` |
| `POST /documents/{id}/verify` | `POST /officer/applications/{id}/verify-document/{docId}` |
| `POST /interviews`, `POST /interviews/{id}/score` | `POST /officer/applications/{id}/schedule-assessment`, `.../assessments/{assessmentId}/score` |
| `POST /enroll` (body: `application_id`) | `POST /officer/applications/{id}/enroll` |

## Guardian flow in the app (same steps as the website)

The website flow is: pick a programme, enter the guardian's details, click **Send OTP**, enter the 6-digit code that arrives by email, then apply. The app does the same with these calls.

| Step | Method and path | Body | Result |
| :--- | :--- | :--- | :--- |
| 1. Programme and fees | `GET /programs/{id}` | none | Fees, seats, deadline, `is_open` |
| 2. Send OTP | `POST /guardian/otp/request` | `program_id`, `guardian_name` (3+ chars), `relation` (`FATHER`, `MOTHER`, `GUARDIAN`), `email`, `phone` (BD mobile) | `sent: true`. The code is emailed. Valid 10 minutes. Call again to resend |
| 3. Verify OTP | `POST /guardian/otp/verify` | `email`, `code` (6 digits) | `token` with abilities `applicant` and `guardian`, `program_id`, `guardian` details |
| 4. Apply | `POST /apply` with `Authorization: Bearer <token>` | as for a normal applicant, plus optional `guardian_relation` | 201. The guardian is saved as the primary guardian |
| 5. Documents, submit, pay | as in the applicant section | | |

Notes:
- Step 2 is refused with `422` for a closed programme and with `503` if the email cannot be sent.
- Step 3 with no pending request returns `422`. A wrong code returns `422`.
- The candidate's `phone` in step 4 must equal the guardian's `phone` from step 2. Otherwise `422`.
- In local and testing, step 2 also returns `debug_otp`. Test code `123456` is accepted in testing.
- The OTP purposes accepted by the phone-based endpoints are `APPLICATION_AUTH` and `TRACK` only.

## Staff endpoints

Each needs a staff token and the permission in the last column.

| Method | Path | Permission |
| :--- | :--- | :--- |
| POST | `/auth/login` | (any admissions permission) |
| POST | `/auth/refresh`, `/auth/logout` | staff token |
| GET | `/council/dashboard` | `admission.report.view` |
| GET | `/council/applications` | `admission.application.view` |
| GET | `/council/applications/{id}` | `admission.application.view` |
| POST | `/council/applications/{id}/decision` (or `/council/decision` with `application_id`) | `admission.decide`. Body: `decision` (`APPROVED`, `WAITLISTED`, `REJECTED`), `remarks` (required for the last two) |
| POST | `/officer/applications/{id}/verify-document/{docId}` (or `/documents/{id}/verify`) | `admission.document.verify`. Body: `status` (`VERIFIED`, `REJECTED`), `review_notes` (required for rejection) |
| POST | `/officer/applications/{id}/schedule-assessment` (or `/interviews`) | `admission.assessment.record`. Body: `assessment_type` (`WRITTEN`, `VIVA`, `INTERVIEW`), `scheduled_at` (future), `venue` |
| POST | `/officer/applications/{id}/assessments/{assessmentId}/score` (or `/interviews/{id}/score`) | `admission.assessment.record`. Body: `obtained_marks` (0 to 100), `comments` |
| GET | `/classes` | `admission.enroll` |
| GET | `/sections?program_id=` | `admission.enroll`. Shows free seats |
| POST | `/officer/applications/{id}/enroll` (or `/enroll`) | `admission.enroll`. Body: `section_id`, `roll_no`. Returns the Student ID |
| POST | `/qr/verify` | `admission.application.view`. Body: `token` |

Rules enforced by the server (not just the app): decider cannot enrol the same candidate; enrolment needs a paid admission fee and a valid pass; the section must belong to the programme's class, group and year; a full programme forces a waitlist unless a principal overrides with a reason.

## Public CMS (read-only)

| Method | Path | Returns |
| :--- | :--- | :--- |
| GET | `/v1/cms/notices` | Published notices inside their date window |
| GET | `/v1/cms/gallery` | Published gallery images |
| GET | `/v1/cms/staff?group=GOVERNING_BODY|TEACHER|STAFF` | Published people |
| GET | `/v1/cms/pages/{slug}` | A published page (body is sanitised); 404 if not published |

## Errors you may see

| Code | Meaning |
| :--- | :--- |
| 401 | Missing or invalid token |
| 403 | Signed in, but the permission is missing |
| 404 | Not found or not yours |
| 422 | Rule failed: the `errors` object says which field |
| 429 | Too many codes or logins in a short time |
| 503 | A payment gateway is unreachable |
