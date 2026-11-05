# Implementation Plan: MCC–Hello Future Admission & Onboarding Engine

This document outlines the step-by-step engineering plan to build, test, and verify the **Digital Admission & Onboarding Module** for **Maestro Crown College (MCC)**.

---

## Phase 1: Infrastructure & Package Setup

1. **Composer Dependencies**:
   - `spatie/laravel-permission` (for granular roles & permissions).
   - Verify `ramsey/uuid` and `laravel/sanctum` configurations.
2. **Environment & Local Services**:
   - Create or update `.env.example` with Redis, MinIO/S3, and Mock Gateway configuration.
   - Configure filesystem disks for private documents and public slips.
3. **Module Directory Scaffolding**:
   ```
   app/Modules/Admissions/
     ├── Actions/
     ├── Enums/
     ├── Events/
     ├── Http/
     │   ├── Controllers/
     │   │   ├── Api/V1/
     │   │   └── Web/
     │   ├── Requests/
     │   └── Resources/
     ├── Listeners/
     ├── Models/
     ├── Notifications/
     ├── Payments/
     ├── Policies/
     ├── Rules/
     └── Services/
   ```

---

## Phase 2: Database Migrations & Eloquent Models

### 2.1 Migrations to Create
All tables use `id CHAR(36) PRIMARY KEY` (UUID) with appropriate indexes and foreign key constraints:
1. `academic_years`
2. `admission_classes` / `groups` / `admission_sections`
3. `admission_programs`
4. `applications`
5. `application_guardians`
6. `application_documents`
7. `admission_assessments`
8. `admission_decisions`
9. `application_status_history`
10. `admission_payments`
11. `payment_gateway_logs`
12. `otp_codes`
13. `number_sequences`
14. `audit_logs`
15. `student_profiles`, `guardian_profiles`, `student_guardians`, `student_enrollments`

### 2.2 Eloquent Models & Enums
- Enums:
  - `ApplicationStatus`: `DRAFT`, `PENDING_APP_FEE`, `SUBMITTED`, `UNDER_REVIEW`, `EXAM_SCHEDULED`, `ASSESSED`, `APPROVED`, `WAITLISTED`, `REJECTED`, `ADMISSION_FEE_PAID`, `ENROLLED`, `WITHDRAWN`, `EXPIRED`.
  - `PaymentStatus`: `INITIATED`, `PENDING`, `SUCCESS`, `FAILED`, `CANCELLED`, `REFUNDED`.
  - `PaymentType`: `APPLICATION_FEE`, `ADMISSION_FEE`.
  - `DocType`: `BIRTH_CERT`, `PREVIOUS_TRANSCRIPT`, `PHOTO`, `NID`, `OTHER`.
  - `AssessmentType`: `WRITTEN`, `VIVA`, `INTERVIEW`.
  - `CouncilDecision`: `APPROVED`, `WAITLISTED`, `REJECTED`.
- Models with `HasUuids`, casts (`encrypted` for NID, `json` for metadata), relationships, and query scopes.

---

## Phase 3: Core Domain Services & Actions

### 3.1 Domain Services
1. **`NumberSequenceService`**:
   - Thread-safe, atomic sequence generator using `lockForUpdate`.
   - Generates `APP-{YYYY}-{seq}` and `MCC{YY}{S|C}{seq}`.
2. **`ApplicationStateMachine`**:
   - Enforces the 13-state transition matrix.
   - Throws `IllegalStateTransitionException` on invalid transitions.
   - Automatically writes to `application_status_history` and `audit_logs`.
3. **`QrTokenService`**:
   - Generates and verifies HMAC-signed QR admission tokens with expiration and single-use validation.
4. **`OtpService`**:
   - Generates, hashes, throttles, and verifies 6-digit OTP codes for applicant mobile tracking and login.
5. **`DocumentService`**:
   - Presigned upload URLs, HEAD object verification, checksum SHA256 computation, and signed download URLs.

### 3.2 Action Classes (Single Responsibility)
- `CreateDraftApplicationAction`
- `UpdateDraftApplicationAction`
- `SubmitApplicationAction`
- `VerifyDocumentAction`
- `ScheduleAssessmentAction`
- `RecordAssessmentScoreAction`
- `RecordCouncilDecisionAction`
- `InitiateAdmissionPaymentAction`
- `ProcessPaymentCallbackAction`
- `EnrollStudentAction` (atomic transaction provisioning permanent student ID, student/guardian profile, and section seat allocation)
- `WithdrawApplicationAction`

---

## Phase 4: Payment Gateway Engine

1. **`PaymentGateway` Interface**: Defines `initiate()` and `verify()`.
2. **`MockGateway`**: Sandbox gateway offering interactive Instant Success, Failure, and Cancellation screens for development and tests.
3. **`SSLCommerzGateway`**: Live/Sandbox integration for Bangladesh mobile banking/cards.
4. **`GatewayManager`**: Resolves gateway driver based on configuration.

---

## Phase 5: REST API (`/api/v1/admissions`)

Implement controllers, FormRequests, and Resources:
1. **Public & Applicant Endpoints**:
   - `GET /programs` — List active programs, eligibility rules, seats, deadlines.
   - `POST /applicants/otp/request` & `/applicants/otp/verify` — OTP mobile auth.
   - `POST /applications` — Create DRAFT.
   - `PATCH /applications/{id}` — Autosave wizard steps.
   - `POST /applications/{id}/submit` — Validate completeness, advance to `PENDING_APP_FEE`.
   - `GET /applications/{id}` — Fetch application status, timeline, QR token.
   - `POST /track` — Secure lookup by `application_no` + OTP.
   - `POST /applications/{id}/withdraw` — Withdraw.
2. **Document Endpoints**:
   - `POST /documents/presign` — Generate upload URL.
   - `POST /documents/{id}/confirm` — Verify and mark uploaded.
   - `GET /applications/{id}/documents` — List signed URLs.
   - `POST /documents/{id}/verify` — Officer approval/rejection.
3. **Payment Endpoints**:
   - `POST /payments/initiate` — Initiate fee checkout.
   - `POST /payments/callback/{provider}` — Server-to-server callback.
   - `GET /payments/{ref}` — Status lookup.
4. **Council & Staff Endpoints**:
   - `GET /council/applications` — Filterable pipeline list.
   - `GET /council/dashboard` — Pipeline metrics.
   - `POST /interviews` & `/interviews/{id}/score` — Assessment management.
   - `POST /council/decision` — Council review action.
   - `POST /qr/verify` — QR scanner validation.
   - `POST /enroll` — One-click enrollment.

---

## Phase 6: Web UI (Blade + Tailwind CSS + Alpine.js)

### A. Public Applicant Portal (`/admissions`)
1. **Programs & Eligibility Showcase** (`/admissions`):
   - School (Playgroup–Class 10) and College (Class XI Science, Business Studies, Humanities).
   - Seats remaining, fee tables, deadlines, "Apply" CTA.
2. **5-Step Multi-Step Application Wizard** (`/admissions/apply/{program}`):
   - Step 1: Basic & Contact Info + OTP Mobile Verification.
   - Step 2: Guardian Details & Relationships.
   - Step 3: Prior Academic Record & Eligibility Check.
   - Step 4: Secure Document Uploaders (Drag & drop, preview, progress).
   - Step 5: Review, Consent & Application Fee Checkout.
3. **Application Tracker** (`/admissions/track`):
   - Lookup via `application_no` + OTP.
   - Visual timeline, interview details, Admission QR badge, "Pay Admission Fee" button, and onboarding checklist upon enrollment.

### B. Admission Officer & Council Portal (`/admin/admissions`)
1. **Pipeline Dashboard**: Metrics (Total, Pending Verification, Under Assessment, Approved, Waitlisted, Enrolled), searchable data table, pagination.
2. **Applicant Evaluation View**: Split-screen interface for document verification, interview scheduling, score entry, and Council Decision Bar (`Approve`, `Waitlist`, `Reject`).
3. **Enrollment & Provisioning View**: Section capacity checker, student ID assignment, and one-click enrollment trigger.

---

## Phase 7: Seed Data & Testing Verification

1. **Seeders**:
   - Current Academic Year `2026-27`.
   - Programs for School (Playgroup–Class 10) and College (Class XI Science, Business, Humanities) with realistic eligibility criteria.
   - Classes and Sections with seat capacities.
   - Test staff accounts for `AdmissionOfficer`, `AdmissionCouncil`, `Principal`, `Admin`.
2. **Automated Feature Tests**:
   - Happy path: Apply -> Pay App Fee -> Officer Review -> Interview Score -> Council Approve -> Pay Admission Fee -> Admin Enroll.
   - Negative cases: Double payment prevention, illegal state transition (422), unauthorized access (403), capacity enforcement, separation of duties.
3. **Documentation Deliverables**:
   - `docs/DECISIONS.md` (Architecture decisions recorded).
   - `docs/PLAN.md` (This implementation roadmap).
   - `docs/API.md` (Detailed API resource reference).
   - Mermaid Entity-Relationship (ER) diagram.

