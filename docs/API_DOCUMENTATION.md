> **Admissions endpoints** are documented in [ADMISSIONS_API.md](ADMISSIONS_API.md). The public `/api/admission/submit` and `/api/media/upload` endpoints below are deprecated (no OTP, generated passwords). They remain only for the existing app.

# Maestro Crown College: Mobile App API

API reference for developers building the Maestro Crown College mobile app.

To try these endpoints in Postman, see [POSTMAN_GUIDE.md](POSTMAN_GUIDE.md).

Before you build on an endpoint, check [section 11, Known issues](#11-known-issues). Logout and profile photo upload don't work correctly yet.

---

## 1. Overview

### Base URL

| Environment | Base URL |
| :--- | :--- |
| Local, same machine | `http://127.0.0.1:8000/api` |
| Android emulator | `http://10.0.2.2:8000/api` |
| Physical device on the same network | `http://<computer-LAN-IP>:8000/api` |
| Production | To be confirmed by the backend team |

To reach the local server from an emulator or device, start it with `php artisan serve --host=0.0.0.0`.

### Headers

| Header | Value | When |
| :--- | :--- | :--- |
| `Accept` | `application/json` | Every request |
| `Content-Type` | `application/json` | Requests with a JSON body |
| `Authorization` | `Bearer <token>` | Endpoints marked **Bearer token** |

Always send `Accept: application/json`. Without it, a missing or invalid token returns a `302` redirect to the web login page instead of a `401`.

For file uploads, use `multipart/form-data` and let your HTTP client set the `Content-Type` header, because it has to include the boundary.

### Response conventions

The API doesn't use status codes consistently, so follow these rules in the client:

- **Treat any `2xx` as success.** Many successful requests return `201`, including read requests such as `/user_details`.
- **`400` often means "no data".** Student data endpoints return `400` with a message such as `"No routine found!"` when the list is empty. Show an empty state for these, not an error.
- **`/marks` is the exception.** It returns `201` with empty arrays when there are no marks.
- **Timestamps are Unix seconds**, for example `birthday` and attendance `timestamp`.

### Common errors

| Status | Meaning | Body |
| :--- | :--- | :--- |
| `400` | No data found, or the request was rejected | `{"message": "No routine found!"}` |
| `401` | Missing or invalid token, or wrong login details | `{"message": "Unauthenticated."}` or `{"message": "Invalid credentials!"}` |
| `404` | Record not found | `{"status": "error", "message": "..."}` |
| `422` | Validation failed | `{"message": "...", "errors": {"field": ["..."]}}` |
| `429` | Rate limit exceeded | 60 requests per minute, per user or IP address |
| `500` | Server error | `{"message": "..."}` |

`/admission/submit` returns its `422` as `{"status": "error", "errors": {...}}`, with no `message`.

### Who can call what

| Endpoints | Student with enrollment | New applicant | Teacher | Guardian |
| :--- | :--- | :--- | :--- | :--- |
| Login, `/user`, `/account_delete` | Yes | Yes | Yes | Yes |
| Profile, academics, library and fees (sections 4 to 7) | Yes | No (`500`) | No (`500`) | No (`500`) |
| `/my_application` | `404` unless they applied | Yes | `404` | `404` |

The endpoints in sections 4 to 7 read the logged-in user's class enrollment, and only enrolled students have one. There are no teacher or guardian endpoints yet, so those roles can log in but have little to call.

---

## 2. Test credentials

These accounts are for local development only. The password for all of them is `12345678`.

| Role | `role` value | `login_id` | Email login (`/api/login`) |
| :--- | :--- | :--- | :--- |
| Student | `student` | `STD-1001` | `student@gmail.com` |
| Teacher | `teacher` | `TCH-1001` | Not supported |
| Guardian | `guardian` | `01712345678` | Not supported |

`/api/app_login` accepts only the `login_id` shown for each role. A teacher's phone number or a student's email returns `401`.

---

## 3. Authentication

### 3.1 App login

Use this login for the mobile app.

- **Request**: `POST /app_login`
- **Auth**: Public

| Field | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `role` | string | Yes | `student`, `teacher` or `guardian` |
| `login_id` | string | Yes | Student ID, teacher ID or guardian phone number, depending on `role` |
| `password` | string | Yes | |

```json
{
  "role": "student",
  "login_id": "STD-1001",
  "password": "12345678"
}
```

**Success: `200`**

```json
{
  "status": "success",
  "message": "Login successful",
  "user": {
    "id": 4,
    "student_id": "STD-1001",
    "teacher_id": null,
    "name": "Student User",
    "email": "student@gmail.com",
    "phone_number": "01812345678",
    "user_type": "regular",
    "role_id": 7,
    "parent_id": null,
    "school_id": 1,
    "code": "2026-6895-7104",
    "user_information": "{\"gender\":\"Male\",\"blood_group\":\"a+\",\"birthday\":1777358439,\"phone\":\"01616122333\",\"address\":\"Badda, DHAKA\",\"photo\":\"user.png\"}",
    "status": 1
  },
  "token": "20|BeMEznZnFNHlmwzc6NOxpUuDGES7wRQfCRWflWKt5a124fa0",
  "role": "student"
}
```

The `user` object has more fields than shown. `user_information` is a JSON string, so decode it separately. It is `null` for new applicants.

**Errors**

| Status | Body | Cause |
| :--- | :--- | :--- |
| `400` | `{"message": "Invalid role selected."}` | Unknown `role` |
| `401` | `{"message": "Invalid credentials!"}` | Wrong details, or the account is deactivated |
| `422` | Validation errors | Missing field |

### 3.2 Email login (legacy)

Students only.

- **Request**: `POST /login`
- **Auth**: Public

```json
{
  "email": "student@gmail.com",
  "password": "12345678"
}
```

**Success: `201`**, with `message`, `user` and `token`.

**Errors**: `401` for a wrong password or unknown email. `400` with `"User not found!"` for an account that isn't a student.

### 3.3 Get current user

- **Request**: `GET /user`
- **Auth**: Bearer token

**Success: `200`**, with the same object as `user` in the login response.

### 3.4 Logout

> **Not working yet.** This currently returns `500` and the token stays valid. See [section 11](#11-known-issues). Until it's fixed, discard the token on the device when the user logs out.

- **Request**: `POST /logout`
- **Auth**: Bearer token

**Intended response: `201`**

```json
{
  "message": "Logged out successfully."
}
```

### 3.5 Deactivate account

Sets the account's status to inactive, after which login returns `401`.

- **Request**: `POST /account_delete`
- **Auth**: Bearer token

**Success: `201`**

```json
{
  "message": "Account removed"
}
```

---

## 4. Student profile

### 4.1 Get profile

- **Request**: `POST /user_details`
- **Auth**: Bearer token

**Success: `201`**

```json
{
  "id": 1,
  "user_id": 4,
  "class_id": 1,
  "section_id": 1,
  "school_id": 1,
  "department_id": null,
  "session_id": 2,
  "code": "2026-6895-7104",
  "parent_name": "",
  "name": "Student User",
  "email": "student@gmail.com",
  "role": "student",
  "address": "Badda, DHAKA",
  "phone": "01616122333",
  "birthday": 1777358439,
  "gender": "Male",
  "blood_group": "a+",
  "student_info": "[]",
  "photo": "http://127.0.0.1:8000/assets/uploads/user-images/thumbnail.png",
  "school_name": "Maestro Crown School & College",
  "running_session": 2,
  "class_name": "HSC 1ST",
  "section_name": "A"
}
```

`phone` here comes from the profile and can differ from `user.phone_number` in the login response. `phone_number` is the number used for guardian login.

### 4.2 Update profile

- **Request**: `POST /profile_update`
- **Auth**: Bearer token

Send all five fields, even if only one changed. The server doesn't validate them: a missing `name` returns `500`, and a missing `gender`, `phone` or `address` is saved as `null`, erasing the old value.

| Field | Type | Required |
| :--- | :--- | :--- |
| `name` | string | Yes |
| `email` | string | Yes (send the current value) |
| `gender` | string | Yes |
| `phone` | string | Yes |
| `address` | string | Yes |

```json
{
  "name": "Student User",
  "email": "student@gmail.com",
  "gender": "Male",
  "phone": "01812345678",
  "address": "Savar, Dhaka"
}
```

**Success: `201`**

```json
{
  "student_data": { "...": "same object as /user_details" },
  "message": "Profile Update successfully"
}
```

### 4.3 Change profile photo

> **Not working yet.** This returns `201`, but the new photo isn't saved. See [section 11](#11-known-issues).

- **Request**: `POST /change_profile_photo`
- **Auth**: Bearer token
- **Body**: `multipart/form-data`

| Field | Type | Required |
| :--- | :--- | :--- |
| `user_image` | file | Yes |

**Success: `201`**, with `student_data` and `"message": "Profile Picture Updated"`.

**Error: `400`**, `"First select your image"`, when `user_image` is missing or the field has a different name.

---

## 5. Academics

All endpoints in this section use the Bearer token. Sections 5, 6 and 7 identify the student from the token, so don't send a student ID.

Except for `/subjects`, each response below also includes `class_id`, `class_name`, `section_id`, `section_name`, `school_id` and `session_id`. `/subjects` omits the two section fields. They are left out of the examples for brevity.

### 5.1 Class routine

- **Request**: `POST /routine`
- **Body**: None

**Success: `201`**

```json
{
  "routines": [
    {
      "id": 1,
      "subject_id": 2,
      "subject_name": "Physics",
      "starting_time": "09:00",
      "ending_time": "10:00",
      "room_id": 101,
      "room_name": "Room 101",
      "day": "Sunday",
      "teacher_id": 6,
      "teacher_name": "Teacher User",
      "teacher_image": "http://127.0.0.1:8000/assets/uploads/user-images/teacher.png",
      "teacher_designation": "Lecturer"
    }
  ]
}
```

**No data: `400`**, `"No routine found!"`

### 5.2 Attendance

- **Request**: `POST /attendance`

| Field | Type | Required | Example |
| :--- | :--- | :--- | :--- |
| `month` | string | Yes | `"Nov"` |
| `year` | string | Yes | `"2026"` |

```json
{
  "month": "Nov",
  "year": "2026"
}
```

**Success: `201`**. The response key really is spelled `attedances`.

```json
{
  "attedances": [
    {
      "id": 1,
      "status": 1,
      "timestamp": "1793740800"
    }
  ]
}
```

`status`: `1` is present, `0` is absent. `timestamp` is a string.

**No data: `400`**, `"Attendance report not found!"`. A missing `month` or `year` also returns this, not a validation error.

### 5.3 Subjects

- **Request**: `POST /subjects`
- **Body**: None

**Success: `201`**

```json
{
  "subjects": [
    { "id": 2, "name": "Physics" }
  ]
}
```

**No data: `400`**, `"No subject found!"`

### 5.4 Syllabus list

- **Request**: `POST /syllabus_list`

| Field | Type | Required |
| :--- | :--- | :--- |
| `subject_id` | integer | No |

**Success: `201`**

```json
{
  "syllabuses": [
    {
      "id": 1,
      "title": "Physics Syllabus",
      "subject_id": 2,
      "subject_name": "Physics",
      "file": "physics.pdf",
      "file_url": "http://127.0.0.1:8000/assets/uploads/syllabus/physics.pdf"
    }
  ],
  "subjects": [
    { "subject_id": 2, "subject_name": "Physics" }
  ]
}
```

**No data: `400`**, `"No subject found!"`

### 5.5 Teachers and departments

- **Request**: `POST /teacher_list`

| Field | Type | Required |
| :--- | :--- | :--- |
| `department_id` | integer | No |

**Success: `201`**

```json
{
  "teachers": [
    {
      "id": 6,
      "name": "Teacher User",
      "department_id": 1,
      "school_id": 1,
      "department_name": "Science",
      "designation": "Lecturer"
    }
  ],
  "departments": [
    { "department_id": 1, "department_name": "Science" }
  ]
}
```

**No data: `400`**, `"No teacher found!"`. This can also return `500`. See [section 11](#11-known-issues).

### 5.6 Exam schedule

- **Request**: `POST /exam_list`

| Field | Type | Required |
| :--- | :--- | :--- |
| `subject_id` | integer | No |

**Success: `201`**

```json
{
  "exams": [
    {
      "id": 1,
      "name": "Midterm",
      "exam_type": "written",
      "subject_id": 2,
      "subject_name": "Physics",
      "starting_time": "1793740800",
      "ending_time": "1793748000",
      "total_marks": 100,
      "status": "Upcoming"
    }
  ]
}
```

`status` is `Upcoming` or `Expired`.

**No data: `400`**, `"No exam found!"`

### 5.7 Marks

- **Request**: `POST /marks`

| Field | Type | Required |
| :--- | :--- | :--- |
| `exam_category_id` | integer | No |

**Success: `201`**, also returned when there are no marks yet (`exam_marks` is then `[]`).

```json
{
  "exam_marks": [
    {
      "exam_id": 1,
      "exam_category_id": 1,
      "exam_category_name": "Midterm",
      "subjects": [
        { "subject_id": 2, "subject_name": "Physics", "marks": 85 }
      ],
      "comment": "Good"
    }
  ],
  "exam_categories": [
    { "exam_category_id": 1, "exam_category_name": "Midterm" }
  ]
}
```

---

## 6. Library

### 6.1 Search books

- **Request**: `POST /book_list`
- **Auth**: Bearer token

| Field | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `search_key` | string | No | Partial match on the book name |

**Success: `201`**

```json
{
  "books": [
    {
      "id": 1,
      "name": "Physics Part 1",
      "author": "A. Author",
      "copies": 10,
      "available_copies": 7
    }
  ]
}
```

**No data: `400`**, `"No book found!"`

### 6.2 Issued books

- **Request**: `POST /book_issue_list`
- **Auth**: Bearer token

| Field | Type | Required | Format |
| :--- | :--- | :--- | :--- |
| `date_range` | string | No | `YYYY/MM/DD - YYYY/MM/DD` |

Use slashes in the dates. The server splits the range on `-`, so a date such as `2026-01-01` won't work.

**Success: `201`**

```json
{
  "issued_books": [
    {
      "id": 1,
      "book_id": 1,
      "book_name": "Physics Part 1",
      "author": "A. Author",
      "issue_date": "1793740800",
      "status": "pending"
    }
  ]
}
```

`status` is `pending` (not yet returned) or `returned`.

**No data: `400`**, `"No issued book found!"`

---

## 7. Fees

### 7.1 Fee invoices

- **Request**: `POST /fee_list`
- **Auth**: Bearer token

| Field | Type | Required | Format |
| :--- | :--- | :--- | :--- |
| `date_range` | string | No | `YYYY/MM/DD - YYYY/MM/DD`, with slashes as in [6.2](#62-issued-books) |

**Success: `201`**

```json
{
  "invoices": [
    {
      "id": 1,
      "title": "Tuition Fee",
      "total_amount": 5000,
      "payment_method": "cash",
      "paid_amount": 5000,
      "status": "paid",
      "timestamp": 1793740800
    }
  ]
}
```

`status` is `paid` or `unpaid`.

**No data: `400`**, `"Invoices not found!"`

---

## 8. Online admission

The full flow:

1. Upload the applicant's photo (optional) and keep `data.id`.
2. Submit the form and keep `application_no`, `student_id` and `password`.
3. Log in with `role: student` using that `student_id` and `password`.
4. Start the payment ([section 9](#9-admission-fee-payment-sslcommerz)).
5. Check progress with `/my_application`.

### 8.1 Upload photo

- **Request**: `POST /media/upload`
- **Auth**: Public
- **Body**: `multipart/form-data`

| Field | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `file` | file | Yes | Up to 10 MB. The server doesn't restrict the file type, so limit the app to images. |

**Success: `201`**

```json
{
  "status": "success",
  "message": "File uploaded successfully",
  "data": {
    "id": 5,
    "file_path": "media/abc123xyz.png",
    "url": "http://127.0.0.1:8000/storage/media/abc123xyz.png"
  }
}
```

Pass `data.id` as `photo_id` when submitting the form.

### 8.2 Submit application

- **Request**: `POST /admission/submit`
- **Auth**: Public

| Field | Type | Required | Rules |
| :--- | :--- | :--- | :--- |
| `department` | string | Yes | `Science`, `Commerce` or `Arts` |
| `sscBoard` | string | Yes | |
| `sscRoll` | string | Yes | Up to 6 characters |
| `registrationNumber` | string | Yes | Up to 10 characters |
| `passingYear` | string | Yes | Exactly 4 characters |
| `gpa` | number | Yes | 0.00 to 5.00 |
| `name` | string | Yes | Up to 30 characters |
| `gender` | string | Yes | `Male` or `Female` |
| `mobile` | string | Yes | Exactly 11 characters |
| `email` | string | Yes | Valid email |
| `fatherName` | string | Yes | Up to 30 characters |
| `motherName` | string | Yes | Up to 30 characters |
| `guardianName` | string | Yes | Up to 30 characters |
| `relationWithGuardian` | string | Yes | Up to 30 characters |
| `guardianMobile` | string | Yes | Exactly 11 characters |
| `presentAddress` | string | Yes | |
| `photo_id` | integer | No | `data.id` from [8.1](#81-upload-photo) |

```json
{
  "department": "Science",
  "sscBoard": "Dhaka",
  "sscRoll": "123456",
  "registrationNumber": "1234567890",
  "passingYear": "2026",
  "gpa": 5.00,
  "name": "Rahim Ahmed",
  "gender": "Male",
  "mobile": "01711223344",
  "email": "rahim@example.com",
  "fatherName": "Karim Ahmed",
  "motherName": "Fatema Begum",
  "guardianName": "Karim Ahmed",
  "relationWithGuardian": "Father",
  "guardianMobile": "01711223344",
  "presentAddress": "Savar, Dhaka",
  "photo_id": 5
}
```

**Success: `201`**

```json
{
  "status": "success",
  "message": "Admission application submitted successfully.",
  "data": {
    "application_no": "MCC-2026-000017",
    "id": 17,
    "student_id": "20261762335123",
    "password": "12345678",
    "pdf_url": "http://127.0.0.1:8000/storage/admissions/pdfs/MCC-2026-000017.pdf"
  }
}
```

- `password` is an 8-digit generated value returned **only in this response**. Show it to the applicant and have them save it.
- `pdf_url` is `null` if the PDF couldn't be generated. The application is still saved, so handle this case.
- Every submission creates a new application and new accounts. Nothing blocks duplicates, so prevent double submission in the app.
- The temporary guardian account gets a random password that isn't returned, so that guardian can't log in yet.

### 8.3 Download admission slip

- **Request**: `GET /admission/{application_no}/pdf`, for example `/admission/MCC-2026-000017/pdf`
- **Auth**: Public

**Success: `200`**, a file download named `Admission_Slip_MCC-2026-000017.pdf`.

**Errors**: `404` if the application doesn't exist. `500` with `"Failed to generate PDF."` if the slip can't be generated.

### 8.4 My application

- **Request**: `POST /my_application`
- **Auth**: Bearer token, from the applicant's own login

**Success: `200`**

```json
{
  "status": "success",
  "data": {
    "application_no": "MCC-2026-000017",
    "name": "Rahim Ahmed",
    "father_name": "Karim Ahmed",
    "department": "Science",
    "student_id": "20261762335123",
    "photo_url": "http://127.0.0.1:8000/storage/media/abc123xyz.png",
    "pdf_url": "http://127.0.0.1:8000/storage/admissions/pdfs/MCC-2026-000017.pdf",
    "status": "pending"
  }
}
```

`status` is one of `pending`, `under_review`, `approved`, `rejected` or `waitlisted`. It changes from `pending` to `under_review` after a successful payment.

**Error: `404`**, `"No application found."`, if this account has no application.

---

## 9. Admission fee payment (SSLCommerz)

### 9.1 Start payment

The fee is fixed at 5000 BDT.

- **Request**: `POST /payment/initiate`
- **Auth**: Bearer token

| Field | Type | Required |
| :--- | :--- | :--- |
| `application_no` | string | Yes |

```json
{
  "application_no": "MCC-2026-000017"
}
```

**Success: `200`**

```json
{
  "status": "success",
  "gateway_url": "https://sandbox.sslcommerz.com/EasyCheckOut/testcde123456"
}
```

Open `gateway_url` in a WebView or in-app browser.

**Errors**

| Status | Message | Cause |
| :--- | :--- | :--- |
| `400` | `Application is already approved.` | No further payment is accepted |
| `404` | `Admission application not found.` | Wrong `application_no` |
| `422` | Validation errors | `application_no` missing |
| `500` | `Failed to initiate payment gateway.` | SSLCommerz rejected or failed the request |

Disable the pay button once an application is paid. The server only blocks payment for `approved` applications, so it would start a second payment for one that's `under_review`.

### 9.2 Detect the result in the WebView

After payment, the WebView ends up on one of these URLs:

| URL | Meaning |
| :--- | :--- |
| `/api/payment/result?status=success` | Payment verified |
| `/api/payment/result?status=fail` | Payment failed or couldn't be verified |
| `/api/payment/result?status=cancel` | User cancelled |

1. Watch the WebView's URL for `/api/payment/result`.
2. Read the `status` query parameter and close the WebView. The page shows the same text, "Payment Processed. You can close this window.", for every outcome, so don't rely on it.
3. Call `/my_application` to confirm the new status.

`/payment/success`, `/payment/fail` and `/payment/cancel` are callback URLs for SSLCommerz. The app shouldn't call them directly.

---

## 10. Files and images

Links under `/storage/...`, such as uploaded photos and admission PDFs, only work once the server has run `php artisan storage:link`. Without it they return `404`.

---

## 11. Known issues

Current as of 5 November 2026. These are backend issues. Please check with the backend team before adding workarounds that will outlast the fix.

| Endpoint | What happens | What to do for now |
| :--- | :--- | :--- |
| `POST /logout` | Returns `500`, and the token stays valid | Delete the token on the device and treat the user as logged out |
| `POST /change_profile_photo` | Returns `201`, but the photo isn't saved | Don't offer photo changes yet |
| `POST /teacher_list` | Returns `500` if a listed teacher has no department | Handle the error. Sending a `department_id` avoids it, because only teachers in that department are listed |
| Sections 4 to 7 | Return `500` for accounts with no enrollment: teachers, guardians and new applicants | Only call these for enrolled students |
| `POST /profile_update` | Missing fields return `500` or erase stored values | Always send all five fields |
| `/admission/submit` and `/admission/{no}/pdf` | PDF generation fails on servers without the `imagick` PHP extension: `pdf_url` is `null`, and the download returns `500` | Handle both cases |
| `POST /routine` | `room_name` may show the wrong room: the code appears to always return the first room in the table | Use it with caution until confirmed |
