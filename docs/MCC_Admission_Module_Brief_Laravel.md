# TASK BRIEF v2: Build the "MCC–Hello Future" Admission & Onboarding Engine (Laravel + MySQL)

You are a Senior Full-Stack Engineer and System Architect. Scaffold, implement and verify the **Digital Admission & Onboarding Module** of the **MCC–Hello Future Digital Academic Ecosystem**, following the institutional architecture document (Phase 1: Identity, Admission, Student Information, Guardian, Academic Structure).

Work autonomously, but follow the decisions in this brief exactly. Where something is not specified, choose the simplest production-grade option and record it in `docs/DECISIONS.md`. Do not add features outside this scope.

---

## 1. Scope & Lifecycle

Serves **School Section (Playgroup–Class 10)** and **College Section (Class XI Science / Business Studies / Humanities)**.

Strict lifecycle:
1. **Public Discovery:** programs, eligibility, fees, deadlines.
2. **Application:** applicant profile, form data, guardian details, document uploads (object storage references only).
3. **Application Fee:** paid via a payment gateway, confirmed server-to-server.
4. **Assessment & Council Review:** entrance test / viva / interview, score entry, Admission Council decision (`APPROVED`, `WAITLISTED`, `REJECTED`).
5. **Admission QR & Admission Fee:** QR issued on approval; final admission fee paid.
6. **Enrollment & Provisioning:** permanent Student ID, guardian linking, class/section allocation, student account activation, `StudentEnrolled` event, onboarding package.

Out of scope for this task (leave extension points only): LMS, timetable, attendance, full finance engine (invoices/ledger/scholarships), HR, real payment-gateway credentials.

---

## 2. Fixed Technology Stack (do not substitute)

| Layer | Choice |
| :--- | :--- |
| Language / Framework | PHP 8.3+, **Laravel 11+** |
| Database | **MySQL 8.0** (InnoDB, `utf8mb4`) |
| Auth | **Laravel Sanctum** (API tokens with abilities) + session guard for the admin web portal |
| RBAC | `spatie/laravel-permission` (roles + permission strings) |
| Validation | Laravel **Form Requests** (one per endpoint). Shared rules live in `app/Modules/Admissions/Rules` and are reused by Blade forms and API |
| Web UI | **Laravel Blade + Tailwind CSS + Alpine.js** (Vite). Lucide icons. Plain **JavaScript** only, no TypeScript |
| Queue / Cache | Redis (queue, cache, rate limiter). `database` driver as fallback in `.env.example` |
| File storage | S3-compatible (`league/flysystem-aws-s3-v3`). **MinIO** in local dev. DB stores metadata and object keys only, never blobs |
| Testing | Pest (or PHPUnit) feature tests + Laravel Dusk (or Playwright) for the E2E journey |
| Local infra | `docker-compose.yml`: app, MySQL, Redis, MinIO, Mailpit |

API-first: every web screen must use the same Action/Service classes as the API, so the future Student/Guardian mobile apps can reuse the API unchanged.

---

## 3. Code Organization

```
app/Modules/Admissions/
  Actions/          # one class per use case (SubmitApplication, RecordCouncilDecision, EnrollStudent...)
  Enums/            # ApplicationStatus, PaymentStatus, PaymentType, DocType, AssessmentType, Decision
  Http/Controllers/Api/V1/
  Http/Controllers/Web/
  Http/Requests/
  Http/Resources/
  Models/
  Policies/
  Services/         # NumberSequence, QrToken, OtpService, DocumentService, PaymentService
  Payments/         # PaymentGateway interface, MockGateway, GatewayManager
  Events/ Listeners/ Notifications/
database/migrations, database/seeders, tests/Feature/Admissions, tests/Browser
docs/ (DECISIONS.md, API.md, ER diagram in Mermaid)
```

Rules: thin controllers; business logic in Actions; all state changes go through the status state machine (section 5); every multi-table write runs inside `DB::transaction`; all IDs are **UUID** (`HasUuids`), money is `DECIMAL(12,2)`, JSON columns use MySQL `JSON`.

---

## 4. Database Schema (create migrations + Eloquent models + factories)

Conventions: `id CHAR(36) PK`, `created_at`, `updated_at` on every table, foreign keys with explicit `ON DELETE` behavior, indexes on all FKs and on `status`, `email`, `mobile`.

### 4.1 Identity and student core
- **users**: id, user_type (`APPLICANT|STUDENT|GUARDIAN|STAFF`), name, mobile (E.164, e.g. +8801XXXXXXXXX), email (unique, nullable for young students), password_hash (nullable until set), status (`ACTIVE|PENDING|SUSPENDED`), profile_photo (object key), timestamps.
- **student_profiles**: id, user_id FK, student_number (unique), blood_group, dob, gender, nationality, current_address.
- **guardian_profiles**: id, user_id FK, relation, occupation, nid_passport (encrypted cast), emergency_contact.
- **student_guardians**: id, student_id FK (student_profiles), guardian_id FK (guardian_profiles), is_primary. Unique(student_id, guardian_id).

### 4.2 Academic structure (minimal, owned by this phase)
- **academic_years**: id, name (e.g. `2026-27`), starts_on, ends_on, is_current.
- **classes**: id, code (`PG, NURSERY, KG, 1..10, XI, XII`), name, section_type (`SCHOOL|COLLEGE`), sort_order.
- **groups**: id, class_id FK, code (`SCIENCE|BUSINESS|HUMANITIES`), name. Used for College only.
- **sections**: id, academic_year_id FK, class_id FK, group_id FK nullable, name (A, B...), capacity.
- **student_enrollments**: id, student_id FK, academic_year_id FK, class_id FK, group_id FK nullable, section_id FK, roll_no nullable, status (`ACTIVE|TRANSFERRED|COMPLETED`), enrolled_on. Unique(student_id, academic_year_id).

### 4.3 Admissions domain
- **admission_programs**: id, code (unique), title, section_type, class_id FK, group_id FK nullable, academic_year_id FK, min_eligibility JSON, fee_application, fee_admission, seats, application_opens_at, application_deadline, requires_assessment (bool), is_active.
- **applications**: id, application_no (unique), program_id FK, applicant_user_id FK, applicant_name, email, mobile, dob, gender, academic_history JSON, status (default `DRAFT`), submitted_at, qr_token_hash, qr_expires_at, qr_used_at, enrolled_student_id FK nullable, council_notes (latest summary only). Unique(program_id, mobile, dob) to block duplicates.
- **application_guardians**: id, application_id FK, name, relation, mobile, email, occupation, nid_passport (encrypted), is_primary.
- **application_documents**: id, application_id FK, doc_type (`BIRTH_CERT|PREVIOUS_TRANSCRIPT|PHOTO|NID|OTHER`), object_key, file_name, mime_type, size_bytes, checksum_sha256, status (`PENDING_UPLOAD|UPLOADED|VERIFIED|REJECTED`), verified_by FK nullable, verified_at.
- **admission_assessments**: id, application_id FK, assessment_type (`WRITTEN|VIVA|INTERVIEW`), scheduled_at, venue, score, max_score, evaluator_id FK, remarks.
- **admission_decisions**: id, application_id FK, decision (`APPROVED|WAITLISTED|REJECTED`), decided_by FK, decided_at, notes, waitlist_rank nullable. Append-only history.
- **application_status_history**: id, application_id FK, from_status, to_status, actor_id nullable, reason, created_at.

### 4.4 Payments
- **admission_payments**: id, application_id FK, payment_type (`APPLICATION_FEE|ADMISSION_FEE`), amount, currency (default `BDT`), provider, transaction_ref (unique), payment_status (`INITIATED|PENDING|SUCCESS|FAILED|CANCELLED|REFUNDED`), payment_details JSON, paid_at. Unique(application_id, payment_type) where status = SUCCESS (enforce in code with `lockForUpdate`).
- **payment_gateway_logs**: id, payment_id FK nullable, provider, direction (`REQUEST|CALLBACK|VERIFY`), payload JSON, signature_valid bool, http_status, created_at. Never delete.

### 4.5 Supporting
- **otp_codes**: id, mobile, purpose (`TRACK|LOGIN`), code_hash, attempts, expires_at, consumed_at.
- **number_sequences**: key, year, last_value. Used with `lockForUpdate` for atomic `application_no` and `student_number`.
- **audit_logs** (required by architecture): id, user_id nullable, action, module, record_id, old_value JSON, new_value JSON, ip_address, device, created_at. Written for: status changes, document verification, payment confirmation, council decisions, enrollment.
- Spatie tables: roles, permissions, model_has_roles, etc.

Identifier formats:
- `application_no`: `APP-{YYYY}-{6-digit seq}`, e.g. `APP-2026-000123`.
- `student_number`: `MCC{YY}{S|C}{6-digit seq}`, e.g. `MCC26S000123` (S = School, C = College).

---

## 5. Application Status State Machine

Statuses: `DRAFT, PENDING_APP_FEE, SUBMITTED, UNDER_REVIEW, EXAM_SCHEDULED, ASSESSED, APPROVED, WAITLISTED, REJECTED, ADMISSION_FEE_PAID, ENROLLED, WITHDRAWN, EXPIRED`

| From | To | Trigger / guard |
| :--- | :--- | :--- |
| DRAFT | PENDING_APP_FEE | Applicant submits; all required fields + required documents present; program open and before deadline |
| PENDING_APP_FEE | SUBMITTED | Application-fee payment confirmed `SUCCESS` (server-to-server) |
| SUBMITTED | UNDER_REVIEW | Officer starts verification |
| UNDER_REVIEW | EXAM_SCHEDULED | Assessment scheduled (only if `requires_assessment`) |
| UNDER_REVIEW | ASSESSED | Direct path when `requires_assessment = false` (e.g. Playgroup/KG interaction recorded) |
| EXAM_SCHEDULED | ASSESSED | Score recorded |
| ASSESSED | APPROVED / WAITLISTED / REJECTED | Council decision (requires `admission.decide`) |
| WAITLISTED | APPROVED / REJECTED | Seat available / council decision |
| APPROVED | ADMISSION_FEE_PAID | Admission-fee payment confirmed; QR must be valid |
| ADMISSION_FEE_PAID | ENROLLED | Admin enrollment action |
| DRAFT, PENDING_APP_FEE, SUBMITTED, APPROVED | WITHDRAWN | Applicant/officer withdraws |
| DRAFT, PENDING_APP_FEE, APPROVED | EXPIRED | Scheduled job after deadline |

Any transition not in this table must throw a domain exception (HTTP 422). Each transition writes `application_status_history` and `audit_logs`. Seat capacity is checked on APPROVED; when full, the decision is forced to WAITLISTED unless an Admin overrides with a reason.

---

## 6. Roles & Permissions

Roles: `Applicant, Guardian, Student, AdmissionOfficer, AdmissionCouncil, Principal, Admin, SuperAdmin`.

Permission strings (examples): `admission.program.manage`, `admission.application.view`, `admission.application.view.own`, `admission.document.verify`, `admission.assessment.record`, `admission.decide`, `admission.payment.view`, `admission.enroll`, `admission.report.view`.

- Applicant: own application only (policy enforced).
- AdmissionOfficer: verify documents, schedule assessments, view all.
- AdmissionCouncil: record scores, decide.
- Admin: enroll and provision, override capacity (with audit reason).
- Principal: read-only oversight, view reports, ability to override with audit entry.
- Separation of duties: the user who recorded a decision cannot be the one who enrolls the same application.

---

## 7. API Specification (`/api/v1/admissions`)

All responses use a consistent envelope `{ "data": ..., "meta": ..., "errors": ... }`. Use API Resources. Validation errors are 422 with field-level messages. Rate-limit all public endpoints.

### Public / Applicant
| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :--- |
| GET | `/programs` | Open programs with eligibility, fees, deadlines, seats left | Public |
| POST | `/applicants/otp/request` | Send OTP to mobile (throttled) | Public |
| POST | `/applicants/otp/verify` | Verify OTP, return short-lived Sanctum token (ability `applicant`) | Public |
| POST | `/applications` | Create DRAFT (creates applicant user if needed) | Applicant |
| PATCH | `/applications/{id}` | Update DRAFT (partial save per wizard step) | Applicant (owner) |
| POST | `/applications/{id}/submit` | Validate completeness, move to PENDING_APP_FEE | Applicant (owner) |
| GET | `/applications/{id}` | Details + status timeline + schedule + QR (if approved) | Applicant (owner) / staff |
| POST | `/applications/{id}/withdraw` | Withdraw | Applicant (owner) |
| POST | `/track` | Lookup by `application_no` + OTP-verified mobile (generic error to prevent enumeration) | Public |

### Documents
| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :--- |
| POST | `/documents/presign` | Validate type/mime/size, create `PENDING_UPLOAD` row, return presigned PUT URL (expires in 10 min) | Applicant / staff |
| POST | `/documents/{id}/confirm` | Verify the object exists (HEAD), re-check size and mime, compute checksum, mark `UPLOADED` | Applicant / staff |
| GET | `/applications/{id}/documents` | List with short-lived signed view URLs | Owner / staff |
| POST | `/documents/{id}/verify` | Verify or reject with reason | AdmissionOfficer |

Limits: JPG/PNG/PDF only, max 5 MB (photo 1 MB), max 10 files per application, private bucket, never expose permanent public URLs.

### Payments
| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :--- |
| POST | `/payments/initiate` | Body: `application_id`, `payment_type`. Server computes amount from the program (never trust client amount). Returns gateway redirect URL | Applicant (owner) |
| POST | `/payments/callback/{provider}` | Gateway callback/IPN | Public (signature-verified) |
| GET | `/payments/{ref}` | Payment status | Owner / staff |

Callback requirements: verify HMAC signature; call the gateway's verify endpoint (server-to-server) before trusting; check amount and currency against the stored payment; idempotent on `transaction_ref` (duplicate callbacks are no-ops and return 200); log every request in `payment_gateway_logs`; on first SUCCESS fire the status transition and `PaymentReceived`. Build `PaymentGateway` interface + `MockGateway` (sandbox pages for success / fail / cancel); real providers (SSLCommerz, bKash, Nagad) plug in later without touching business code.

### Council and staff
| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :--- |
| GET | `/council/applications` | Filters: status, program, score range, date, search; pagination | Officer / Council / Admin |
| GET | `/council/dashboard` | Counts: total, pending verification, under assessment, approved, waitlisted, rejected, enrolled | Staff |
| POST | `/interviews` | Schedule (`scheduled_at`, venue, type) | Officer / Council |
| POST | `/interviews/{id}/score` | Record score, max score, remarks | Council |
| POST | `/council/decision` | `application_id`, `decision`, `notes`; issues QR on APPROVED | Council / Admin |
| POST | `/qr/verify` | Validate QR token (not expired, not used, matches application) | Staff |
| GET | `/classes` and `/sections?program_id=` | Options for allocation, with remaining capacity | Admin |
| POST | `/enroll` | `application_id`, `section_id`. See section 8 | Admin |

### Auth
`POST /api/v1/auth/login`, `POST /api/v1/auth/logout`, `POST /api/v1/auth/refresh` for staff. Token expiry and revocation configured; OTP and login endpoints throttled (e.g. 5/min per mobile+IP).

---

## 8. Enrollment & Provisioning (single transaction, idempotent)

Preconditions: status = `ADMISSION_FEE_PAID`, admission fee `SUCCESS`, QR valid, section has capacity, actor has `admission.enroll` and is not the decision maker.

Steps inside `DB::transaction` with row lock on the application:
1. Generate `student_number` from `number_sequences`.
2. Create or reuse the student `users` + `student_profiles` (copy data from the application).
3. Guardians: for each `application_guardians` row, find an existing guardian user by mobile or email (sibling case) and reuse it; otherwise create user + `guardian_profiles`. Create `student_guardians` (one primary).
4. Create `student_enrollments` (year, class, group, section).
5. Set application `enrolled_student_id`, status `ENROLLED`, mark QR used.
6. Write audit + status history.
7. After commit: dispatch `StudentEnrolled` (queued listeners): send welcome email/SMS with **set-password link** (never a plain password), generate enrollment letter PDF (stored in object storage), create onboarding checklist (digital ID card pending, book list, uniform info, orientation, transport and library registration as placeholder tasks).

Calling `/enroll` twice returns the same result and creates no duplicates.

---

## 9. Events

Use Laravel events with queued listeners and `ShouldDispatchAfterCommit`: `ApplicationSubmitted`, `PaymentReceived`, `AssessmentScheduled`, `AdmissionDecided`, `AdmissionApproved`, `StudentEnrolled`. Notification listeners send email/SMS (log driver in dev) for each. Keep listener classes small so the future Notification Service can take over.

---

## 10. Web UI (Blade + Tailwind + Alpine)

Every screen must handle **loading, error, empty, offline and success** states, validation messages inline, double-submit protection, mobile-first responsive layout, keyboard accessibility and labelled inputs. Show a clear offline banner and keep wizard progress in `localStorage` (draft also saved server-side per step).

### A. Public Applicant Portal (`/admissions`)
1. **Programs & Eligibility** (`/admissions`): cards grouped by School and College, deadlines, seats left, fee table, "Apply" CTA (disabled with reason after deadline or when closed).
2. **Application Wizard** (`/admissions/apply/{program}`), 5 steps with progress bar and autosave:
   - Step 1: Basic and contact info (OTP mobile verification here).
   - Step 2: Guardian details and relationship (required; for Playgroup–Class 5 the guardian is treated as the primary operator of the application).
   - Step 3: Prior academic record (SSC/previous class GPA/marks, board, year) and group choice (Science / Business Studies / Humanities for XI). Eligibility is checked live against `min_eligibility`.
   - Step 4: Document uploaders: drag and drop, preview, per-file progress, retry, remove, type/size errors.
   - Step 5: Review, edit links per step, consent checkbox (data use for minors), application-fee checkout.
3. **Payment return page**: success, failed, cancelled and pending-verification states (poll status; never trust the redirect alone).
4. **Track** (`/admissions/track`): `application_no` + mobile OTP; status timeline, exam schedule, QR (when approved) with "Pay admission fee" action, and onboarding checklist after enrollment.

### B. Admission Officer and Council Portal (`/admin/admissions`)
1. **Pipeline Dashboard**: metrics (Total, Pending Verification, Under Assessment, Approved, Waitlisted, Enrolled), filterable table, pagination, empty state.
2. **Applicant Evaluation View**: split screen. Left: submitted data and document viewer with verify/reject. Right: assessment scheduling and score entry, council notes, decision bar (`Approve`, `Waitlist`, `Reject`) with confirmation modal and required reason for reject/waitlist.
3. **Enrollment Action**: class/section dropdown (shows remaining capacity), one-click "Enroll & Provision Student" with confirmation and result screen (Student ID, guardian link status).

---

## 11. Seed Data

- Academic year `2026-27` (current).
- Classes: Playgroup, Nursery, KG, Class 1–10 (School); Class XI, Class XII (College) with groups Science, Business Studies, Humanities for XI and XII.
- Programs: one per School class (Playgroup–Class 10) and three for Class XI (Science, Business, Humanities) with realistic eligibility JSON (e.g. SSC minimum GPA), application and admission fees in BDT, deadlines, seats.
- Sections: A and B per class/group with capacity.
- Users: one each of Admin, AdmissionOfficer, AdmissionCouncil, Principal (documented dev credentials, dev only).

---

## 12. Security & Compliance Checklist

- Encrypted casts for NID/passport; PII never logged; minors' data access limited by policy.
- Private bucket, signed URLs, mime sniffing on confirm, optional ClamAV hook.
- Rate limiting and OTP attempt limits, generic errors on track lookup.
- CSRF for web, Sanctum abilities for API, CORS allow-list from `.env`.
- Idempotency on payment callbacks and enrollment; DB transactions and row locks on every money/status change.
- Audit log on every sensitive action. Data retention note for rejected applications in `DECISIONS.md`.
- Only the mock gateway is enabled; no real keys in the repo. `.env.example` included.

---

## 13. Execution Plan

1. **Scan and plan:** inspect the repo, then write `docs/PLAN.md` and `docs/DECISIONS.md`. If no Laravel app exists, create one with the stack above.
2. **Infrastructure:** `docker-compose.yml`, `.env.example`, MinIO bucket bootstrap.
3. **Database:** migrations, models, enums, factories, seeders (section 11). Run and confirm.
4. **Backend:** Actions, Services, Form Requests, Policies, Resources, controllers, routes, events, mock gateway, scheduled jobs (expire applications, clean orphan uploads).
5. **Web UI:** applicant portal then staff portal (section 10).
6. **Tests:** feature tests for every endpoint and transition, including the negative cases below.
7. **E2E verification:** launch services and run the full journey in a browser: apply, verify OTP, upload documents, pay application fee, officer verifies, schedule and score, council approves, QR shown, pay admission fee, admin enrolls. Save screenshots or a recording in `docs/e2e/`.
8. **Final report:** `docs/API.md`, ER diagram (Mermaid), how to run, known limitations.

---

## 14. Acceptance Criteria

Happy path passes end to end. In addition, tests must prove:
- Failed or cancelled payment leaves status unchanged and allows retry.
- Duplicate or replayed callback does not double-process; invalid signature or wrong amount is rejected and logged.
- Client-supplied amount is ignored.
- An illegal status transition returns 422.
- Applicant A cannot read Applicant B's application or documents (403/404).
- Enrollment is blocked without a paid admission fee, with a full section, or by the same user who decided; double enroll is idempotent.
- Waitlisted applicant can later be approved; rejected applicant cannot pay or enroll.
- Existing guardian (same mobile) is reused for a sibling; no duplicate guardian user.
- Upload rejects wrong type, oversize, and unconfirmed files; application cannot be submitted without required documents.
- Applications after the deadline cannot be submitted; expired jobs behave correctly.
- Every sensitive action has an `audit_logs` row.
- All UI screens show loading, error, empty and offline states.

Do not finish until all tests pass and the E2E run is recorded. Report anything not completed explicitly.
