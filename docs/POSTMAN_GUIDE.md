# Testing the API in Postman

How to import the collection, log in, and call each endpoint. For full response formats, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

## 1. Start the API

Run these from the project root. PHP 8.5 and a configured `.env` are required.

```bash
php artisan migrate
php artisan db:seed --class=CreateUsersSeeder   # test accounts in section 4
php artisan storage:link                        # makes uploaded files and PDFs reachable
php artisan serve
```

The API is now at `http://127.0.0.1:8000/api`.

**Run the seeder on local databases only.** It creates accounts with a known password, and resets those accounts if they already exist.

For a fully working setup, you may also need:

- **Student enrollment:** the seeder doesn't enroll the test student in a class. Without an enrollment, most student endpoints return `500`. Create one in the admin panel, or use a database that already has one.
- **Academic data:** routines, subjects, books and similar records aren't seeded. Without them, those endpoints return `400` with a "not found" message.
- **`imagick` PHP extension:** needed to generate the admission slip PDF.
- **SSLCommerz sandbox settings:** set `SSL_STORE_ID`, `SSL_STORE_PASSWORD` and `SSL_IS_SANDBOX=true` in `.env` to test payments. Ask the backend team for the values, and don't commit them or paste them into Postman.

## 2. Import the collection

1. Open the Postman desktop app. The web version can't reach `127.0.0.1` without the Postman Desktop Agent.
2. Select **Import** and choose `docs/postman_collection.json`.
3. Open **Maestro Crown College - Mobile App API** in the sidebar.

## 3. Check the variables

Select the collection, then open its **Variables** tab. You only need to change `base_url`, and only if your server isn't at the default address. The rest fill in automatically.

| Variable | Set by | Used for |
| :--- | :--- | :--- |
| `base_url` | You. Default: `http://127.0.0.1:8000/api` | Every request |
| `token` | Any login request | Authenticated requests |
| `photo_id` | Upload Media / Photo | Submit Admission Form |
| `application_no` | Submit Admission Form | PDF download and payment |
| `student_id` | Submit Admission Form | New Applicant login |
| `student_password` | Submit Admission Form | New Applicant login |

## 4. Log in

You don't need to copy tokens. Each login request saves its token to `{{token}}`, and the other requests use it automatically.

1. Open **1. Authentication**.
2. Send the login request for the role you want to test:

| Request | Role | Login ID | Password |
| :--- | :--- | :--- | :--- |
| App Login (Student) | Student | `STD-1001` | `12345678` |
| App Login (Teacher) | Teacher | `TCH-1001` | `12345678` |
| App Login (Guardian) | Guardian | `01712345678` | `12345678` |
| Legacy Email Login (Student) | Student | `student@gmail.com` | `12345678` |

3. Check for a `200` response (`201` for the legacy login) with a `token` in the body.
4. To confirm it worked, send **Get Authenticated User**.

Only one token is stored, so **the latest login replaces the previous one**. Log in as the student before testing folders 2 to 5, because teacher and guardian tokens get a `500` there.

## 5. Required data for each request

The collection already contains working sample values. A blank cell means no fields are needed.

### Authentication

| Request | Auth | Required fields | Optional fields |
| :--- | :--- | :--- | :--- |
| `POST /app_login` | None | `role`, `login_id`, `password` | |
| `POST /login` | None | `email`, `password` | |
| `GET /user` | Token | | |
| `POST /logout` | Token | | |
| `POST /account_delete` | Token | | |

### Student data: log in as the student first

| Request | Required fields | Optional fields |
| :--- | :--- | :--- |
| `POST /user_details` | | |
| `POST /profile_update` | `name`, `email`, `gender`, `phone`, `address` | |
| `POST /change_profile_photo` | `user_image` (file) | |
| `POST /routine` | | |
| `POST /attendance` | `month` (`Nov`), `year` (`2026`) | |
| `POST /subjects` | | |
| `POST /syllabus_list` | | `subject_id` |
| `POST /teacher_list` | | `department_id` |
| `POST /exam_list` | | `subject_id` |
| `POST /marks` | | `exam_category_id` |
| `POST /book_list` | | `search_key` |
| `POST /book_issue_list` | | `date_range` |
| `POST /fee_list` | | `date_range` |

Write `date_range` with slashes: `2026/01/01 - 2026/12/31`.

The sample filters may hide real data. If a request with a filter returns "not found", try again with the body set to **none**.

### Admission and payment

| Request | Auth | Required fields | Optional fields |
| :--- | :--- | :--- | :--- |
| `POST /media/upload` | None | `file` (up to 10 MB) | |
| `POST /admission/submit` | None | All fields below | `photo_id` |
| `GET /admission/{application_no}/pdf` | None | `application_no` in the URL | |
| `POST /my_application` | Applicant's token | | |
| `POST /payment/initiate` | Token | `application_no` | |

**Fields for `/admission/submit`:**

| Field | Rule | Sample |
| :--- | :--- | :--- |
| `department` | `Science`, `Commerce` or `Arts` | `Science` |
| `sscBoard` | Text | `Dhaka` |
| `sscRoll` | Up to 6 characters | `123456` |
| `registrationNumber` | Up to 10 characters | `1234567890` |
| `passingYear` | Exactly 4 characters | `2026` |
| `gpa` | 0.00 to 5.00 | `5.0` |
| `name` | Up to 30 characters | `Tanvir Hasan` |
| `gender` | `Male` or `Female` | `Male` |
| `mobile` | Exactly 11 characters | `01799887766` |
| `email` | Valid email | `tanvir@example.com` |
| `fatherName` | Up to 30 characters | `Anwar Hasan` |
| `motherName` | Up to 30 characters | `Rokeya Begum` |
| `guardianName` | Up to 30 characters | `Anwar Hasan` |
| `relationWithGuardian` | Up to 30 characters | `Father` |
| `guardianMobile` | Exactly 11 characters | `01799887766` |
| `presentAddress` | Text | `Radio Colony, Savar, Dhaka` |

## 6. Test the admission flow

Run these in order from **6. Admission Flow**. Each request saves what the next one needs.

1. **Upload Media / Photo (Step 1)** is optional. In **Body**, choose a file in the `file` row's **Value** column, then send. Expect `201`.
2. **Submit Admission Form (Step 2)**: send it as is. Expect `201`. The application number, student ID and password are saved for you.
3. **App Login (New Applicant) (Step 3)**: send it. Expect `200`. You're now logged in as the applicant.
4. **Get My Application Status**: expect `200` with `"status": "pending"`.
5. **Download Admission Slip PDF**: expect `200`. Select **Send and Download** to save the file.

Then pay from **7. Payment Gateway (SSLCommerz)**:

1. Send **Initiate Admission Payment**. Expect `200` with a `gateway_url`.
2. Open `gateway_url` in a browser on the same computer as the server, and complete the payment using the sandbox's test options.
3. The browser should land on `/api/payment/result?status=success`.
4. Send **Get My Application Status** again. Expect `"status": "under_review"`.

Each Submit creates a real application and accounts in your database, so it's best to run this flow only on a local database.

## 7. Requests to be careful with

| Request | Effect | How to undo it |
| :--- | :--- | :--- |
| Account Delete / Deactivate | The account can no longer log in | Run `php artisan db:seed --class=CreateUsersSeeder` to restore the test accounts |
| Update Profile | Overwrites the profile of the logged-in user | Send it again with the old values |
| Submit Admission Form | Adds a new application and two users each time | Remove them from the database |

## 8. Troubleshooting

| What you see | Likely cause | Fix |
| :--- | :--- | :--- |
| Could not send request / connection refused | The server isn't running, or `base_url` is wrong | Run `php artisan serve` and check `base_url` |
| `401 Unauthenticated.` | Not logged in, or the token is invalid | Send a login request again |
| `401 Invalid credentials!` on login | Wrong ID for that role, test accounts not seeded, or the account was deactivated | Check section 4, then run the seeder |
| `302` redirect or an HTML page | The `Accept: application/json` header is missing | Add the header |
| `500`: `Attempt to read property "class_id" on null` | The logged-in account has no enrollment | Log in as an enrolled student. See section 1 if the test student has none |
| `400` with "No ... found" | The account has no matching data, or the filter excluded it | Add the data, or remove the filter. The request itself is fine |
| `422` | A field is missing or breaks a rule | The `errors` object names the field. See section 5 |
| `429` | Over 60 requests per minute | Wait a minute |
| `500`: `Failed to generate PDF.` or `pdf_url: null` | The `imagick` PHP extension isn't installed | Install it for your PHP version, then restart the server |
| `/storage/...` link returns `404` | The storage link is missing | Run `php artisan storage:link` |
| Composer error: requires PHP `>= 8.5.0` | The server was started with an older PHP | Stop it, check `php -v`, and start it again with PHP 8.5 |
| `500` on Logout | Known issue | See below |

## 9. Known issues

These backend issues will show up during testing. You haven't set anything up wrongly:

- **Logout** returns `500` and the token stays valid.
- **Change Profile Photo** returns `201` but doesn't save the photo.
- **Get Teachers & Departments** returns `500` with no filter if a teacher has no department.

The full list is in [API_DOCUMENTATION.md, section 11](API_DOCUMENTATION.md#11-known-issues).
