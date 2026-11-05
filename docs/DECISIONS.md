# Architectural Decisions: MCC–Hello Future Admission & Onboarding Engine

This document records the architectural and design decisions made for the Digital Admission & Onboarding Module of Maestro Crown College (MCC), following the specifications in `MCC_Admission_Module_Brief_Laravel.md`.

---

## 1. Domain Organization & Modularity
- **Decision**: All domain logic lives under `app/Modules/Admissions/`.
- **Structure**:
  - `Actions/`: Single-responsibility use-case action classes (e.g., `SubmitApplication`, `ScheduleAssessment`, `RecordCouncilDecision`, `EnrollStudent`, `InitiatePayment`).
  - `Enums/`: PHP 8.1+ backed enums (`ApplicationStatus`, `PaymentStatus`, `PaymentType`, `DocType`, `AssessmentType`, `CouncilDecision`).
  - `Http/Controllers/Api/V1/`: Thin API controllers.
  - `Http/Controllers/Web/`: Thin Blade web controllers.
  - `Http/Requests/`: Specialized Form Requests for every endpoint.
  - `Http/Resources/`: Standard API resources with `{ data, meta, errors }` envelope.
  - `Models/`: Eloquent models with UUID primary keys (`HasUuids`).
  - `Services/`: Domain services (`NumberSequenceService`, `QrTokenService`, `OtpService`, `DocumentService`, `ApplicationStateMachine`).
  - `Payments/`: Gateway contract (`PaymentGateway`), `MockGateway`, and `GatewayManager`.
  - `Events/`, `Listeners/`, `Notifications/`: Domain event pipeline with `ShouldDispatchAfterCommit`.

---

## 2. Identity & Coexistence with Legacy Tables
- **Context**: The existing application uses a traditional `users` table with `bigint unsigned` primary key (`id`) referenced by legacy modules (attendance, routines, exams). The new brief specifies UUID identity.
- **Decision**:
  - Add a `uuid CHAR(36) UNIQUE` column to `users` with an automatic UUID generation trigger/listener.
  - Admissions domain models (`StudentProfile`, `GuardianProfile`, `Application`) link to `users.uuid` or store both for seamless interoperability.
  - New users created during the admission flow (Applicant, Student, Guardian) are assigned a UUID and appropriate role from day one.
  - This ensures 100% backward compatibility with existing systems while adhering to the UUID architecture of the new module.

---

## 3. Primary Key & Identifier Conventions
- **Decision**:
  - All new Admissions tables (`admission_programs`, `applications`, `application_guardians`, `application_documents`, `admission_assessments`, `admission_decisions`, `admission_payments`, etc.) use `CHAR(36)` UUID primary keys (`id`).
  - Application numbers format: `APP-{YYYY}-{6-digit seq}` (e.g., `APP-2026-000123`).
  - Permanent Student ID format: `MCC{YY}{S|C}{6-digit seq}` (e.g., `MCC26S000123` for School, `MCC26C000123` for College).
  - Sequence generation uses atomic `lockForUpdate` on `number_sequences`.

---

## 4. State Machine & Status Transitions
- **Decision**:
  - Statuses: `DRAFT`, `PENDING_APP_FEE`, `SUBMITTED`, `UNDER_REVIEW`, `EXAM_SCHEDULED`, `ASSESSED`, `APPROVED`, `WAITLISTED`, `REJECTED`, `ADMISSION_FEE_PAID`, `ENROLLED`, `WITHDRAWN`, `EXPIRED`.
  - State machine is encapsulated in `ApplicationStateMachine`.
  - Any transition not explicitly permitted in the transition matrix throws `IllegalStateTransitionException` (mapped to HTTP 422).
  - Every transition writes to `application_status_history` and `audit_logs` inside a `DB::transaction`.

---

## 5. File Storage & Document Pipeline
- **Decision**:
  - The database stores metadata, object keys, mime types, checksums, and verification flags only. Binary blobs are never stored in SQL.
  - S3-compatible driver (`league/flysystem-aws-s3-v3` / MinIO / local public storage).
  - Upload flow: Presign PUT request -> Client uploads -> Client confirms -> Server verifies object (HEAD request, mime sniffing, checksum) -> Marks `UPLOADED`.
  - File viewing uses short-lived signed URLs (10-minute expiry).

---

## 6. Payment Gateway Abstraction
- **Decision**:
  - Polymorphic `PaymentGateway` interface:
    ```php
    interface PaymentGateway {
        public function initiate(AdmissionPayment $payment): GatewayInitiateResponse;
        public function verify(Request $request): PaymentVerificationResult;
    }
    ```
  - `MockGateway`: Provides interactive sandbox simulation (Instant Success, Failed, Cancelled) for automated testing and development without live credentials.
  - `SSLCommerzGateway`: Pluggable implementation for Bangladesh payment rails.
  - `admission_payments` enforces row lock (`lockForUpdate`) on callback confirmation to guarantee idempotency. Duplicate callbacks return HTTP 200 without double-crediting.

---

## 7. Web UI Architecture
- **Decision**:
  - Public Applicant Portal (`/admissions`, `/admissions/apply/{program}`, `/admissions/track`) and Admin/Council Portal (`/admin/admissions`) are built with Laravel Blade, Tailwind CSS, and Alpine.js.
  - All web controllers invoke the same Action classes as the API (`Api-First` architecture).
  - Multi-step wizard features client-side autosave (`localStorage`) and server-side draft updates per step.
  - Double-submit prevention and loading/error/empty/offline states are implemented with Alpine.js.

---

## 8. Role-Based Access Control (RBAC) & Separation of Duties
- **Decision**:
  - Roles: `Applicant`, `Guardian`, `Student`, `AdmissionOfficer`, `AdmissionCouncil`, `Principal`, `Admin`, `SuperAdmin`.
  - Separation of duties: The council user who records a decision (`decided_by`) is strictly prohibited from executing the final enrollment action (`enrolled_by`) for the same application.
  - Authorization policies (`ApplicationPolicy`, `DocumentPolicy`, `EnrollmentPolicy`) enforce ownership and permission gates.


---

## 9. Revision: decisions made while completing the build

These extend or replace earlier sections where they conflict.

- **Applicant identity (replaces section 8's open applicant access).** Applicants prove their mobile number with a one-time code (10 minutes, 5 attempts, 5 codes per 15 minutes). The verified code issues a Sanctum token with the `applicant` ability. Tracking uses the same code, with a generic error for any mismatch.
- **Staff identity.** Staff sign in with email and password and receive a 12-hour token with the `staff` ability. Every staff route also checks a Spatie permission. Applicant tokens cannot reach staff routes.
- **Roles and permissions.** Five admissions roles and ten permissions (`AdmissionsRbacSeeder`). Separation of duties remains a service-level rule that applies even to users with the enrolment permission.
- **Ownership.** Applicants can read and change only their own applications. Another person's application returns 404, not 403, so numbers cannot be probed.
- **Mock gateway disabled by default.** It is on only in `local` and `testing` (`ADMISSIONS_MOCK_PAYMENTS`). Without it a `mock` request is refused, so nothing can be paid for free on a live server.
- **Payment callbacks.** Checks run in this order: gateway verification, log (kept even when rejected), signature, amount and currency, then a locked, idempotent status change. A second successful payment for the same fee is refused.
- **Pass (QR).** Issued on approval and stored on the application, not in the history JSON. Shown only to the owner and staff, and only while approved. Public scans reveal validity only and change nothing. Enrolment marks it used.
- **Capacity.** An approval for a full programme becomes a waitlist unless a principal overrides with a written reason (`admission.override_capacity`).
- **Documents.** Stored on the private `local` disk under `admissions/{application}/`. The file's content is checked for type. Required types must be present at submission and verified before review. Links expire after 10 minutes.
- **Eligibility.** Checked at submission using whole years on the day of submission (`min_age`, `max_age`), SSC GPA (`min_ssc_gpa`) and declared subjects.
- **Expiry.** `admissions:expire-applications` runs daily and moves applications past their programme deadline to `EXPIRED`.
- **Enrolment.** Idempotent. Guardians are matched by phone and guardian role, so a child whose number equals the father's is never linked to themselves. New accounts get a random, unknown password; the set-password link is not built yet.
- **Audit.** Every sensitive action writes an `audit_logs` row through `AuditService`, including CMS changes (module `cms`).
- **CMS.** Notices, gallery, governing body, teachers, staff and editable pages. Page bodies are sanitised on save and again on display. Uploads go to the public disk. A read-only JSON API is provided for the mobile app.
- **Deviation: Vite.** The brief asks for Vite builds. The admissions and CMS pages still load Tailwind and Alpine from a CDN. Moving them to Vite is planned work.
- **Deviation: Docker and MinIO.** Not set up, as agreed. Storage is local and private.
- **Deviation: SMS and email.** Not connected. Development shows the OTP on screen; production will not deliver codes until an SMS provider is added.
