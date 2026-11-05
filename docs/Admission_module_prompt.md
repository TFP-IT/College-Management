# TASK BRIEF: Build "MCC–Hello Future" Admission & Onboarding Engine (API + Web)

You are operating as a Senior Full-Stack Engineer and System Architect inside Google Antigravity. Your objective is to scaffold, implement, and verify the complete **Digital Admission & Onboarding Module** for the **MCC–Hello Future Digital Academic Ecosystem** based on institutional architecture specifications.

---

## 1. System Context & Architecture Alignment

The admission lifecycle serves both **School Section (Playgroup–Class 10)** and **College Section (Class XI–XII / HSC)**.
The core lifecycle follows this strict sequence:
1. **Public Discovery:** View program information, eligibility criteria, and fee schedules.
2. **Application Submission:** Create applicant profile, submit form data, and upload documents via object storage references.
3. **Application Fee:** Process payment through server-to-server verified payment gateway.
4. **Assessment & Council Review:** Schedule entrance test / interview, record marks, and execute Admission Council evaluation (`Approved`, `Waitlisted`, `Rejected`).
5. **Admission Fee & QR:** Issue digital admission QR code upon approval and accept final admission fee payment.
6. **Enrollment & Provisioning:** Generate permanent Student ID, link Guardian account, allocate Class/Section, provision student profile, and trigger the `StudentEnrolled` event.

---

## 2. Technical Stack & Workspace Requirements

- **Backend (API):** Node.js / NestJS (or Express + TypeScript) / Fastify.
- **Frontend (Web):** Next.js (App Router) / React with Tailwind CSS and Lucide icons.
- **Database:** PostgreSQL with Prisma or Drizzle ORM.
- **File Storage:** S3-compatible object storage service (store metadata and URLs in DB; never store binary blobs directly in the relational database).
- **Authentication & RBAC:** JWT with role-based access control (`Applicant`, `Admin`, `AdmissionCouncil`, `Guardian`, `Student`).
- **Validation:** Zod schemas shared or mirrored between web forms and API handlers.

---

## 3. Database Schema Deliverables

Create migration scripts and ORM models for:

```sql
-- 1. Core Users & Profiles
users (id UUID PK, user_type VARCHAR, name VARCHAR, mobile VARCHAR, email VARCHAR UNIQUE, password_hash VARCHAR, status VARCHAR, profile_photo VARCHAR, created_at TIMESTAMP, updated_at TIMESTAMP)
student_profiles (id UUID PK, user_id UUID FK, student_number VARCHAR UNIQUE, blood_group VARCHAR, dob DATE, gender VARCHAR, nationality VARCHAR, current_address TEXT)
guardian_profiles (id UUID PK, user_id UUID FK, relation VARCHAR, occupation VARCHAR, nid_passport VARCHAR, emergency_contact VARCHAR)
student_guardians (id UUID PK, student_id UUID FK, guardian_id UUID FK, is_primary BOOLEAN)

-- 2. Admissions Domain
admission_programs (id UUID PK, code VARCHAR, title VARCHAR, section_type VARCHAR, min_eligibility JSONB, fee_application NUMERIC, fee_admission NUMERIC, is_active BOOLEAN)
applications (
    id UUID PK,
    application_no VARCHAR UNIQUE,
    program_id UUID FK,
    applicant_name VARCHAR,
    email VARCHAR,
    mobile VARCHAR,
    dob DATE,
    gender VARCHAR,
    academic_history JSONB,
    status VARCHAR DEFAULT 'DRAFT', -- DRAFT, SUBMITTED, UNDER_REVIEW, EXAM_SCHEDULED, APPROVED, WAITLISTED, REJECTED, ENROLLED
    council_notes TEXT,
    admission_qr_token VARCHAR,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
application_documents (
    id UUID PK,
    application_id UUID FK,
    doc_type VARCHAR, -- BIRTH_CERT, PREVIOUS_TRANSCRIPT, PHOTO, NID
    file_url VARCHAR,
    file_name VARCHAR,
    mime_type VARCHAR,
    size_bytes INT,
    verified BOOLEAN DEFAULT FALSE
)
admission_assessments (
    id UUID PK,
    application_id UUID FK,
    assessment_type VARCHAR, -- WRITTEN, VIVA, INTERVIEW
    scheduled_at TIMESTAMP,
    score NUMERIC,
    max_score NUMERIC,
    evaluator_id UUID FK,
    remarks TEXT
)

-- 3. Payments & Ledgers
admission_payments (
    id UUID PK,
    application_id UUID FK,
    payment_type VARCHAR, -- APPLICATION_FEE, ADMISSION_FEE
    amount NUMERIC,
    currency VARCHAR DEFAULT 'BDT',
    provider VARCHAR,
    transaction_ref VARCHAR UNIQUE,
    payment_status VARCHAR, -- PENDING, SUCCESS, FAILED
    payment_details JSONB,
    created_at TIMESTAMP
)
```

---

## 4. API Endpoints Specification

Expose and implement the following REST endpoints with thorough request validation and error handling:

| Method | Endpoint | Description | Auth / Role |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admissions/programs` | List open admission programs with eligibility and fees | Public |
| `POST` | `/api/v1/admissions/applications` | Submit application draft or finalized form | Public / Applicant |
| `GET` | `/api/v1/admissions/applications/:id` | Get application details and review status | Applicant / Admin |
| `POST` | `/api/v1/admissions/documents` | Upload document metadata & presigned URL verification | Applicant / Admin |
| `POST` | `/api/v1/admissions/payments/initiate` | Initiate payment for application or admission fee | Applicant |
| `POST` | `/api/v1/admissions/payments/callback/:provider` | Webhook / server-to-server callback verification | Webhook Public |
| `GET` | `/api/v1/admissions/council/applications` | List applications with filters (status, score, program) | AdmissionCouncil / Admin |
| `POST` | `/api/v1/admissions/interviews` | Schedule entrance test or viva; record evaluator scores | AdmissionCouncil / Admin |
| `POST` | `/api/v1/admissions/council/decision` | Approve, waitlist, or reject an applicant | AdmissionCouncil / Admin |
| `POST` | `/api/v1/admissions/enroll` | Final enrollment trigger: generates ID, provisions User/Student/Guardian | Admin |

---

## 5. Web Frontend Deliverables

Develop clean, responsive UI views:

### A. Public Applicant Portal (`/admissions`)
1. **Programs & Eligibility Page:** Visual cards showing School and College sections, deadlines, and fee tables.
2. **Multi-Step Application Wizard:**
   - Step 1: Basic & Contact Information.
   - Step 2: Guardian Details & Relationships.
   - Step 3: Prior Academic Record & Program Selection (Science / Business Studies / Humanities).
   - Step 4: Secure Document Uploaders (Drag & drop with preview).
   - Step 5: Review & Application Fee Checkout.
3. **Application Status Tracker (`/admissions/track`):**
   - Lookup by `application_no` and verified mobile/OTP.
   - Displays real-time status tracker, entrance exam schedule, QR verification token, and onboarding checklist upon approval.

### B. Admission Officer & Council Portal (`/admin/admissions`)
1. **Application Pipeline Dashboard:** Metrics for Total Applications, Pending Verification, Under Assessment, Approved, and Enrolled.
2. **Applicant Evaluation View:** Split-screen displaying submitted data, uploaded documents, test score entry, and council decision action bar (`Approve`, `Waitlist`, `Reject`).
3. **Enrollment & Provisioning Action:**
   - Class & Section allocation dropdown.
   - One-click "Enroll & Provision Student" triggering automated student ID generation and credentials dispatch.

---

## 6. Execution Instructions for Antigravity

1. **Step 1 — Workspace Scan & Planning:** Inspect the current directory, determine the project layout (monorepo or existing frontend/backend folders), and produce an implementation plan artifact.
2. **Step 2 — Database Models & Migrations:** Generate and run the database migrations for all tables listed in Section 3. Seed initial programs (School Class 1-10, College Class XI Science/Business/Humanities).
3. **Step 3 — API Implementation:** Build controllers, services, and DTOs for the endpoints in Section 4. Implement mock or sandbox payment gateway verification.
4. **Step 4 — Web UI Construction:** Implement the multi-step applicant form, tracking page, and administrative review screens.
5. **Step 5 — Automated End-to-End Verification:**
   - Launch local services.
   - Run tests or use Antigravity's browser action to complete an end-to-end journey: submit an application -> upload documents -> pay fee -> record council approval -> finalize enrollment.