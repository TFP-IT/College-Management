> **Superseded.** The problems in this guide were fixed in the build that followed. Use [ADMISSION_AND_CMS_GUIDE.md](ADMISSION_AND_CMS_GUIDE.md) for the current state and the test commands. This file is kept as the record of what was found.

# Admission Module: Implementation Check and Testing Guide

This guide compares the admission module with its specification and shows you how to repeat every check yourself.

- **Specification:** [MCC_Admission_Module_Brief_Laravel.md](MCC_Admission_Module_Brief_Laravel.md), with [PLAN.md](PLAN.md), [DECISIONS.md](DECISIONS.md), [ERD.md](ERD.md) and [ADMISSIONS_API.md](ADMISSIONS_API.md)
- **Checked:** 5 November 2026, on the uncommitted working tree
- **In the tables:** ✅ meets the spec, ❌ doesn't meet it, ⚠️ partly meets it

---

## 1. Result

**The module isn't ready to use.** The 12 automated tests pass, but a real applicant can't complete the journey, and the staff and payment parts aren't secure.

The tests pass because they skip the broken parts. They insert documents directly into the database and set statuses such as `ADMISSION_FEE_PAID` by hand, so they never exercise real uploads, staff pages or payments.

### Blockers

| # | Problem | Effect | Where to fix |
| :--- | :--- | :--- | :--- |
| 1 | Every document upload returns `500`: `Unknown column 'storage_disk'` | No application can get past `SUBMITTED`, because that step needs a verified document | `Models/ApplicationDocument.php`, `Services/DocumentService.php` |
| 2 | Staff access isn't controlled | Without logging in, anyone can verify documents, schedule exams and enter scores. Any logged-in student can approve applications | `routes/admissions_web.php`, `routes/admissions_api.php`, and the empty `Policies/` folder |
| 3 | Applicants aren't checked | Anyone can read an application using its sequential number, or edit a draft using its ID | `Http/Controllers/Api/V1/ApplicationApiController.php` |
| 4 | Payments can be faked | One unsigned request marks a fee as paid | `Payments/MockGateway.php`, `Http/Controllers/Api/V1/PaymentApiController.php` |
| 5 | SSLCommerz can't work | Its config is missing, its return URLs are blocked by CSRF (`419`), and its IPN route crashes | `Payments/SSLCommerzGateway.php`, `Http/Controllers/Web/MockPaymentWebController.php` |
| 6 | Key pages crash | The staff dashboard, mock payment page, receipt page, and evaluation page (once a decision exists) all return `500` | See [section 7](#7-check-the-pages-in-a-browser) |
| 7 | Data is lost when a draft is created | The address, blood group and previous school sent with a new application are discarded | `Actions/CreateDraftApplicationAction.php` |

Paths in this table are under `app/Modules/Admissions/`, except the route files.

**Suggested fix order:** 1, 7 and 6 make the journey work. Then 2 and 3 secure it, and 4 and 5 make payments safe. After that, work through [section 9](#9-what-is-missing).

---

## 2. Before you start

**Never run these checks on `mcc_db_live`.** They create users and payments, and they change settings. `phpunit.xml` doesn't set a test database, so `php artisan test` uses whatever is in `.env`.

This guide uses a temporary copy of your database named `mcc_admission_check`.

You need PHP 8.5, MySQL, `curl` and `jq`. Run every command from the project root.

---

## 3. Set up

### 3.1 Copy the database

Run this in **terminal 1**. It reads the database login from your Laravel config, so you don't need to type it.

```bash
export DBU="$(php artisan tinker --execute='echo config("database.connections.mysql.username");' 2>/dev/null)"
export MYSQL_PWD="$(php artisan tinker --execute='echo config("database.connections.mysql.password");' 2>/dev/null)"

mysql -h127.0.0.1 -u"$DBU" -e "CREATE DATABASE mcc_admission_check CHARACTER SET utf8mb4;"
mysqldump -h127.0.0.1 -u"$DBU" --single-transaction --no-tablespaces mcc_db_live | mysql -h127.0.0.1 -u"$DBU" mcc_admission_check
```

The checks need the seeded programs, sections and staff accounts. If `mcc_db_live` doesn't have them, run this for the copy:

```bash
DB_DATABASE=mcc_admission_check php artisan db:seed --class=AdmissionSeeder --force
```

### 3.2 Start a server on the copy

Run this in **terminal 2** and leave it running. Use this command rather than `php artisan serve`, because it's the one confirmed to stay on the copy.

```sh
DB_DATABASE=mcc_admission_check PHP_CLI_SERVER_WORKERS=4 php -d variables_order=EGPCS -S 127.0.0.1:8012 -t public vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

### 3.3 Load the helper commands

Back in **terminal 1**, paste this. You'll need to paste it again if you open a new terminal, along with the two `export` lines from 3.1.

```bash
BASE=http://127.0.0.1:8012
API=$BASE/api/v1/admissions
OUT=/tmp/mcc_check.json

# Usage: api METHOD URL [JSON_BODY] [TOKEN]
api() {
  local args=(-s -m 30 -o "$OUT" -w '%{http_code}' -X "$1" -H 'Accept: application/json')
  [ -n "$3" ] && args+=(-H 'Content-Type: application/json' -d "$3")
  [ -n "$4" ] && args+=(-H "Authorization: Bearer $4")
  echo "HTTP $(curl "${args[@]}" "$2")  $(jq -c '.message // .' "$OUT" 2>/dev/null | cut -c1-150)"
}
val() { jq -r "$1" "$OUT"; }
sql() { mysql -h127.0.0.1 -u"$DBU" -N mcc_admission_check -e "$1"; }
app_status() { sql "select status from applications where id='$1'"; }
add_doc() {
  local id=$(sql "select uuid()")
  sql "insert into application_documents(id,application_id,doc_type,object_key,file_name,mime_type,size_bytes,status,created_at,updated_at) values('$id','$1','BIRTH_CERT','x/y.png','y.png','image/png',10,'UPLOADED',now(),now())"
  echo "$id"
}
```

### 3.4 Confirm the server is on the copy

```bash
sql "update admission_programs set title='CHECK-COPY' where code='C6-2026'"
curl -s $API/programs | grep -o 'CHECK-COPY'
```

**This must print `CHECK-COPY`.** If it doesn't, the server is using another database, so stop here and don't continue.

The API allows 60 requests a minute. If you see `HTTP 429`, wait a minute and repeat that command.

---

## 4. Run the automated tests

```bash
DB_DATABASE=mcc_admission_check php artisan test --testsuite=Feature
```

**Expected:** `12 passed`. They need the seeded data from the copy.

This only confirms that the tested Action classes work when called directly. It says little about the real journey, so continue with the sections below.

---

## 5. Walk through the applicant's journey

Run each block in order. Each one tells you what the spec expects and what happens today.

### Step 1: Find a program and start an application

```bash
api GET $API/programs
PROGRAM=$(val '.data[]|select(.code=="PG-2026").id')

api POST $API/apply "{\"program_id\":\"$PROGRAM\",\"candidate_name\":\"Check One\",\"phone\":\"01900000001\",\"email\":\"one@example.com\",\"candidate_dob\":\"2022-05-15\",\"gender\":\"MALE\",\"current_address\":\"12 Test Road\"}"
APP=$(val .data.id); APP_NO=$(val .data.application_number)
echo "$APP_NO  address=$(val .data.candidate.current_address)  qr_length=$(val '.data.qr.token|length')"
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Draft is created | `201`, number like `APP-2026-000001` | ✅ |
| Address is saved | `12 Test Road` | ❌ `null`: blocker 7 |
| QR code | Issued only on approval | ❌ Already issued on the draft |

### Step 2: Add guardians, then submit

```bash
api PUT $API/applications/$APP '{"current_address":"12 Test Road","guardians":[{"relation":"FATHER","full_name":"Father One","phone":"01911111111","nid_passport":"19801234567890","is_primary":true}]}'
sql "select left(nid_passport,10) from application_guardians where application_id='$APP'"

api POST $API/applications/$APP/submit
app_status $APP
sql "select ifnull(submitted_at,'NULL') from applications where id='$APP'"
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Only the owner can edit | `401` or `403` without a login | ❌ `200` with no login |
| NID is encrypted | Stored value starts with `eyJ` | ✅ |
| Submitting without required documents | `422` | ❌ `200`, status `PENDING_APP_FEE` |
| `submitted_at` is recorded | A date | ❌ `NULL` |

### Step 3: Upload a document

```bash
php -r 'file_put_contents("/tmp/mcc_check.png", base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVR4nGNgAAAAAgABSK+kcQAAAABJRU5ErkJggg=="));'
curl -s -o "$OUT" -w 'HTTP %{http_code}\n' -H 'Accept: application/json' -F doc_type=BIRTH_CERT -F file=@/tmp/mcc_check.png $API/applications/$APP/documents
val .message | cut -c1-100

echo hello > /tmp/mcc_check.txt
curl -s -o "$OUT" -w 'HTTP %{http_code}\n' -H 'Accept: application/json' -F doc_type=OTHER -F file=@/tmp/mcc_check.txt $API/applications/$APP/documents
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Valid image uploads | `201` | ❌ `500`, `Unknown column 'storage_disk'`: blocker 1 |
| Text file is refused | `422` | ✅ |

**While blocker 1 exists,** add the document directly to the copy so you can test the later steps:

```bash
DOC=$(add_doc $APP); echo "$DOC"
```

### Step 4: Pay the application fee

```bash
api POST $API/applications/$APP/pay '{"payment_type":"APPLICATION_FEE","provider":"mock","amount":1}'
REF=$(val .data.payment.transaction_ref); PAYMENT=$(val .data.payment.id)
echo "amount charged: $(val .data.payment.amount)"

api POST $API/payments/callback "{\"transaction_ref\":\"$REF\",\"status\":\"FAILED\"}"
echo "after failure: $(app_status $APP)"

api POST $API/payments/callback "{\"transaction_ref\":\"$REF\",\"status\":\"SUCCESS\",\"amount\":1}"
echo "after unsigned success: $(app_status $APP)"

api POST $API/payments/callback "{\"transaction_ref\":\"$REF\"}"
sql "select count(*) from application_status_history where application_id='$APP' and to_status='SUBMITTED'"
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Amount the client sends is ignored | Charged `500` | ✅ |
| Failed payment leaves the status alone | `PENDING_APP_FEE` | ✅ |
| Unsigned callback is refused | Refused and logged | ❌ Accepted, status `SUBMITTED`: blocker 4 |
| Wrong amount is refused | Refused | ❌ Accepted |
| Repeat callback does nothing | History count stays `1` | ✅ |

### Step 5: Log in as staff

```bash
api POST $BASE/api/login '{"email":"council@mcc.edu.bd","password":"Council@123"}'
api POST $BASE/api/v1/auth/login '{"email":"council@mcc.edu.bd","password":"Council@123"}'

api POST $BASE/api/app_login '{"role":"guardian","login_id":"01711000001","password":"Council@123"}'
COUNCIL=$(val .token)
api POST $BASE/api/app_login '{"role":"guardian","login_id":"01711000002","password":"Officer@123"}'
OFFICER=$(val .token)
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Staff can log in through the API | `POST /api/v1/auth/login` | ❌ `404`. `/api/login` also refuses staff |
| Login checks the role | Council member can't log in as a guardian | ❌ This is the only way to get a staff token today |

### Step 6: Review and decide

```bash
api POST $API/officer/applications/$APP/verify-document/$DOC '{"status":"VERIFIED"}' $OFFICER
echo "$(app_status $APP)"

SCHEDULED_AT=$(date -d '+7 days' '+%F %T')
api POST $API/officer/applications/$APP/schedule-assessment "{\"assessment_type\":\"WRITTEN\",\"scheduled_at\":\"$SCHEDULED_AT\",\"venue\":\"Hall B\"}" $OFFICER
ASSESSMENT=$(val .data.id); echo "$(app_status $APP)"

api POST $API/officer/applications/$APP/assessments/$ASSESSMENT/score '{"obtained_marks":88}' $COUNCIL
echo "$(app_status $APP)"

api POST $API/council/applications/$APP/decision '{"decision":"APPROVED","remarks":"Good score"}' $COUNCIL
echo "$(app_status $APP)"
```

**Spec and today ✅:** the status moves through `UNDER_REVIEW`, `EXAM_SCHEDULED`, `ASSESSED`, then `APPROVED`.

### Step 7: Pay the admission fee and enroll

```bash
SECTION=$(sql "select s.id from academic_sections s join admission_programs p on p.class_id=s.class_id where p.code='PG-2026' and s.name='A' limit 1")

echo "--- enroll before paying"
api POST $API/officer/applications/$APP/enroll "{\"section_id\":\"$SECTION\"}" $OFFICER

api POST $API/applications/$APP/pay '{"payment_type":"ADMISSION_FEE","provider":"mock"}'
REF2=$(val .data.payment.transaction_ref); echo "amount: $(val .data.payment.amount)"
api POST $API/payments/callback "{\"transaction_ref\":\"$REF2\"}"
echo "$(app_status $APP)"

echo "--- enroll by the person who decided"
api POST $API/officer/applications/$APP/enroll "{\"section_id\":\"$SECTION\"}" $COUNCIL

echo "--- enroll into a full section"
sql "update academic_sections set capacity=0 where id='$SECTION'"
api POST $API/officer/applications/$APP/enroll "{\"section_id\":\"$SECTION\"}" $OFFICER
sql "update academic_sections set capacity=40 where id='$SECTION'"

echo "--- enroll"
api POST $API/officer/applications/$APP/enroll "{\"section_id\":\"$SECTION\",\"roll_no\":\"1\"}" $OFFICER
STUDENT_NO=$(val .data.student_id); echo "$STUDENT_NO"

echo "--- enroll again"
api POST $API/officer/applications/$APP/enroll "{\"section_id\":\"$SECTION\",\"roll_no\":\"1\"}" $OFFICER
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Enroll before paying | Blocked | ✅ `422` |
| Admission fee comes from the program | `12000` | ✅ |
| Enroll by the person who decided | Blocked | ✅ `422` |
| Enroll into a full section | Blocked | ✅ `422` |
| Student number format | Like `MCC26S000001` | ✅ |
| Enroll twice | Same result, no duplicates | ⚠️ No duplicates, but `422` rather than the same result |

### Step 8: Check what enrollment created

```bash
sql "select concat('status=',status,' enrolled_student_id=',ifnull(enrolled_student_id,'NULL'),' qr_used_at=',ifnull(qr_used_at,'NULL')) from applications where id='$APP'"
sql "select concat('profile address=',ifnull(current_address,'NULL')) from student_profiles where student_number='$STUDENT_NO'"
sql "select concat('legacy enrollments=',count(*)) from enrollments e join users u on u.id=e.user_id where u.student_id='$STUDENT_NO'"
sql "select concat(ifnull(from_status,'-'),' -> ',to_status) from application_status_history where application_id='$APP' order by created_at"
sql "select action from audit_logs where record_id='$APP' order by created_at"

api POST $BASE/api/app_login "{\"role\":\"student\",\"login_id\":\"$STUDENT_NO\",\"password\":\"Student@123\"}"
NEW_STUDENT=$(val .token)
api POST $BASE/api/user_details '' $NEW_STUDENT
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Application links to the student | `enrolled_student_id` is set | ❌ `NULL` |
| QR is marked used | A date | ❌ `NULL` |
| History and audit row for each status change | One each | ✅ |
| Audit row for each sensitive action | Includes document checks, payments, decisions | ⚠️ Status changes only. Rejecting a document leaves no audit row |
| Student gets a set-password link | Never a plain password | ❌ Everyone gets `Student@123`, and guardians `Guardian@123` |
| New student can use the existing app API | Works | ❌ `/api/user_details` returns `500`: no row in the legacy `enrollments` table |

---

## 6. Check security and rule enforcement

This helper creates an application that's ready for a decision. Paste it once.

```bash
# Usage: ready_app PROGRAM_ID NAME PHONE DOB GUARDIAN_PHONE    (sets $READY)
ready_app() {
  api POST $API/apply "{\"program_id\":\"$1\",\"candidate_name\":\"$2\",\"phone\":\"$3\",\"candidate_dob\":\"$4\",\"gender\":\"FEMALE\"}" >/dev/null
  READY=$(val .data.id)
  api PUT $API/applications/$READY "{\"guardians\":[{\"relation\":\"FATHER\",\"full_name\":\"Father of $2\",\"phone\":\"$5\",\"is_primary\":true}]}" >/dev/null
  api POST $API/applications/$READY/submit >/dev/null
  api POST $API/applications/$READY/pay '{"payment_type":"APPLICATION_FEE","provider":"mock"}' >/dev/null
  local ref=$(val .data.payment.transaction_ref)
  api POST $API/payments/callback "{\"transaction_ref\":\"$ref\"}" >/dev/null
  local doc=$(add_doc $READY)
  api POST $API/officer/applications/$READY/verify-document/$doc '{"status":"VERIFIED"}' $OFFICER >/dev/null
  echo "ready: $READY  $(app_status $READY)"
}
# Usage: pay_fee APPLICATION_ID    (fakes the admission fee payment)
pay_fee() {
  api POST $API/applications/$1/pay '{"payment_type":"ADMISSION_FEE","provider":"mock"}' >/dev/null
  local ref=$(val .data.payment.transaction_ref)
  api POST $API/payments/callback "{\"transaction_ref\":\"$ref\"}" >/dev/null
}
CLASS1=$(curl -s $API/programs | jq -r '.data[]|select(.code=="C1-2026").id')
SCIENCE=$(curl -s $API/programs | jq -r '.data[]|select(.code=="XI-SCI-2026").id')
CLASS1_SECTION=$(sql "select s.id from academic_sections s join admission_programs p on p.class_id=s.class_id where p.code='C1-2026' and s.name='A' limit 1")
SCIENCE_SECTION=$(sql "select id from academic_sections where name='Science A' limit 1")
```

### 6.1 Access control

```bash
api POST $BASE/api/app_login '{"role":"student","login_id":"STD-1001","password":"12345678"}'
STUDENT=$(val .token)

echo "--- no token"
api GET $API/council/applications
echo "--- student lists every application"
api GET $API/council/applications '' $STUDENT
echo "--- student approves an application"
ready_app $CLASS1 "Check Two" 01900000002 2019-01-02 01922222222
api POST $API/council/applications/$READY/decision '{"decision":"APPROVED","remarks":"forged"}' $STUDENT
app_status $READY

echo "--- anyone reads an application by its number"
api GET $API/applications/$APP_NO/status
echo "$(val .data.candidate.name) / $(val '.data.guardians[0].phone') / qr=$(val '.data.qr.token|length') chars"

echo "--- anyone edits a draft"
api POST $API/apply "{\"program_id\":\"$CLASS1\",\"candidate_name\":\"Victim\",\"phone\":\"01900000003\",\"candidate_dob\":\"2019-01-03\",\"gender\":\"MALE\"}"
VICTIM=$(val .data.id)
api PUT $API/applications/$VICTIM '{"candidate_name":"HIJACKED"}'
sql "select applicant_name from applications where id='$VICTIM'"

echo "--- public QR scan of a draft"
QR=$(sql "select json_unquote(json_extract(academic_history,'$.raw_qr_token')) from applications where id='$VICTIM'")
curl -s "$BASE/admissions/qr/$QR" | sed 's/<[^>]*>//g' | grep -m1 -i 'verified'
sql "select concat('qr_used_at=',ifnull(qr_used_at,'NULL')) from applications where id='$VICTIM'"

echo "--- track with phone only"
curl -s "$BASE/admissions/track?application_number=$APP_NO&phone=01900000001" | grep -c 'Check One'
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| No token on staff API | `401` | ✅ |
| Student on staff API | `403` | ❌ `200`, and the approval is saved |
| Application A can't read application B | `403` or `404` | ❌ `200` with no login, including guardian phone and QR token |
| Editing without a login | `401` or `403` | ❌ `200`, and the name changes |
| QR for a draft | Refused | ❌ Shows "Admission Pass Verified" |
| Public QR scan | Doesn't use up the QR | ❌ Sets `qr_used_at` |
| Tracking | Needs a verified OTP | ❌ Phone number alone is enough |

### 6.2 Payments

```bash
echo "--- pay twice, then an unknown gateway"
api POST $API/apply "{\"program_id\":\"$CLASS1\",\"candidate_name\":\"Check Four\",\"phone\":\"01900000005\",\"candidate_dob\":\"2019-01-05\",\"gender\":\"MALE\"}"
TWICE=$(val .data.id)
api PUT $API/applications/$TWICE '{"guardians":[{"relation":"FATHER","full_name":"F","phone":"01944444444","is_primary":true}]}'
api POST $API/applications/$TWICE/submit
api POST $API/applications/$TWICE/pay '{"payment_type":"APPLICATION_FEE","provider":"nonsense"}'
echo "saved provider: $(val .data.payment.provider)"
api POST $API/applications/$TWICE/pay '{"payment_type":"APPLICATION_FEE","provider":"mock"}'; R1=$(val .data.payment.transaction_ref)
api POST $API/applications/$TWICE/pay '{"payment_type":"APPLICATION_FEE","provider":"mock"}'; R2=$(val .data.payment.transaction_ref)
api POST $API/payments/callback "{\"transaction_ref\":\"$R1\"}"
api POST $API/payments/callback "{\"transaction_ref\":\"$R2\"}"
sql "select concat('successful fee payments=',count(*)) from admission_payments where application_id='$TWICE' and payment_status='SUCCESS'"

echo "--- callback with no details"
api POST $API/payments/callback '{}'
val .data.transaction_ref

echo "--- real gateway"
api POST $API/applications/$TWICE/pay '{"payment_type":"ADMISSION_FEE","provider":"sslcommerz"}'
curl -s -o /dev/null -w 'SSLCommerz return URL: HTTP %{http_code}\n' -X POST "$BASE/admissions/payments/callback/$(sql "select id from admission_payments limit 1")?status=success" -d 'val_id=x'
curl -s -o /dev/null -w 'IPN URL: HTTP %{http_code}\n' "$BASE/admissions/payments/ipn?tran_id=x"
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Unknown gateway name | Refused | ❌ `200`, saved as `nonsense`, handled as mock |
| One successful fee per application | `1` | ❌ `2` |
| Callback with no details | Refused | ❌ `200`, and it processes the first payment in the table |
| SSLCommerz is usable | Works in sandbox | ❌ See below |

The SSLCommerz test above only shows that the fee rule stops the request, because the application isn't approved yet. These are the actual failures:

- **Credentials aren't found.** The code reads `SSLCZ_*` variables and `services.sslcommerz`, but the project uses `SSL_*` variables and has no such config. Starting a payment with credentials missing returns `500`.
- **The return URL returns `419`**, because it's a web route with CSRF protection.
- **The IPN URL returns `500`.** On `POST` it returns `419`.
- **The return page never records the payment.** It only displays a receipt.

### 6.3 Application rules

```bash
echo "--- duplicate application"
api POST $API/apply "{\"program_id\":\"$PROGRAM\",\"candidate_name\":\"Check One\",\"phone\":\"01900000001\",\"candidate_dob\":\"2022-05-15\",\"gender\":\"MALE\"}"
echo "--- missing date of birth"
api POST $API/apply "{\"program_id\":\"$PROGRAM\",\"candidate_name\":\"No Dob\",\"phone\":\"01900000006\"}"
echo "--- validation error format"
api POST $API/apply '{"candidate_name":""}'
jq -c 'keys' "$OUT"
echo "--- applicant far outside the age limit"
api POST $API/apply "{\"program_id\":\"$PROGRAM\",\"candidate_name\":\"Too Old\",\"phone\":\"01900000007\",\"candidate_dob\":\"2000-01-01\",\"gender\":\"MALE\"}"

echo "--- deadline"
api POST $API/apply "{\"program_id\":\"$CLASS1\",\"candidate_name\":\"Late\",\"phone\":\"01900000008\",\"candidate_dob\":\"2019-01-08\",\"gender\":\"MALE\"}"
LATE=$(val .data.id)
api PUT $API/applications/$LATE '{"guardians":[{"relation":"FATHER","full_name":"F","phone":"01955555555","is_primary":true}]}'
sql "update admission_programs set application_deadline='2020-01-01' where id='$CLASS1'"
api POST $API/applications/$LATE/submit
api POST $API/apply "{\"program_id\":\"$CLASS1\",\"candidate_name\":\"Later\",\"phone\":\"01900000009\",\"candidate_dob\":\"2019-01-09\",\"gender\":\"MALE\"}"
sql "update admission_programs set application_deadline='2027-01-01' where id='$CLASS1'"

echo "--- withdraw"
api POST $API/applications/$LATE/withdraw
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Duplicate application | Clear `422` | ⚠️ Blocked, but with `500` and a raw SQL error |
| Missing date of birth | `422` | ❌ `500` |
| Error format | `success`, `message`, `data`, `meta`, `errors` | ❌ Validation errors only have `message` and `errors` |
| Eligibility | Refused or flagged | ❌ `201` |
| Submit after the deadline | Refused | ❌ `200` |
| New application after the deadline | Refused | ✅ `422` |
| Withdraw | Available | ❌ `404` |

### 6.4 Decisions and enrollment rules

```bash
echo "--- wrong order: decision on a draft"
api POST $API/council/applications/$VICTIM/decision '{"decision":"APPROVED"}' $COUNCIL

echo "--- decision with no assessment, and no reason given"
ready_app $CLASS1 "Check Five" 01900000010 2019-01-10 01966666666; WAIT=$READY
api POST $API/council/applications/$WAIT/decision '{"decision":"WAITLISTED"}' $COUNCIL
echo "$(app_status $WAIT)"
echo "--- waitlisted to approved"
api POST $API/council/applications/$WAIT/decision '{"decision":"APPROVED"}' $COUNCIL
echo "$(app_status $WAIT)"

echo "--- rejected applicant"
ready_app $CLASS1 "Check Six" 01900000011 2019-01-11 01977777777; REJ=$READY
api POST $API/council/applications/$REJ/decision '{"decision":"REJECTED"}' $COUNCIL
api POST $API/applications/$REJ/pay '{"payment_type":"ADMISSION_FEE"}'
api POST $API/officer/applications/$REJ/enroll "{\"section_id\":\"$CLASS1_SECTION\"}" $OFFICER

echo "--- approve when no seats are left"
sql "update admission_programs set seats=0 where id='$SCIENCE'"
ready_app $SCIENCE "Check Seven" 01900000012 2010-01-12 01988888888; NOSEAT=$READY
api POST $API/council/applications/$NOSEAT/decision '{"decision":"APPROVED"}' $COUNCIL
echo "$(app_status $NOSEAT)"
sql "update admission_programs set seats=120 where id='$SCIENCE'"

echo "--- enroll into another class's section"
pay_fee $WAIT
api POST $API/officer/applications/$WAIT/enroll "{\"section_id\":\"$SCIENCE_SECTION\"}" $OFFICER
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Illegal status change | `422` | ✅ |
| Decision before assessment | Only from `ASSESSED` or `WAITLISTED` | ❌ Allowed from `UNDER_REVIEW` |
| Reason for reject or waitlist | Required | ❌ Not required |
| Waitlisted can later be approved | Allowed | ✅ |
| Rejected can't pay or enroll | `422` | ✅ |
| Approve when seats are full | Forced to `WAITLISTED` | ❌ `APPROVED` |
| Section must match the class | Refused | ❌ A Class 1 applicant can join `Science A` |

### 6.5 Guardians

```bash
echo "--- siblings sharing a guardian"
ready_app $CLASS1 "Sibling A" 01900000013 2019-01-13 01999999999; SIB1=$READY
api POST $API/council/applications/$SIB1/decision '{"decision":"APPROVED"}' $COUNCIL; pay_fee $SIB1
api POST $API/officer/applications/$SIB1/enroll "{\"section_id\":\"$CLASS1_SECTION\"}" $OFFICER
ready_app $CLASS1 "Sibling B" 01900000014 2019-01-14 01999999999; SIB2=$READY
api POST $API/council/applications/$SIB2/decision '{"decision":"APPROVED"}' $COUNCIL; pay_fee $SIB2
api POST $API/officer/applications/$SIB2/enroll "{\"section_id\":\"$CLASS1_SECTION\"}" $OFFICER
sql "select concat('guardian users=',count(*)) from users where phone_number='01999999999'"

echo "--- child's contact number is the father's number"
ready_app $CLASS1 "Shared Phone" 01900000015 2019-01-15 01900000015; SHARED=$READY
api POST $API/council/applications/$SHARED/decision '{"decision":"APPROVED"}' $COUNCIL; pay_fee $SHARED
api POST $API/officer/applications/$SHARED/enroll "{\"section_id\":\"$CLASS1_SECTION\"}" $OFFICER
sql "select concat('users=',count(*),' roles=',group_concat(role_id)) from users where phone_number='01900000015'"
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Sibling reuses the guardian | `guardian users=1` | ✅ |
| Shared phone number | Separate student and guardian accounts | ❌ `users=1 roles=7`: the student's account becomes its own guardian. This will be common for young children |

### 6.6 OTP

```bash
echo "--- wrong code limit"
api POST $API/auth/otp/send '{"phone":"01900000020","purpose":"TRACK"}'
CODE=$(val .data.debug_otp)
for i in 1 2 3 4 5 6; do api POST $API/auth/otp/verify '{"phone":"01900000020","code":"000000","purpose":"TRACK"}'; done
api POST $API/auth/otp/verify "{\"phone\":\"01900000020\",\"code\":\"$CODE\",\"purpose\":\"TRACK\"}"

echo "--- correct code"
api POST $API/auth/otp/send '{"phone":"01900000021","purpose":"TRACK"}'
CODE=$(val .data.debug_otp)
api POST $API/auth/otp/verify "{\"phone\":\"01900000021\",\"code\":\"$CODE\",\"purpose\":\"TRACK\"}"
echo "token: $(val '.data.token // "none"')"
api POST $API/auth/otp/verify "{\"phone\":\"01900000021\",\"code\":\"$CODE\",\"purpose\":\"TRACK\"}"

echo "--- repeated sends"
for i in 1 2 3 4 5 6; do api POST $API/auth/otp/send '{"phone":"01900000022","purpose":"TRACK"}'; done
```

| Check | Spec | Today |
| :--- | :--- | :--- |
| Wrong code limit | Locked after 5 tries | ✅ |
| Code works only once | Second use refused | ✅ |
| Verifying returns a token | Short-lived applicant token | ❌ `verified: true` only |
| Send limit | Throttled | ❌ The sixth send still returns `200`. The count deletes its own earlier codes, so the limit never triggers |

`debug_otp` only appears when `APP_ENV` is `local` or `testing`. Make sure production doesn't use either.

---

## 7. Check the pages in a browser

Stay logged out and open these in a private window on `http://127.0.0.1:8012`. To get an application's ID, run `echo $APP`.

| Page | Spec | Today |
| :--- | :--- | :--- |
| `/admissions` | Program cards | ✅ Loads |
| `/admissions/apply/<program id>`, submitted **with** a document | Saved, moves to payment | ❌ `500`, and it leaves a half-created draft behind |
| `/admissions/track` | Number plus OTP | ❌ No OTP step |
| `/admissions/applications/APP-2026-000001` | Owner or staff only | ❌ Shows it to anyone |
| `/admissions/payments/mock/<payment id>` | Payment choices | ❌ `500`: `PaymentType::label()` doesn't exist |
| `/admissions/payments/callback/<payment id>` | Receipt | ❌ `500`: same cause |
| `/admin/admissions` | Login page for visitors, dashboard for staff | ❌ `500`: `Unknown column 'application_status'`. Filtering by status has the same bug |
| `/admin/admissions/applications/<id>` before a decision | Login page | ❌ Full application shown to anyone, with working forms |
| Same page after a decision | Evaluation page | ❌ `500`: `CouncilDecision::badgeClass()` doesn't exist |

On the evaluation page, while still logged out, use **Verify**, **Schedule** and **Save score**. All three work today and change the status. Only the decision and enrollment buttons need a login.

Also log in at `/login` as `officer@mcc.edu.bd` / `Officer@123`. You land in the old admin panel, because the seeder gives this account the legacy `admin` role (`role_id` 2). The council account gets the legacy `teacher` role (`role_id` 3). That gives them access to unrelated parts of the school system, and `/admin/admissions` isn't linked from there.

### Wizard features

I searched the view files for these rather than testing each by hand, so confirm them in a browser after the page errors are fixed.

| Feature | Spec | Today |
| :--- | :--- | :--- |
| Five steps with progress bar | Yes | ✅ |
| OTP on step 1 | Yes | ❌ Not found |
| Live eligibility check on step 3 | Yes | ❌ Not found |
| Drag and drop upload with progress | Yes | ❌ Not found |
| Consent checkbox on step 5 | Yes | ❌ Not found |
| Saved progress, offline banner | Yes | ❌ Not found |
| Double-submit protection | Yes | ❌ Not found |
| Status timeline, onboarding checklist on tracking | Yes | ⚠️ The application page has a progress stepper. The tracking page has no timeline, and neither has a checklist |
| Confirmation before a decision, required reason | Yes | ❌ Not found |
| Remaining section capacity on enrollment | Yes | ✅ |

---

## 8. Check the documents against the code

### ADMISSIONS_API.md

| Claim | Today |
| :--- | :--- |
| The listed endpoints exist | ✅ All routes exist |
| Standard error format everywhere | ❌ Validation and not-found errors use Laravel's default format |
| `candidate_dob` optional on `/apply` | ❌ Leaving it out gives `500`. `gender` too, because the column can't be empty |
| Document upload works (2.5) | ❌ Always `500` |
| OTP verification grants access (2.8) | ❌ No token, and nothing else uses the result |
| Staff use a Sanctum token (section 3) | ❌ No staff login endpoint is documented or available |
| `candidate@mcc.edu.bd` and `guardian@mcc.edu.bd` exist | ❌ Only `council@` and `officer@` are seeded. Every enrolled student and new guardian does share the listed passwords |

### ERD.md

| Claim | Today |
| :--- | :--- |
| Columns on applications, payments, documents, assessments, decisions, guardians, programs, enrollments | ✅ Match the database |
| Tables `classes`, `groups`, `sections` | ❌ The real tables are `academic_classes`, `academic_groups` and `academic_sections`. `classes` and `sections` exist but belong to the old system |
| `users.id` is a UUID | ❌ It's a `bigint` with a separate `uuid` column, as DECISIONS.md explains |
| `users.mobile` | ❌ The column is `phone_number` |

### DECISIONS.md and PLAN.md

| Claim | Today |
| :--- | :--- |
| `ApplicationPolicy`, `DocumentPolicy`, `EnrollmentPolicy` | ❌ `Policies/` is empty |
| Roles include `AdmissionOfficer`, `Principal`, `Admin` and others | ❌ Five different role names, no permissions, and nothing checks them |
| Row lock on payment callbacks | ❌ `lockForUpdate` is only used for number sequences |
| Duplicate callbacks are harmless | ✅ For the same payment. ❌ A second payment can also succeed |
| Presigned uploads, HEAD check | ❌ Direct upload to the local disk, with no presign or confirm step |
| Events use `ShouldDispatchAfterCommit`, with listeners and notifications | ❌ Not used. `Listeners/` and `Notifications/` are empty, so no email or SMS is ever sent |
| `IllegalStateTransitionException` | ⚠️ Uses `ValidationException`. The `422` result is the same |
| `WithdrawApplicationAction` | ❌ Doesn't exist |
| Web and API share Action classes | ✅ |
| UUID keys, number formats, encrypted NID | ✅ |
| Separation of duties | ✅ Enforced, though the role system around it isn't |

### Status rules compared with the brief

These status changes are allowed by `Services/ApplicationStateMachine.php` but aren't in the brief, and DECISIONS.md doesn't record them:

- `UNDER_REVIEW` to `APPROVED`, `WAITLISTED` or `REJECTED`
- `APPROVED` to `ENROLLED`, which skips the fee check at this level
- `REJECTED` to `UNDER_REVIEW`
- `WAITLISTED` to `EXPIRED`, and `WAITLISTED`, `ASSESSED` or `EXAM_SCHEDULED` back to `UNDER_REVIEW`
- `DRAFT` to `SUBMITTED`, used for zero-fee programs
- `WITHDRAWN` from `UNDER_REVIEW`, `EXAM_SCHEDULED`, `ASSESSED`, `WAITLISTED` and `ADMISSION_FEE_PAID`

`DRAFT` to `EXPIRED` is in the brief but missing here. Nothing can trigger `EXPIRED` or `WITHDRAWN` today in any case.

Decide which of these you want, then update either the code or the brief.

### Brief acceptance criteria (section 14)

| Criterion | Today |
| :--- | :--- |
| Happy path works end to end | ❌ Blocked by uploads and page errors |
| Failed payment leaves status unchanged, allows retry | ✅ |
| Replayed callback isn't processed twice | ✅ |
| Invalid signature or wrong amount is rejected and logged | ❌ No signature check, no amount check. Calls are logged, but `signature_valid` is always `0` |
| Client amount is ignored | ✅ |
| Illegal status change returns `422` | ✅ |
| Applicant A can't read B | ❌ |
| Enrollment blocked: unpaid, full section, same decider | ✅ |
| Double enroll is idempotent | ⚠️ No duplicates, but returns `422` |
| Waitlisted can be approved, rejected can't pay or enroll | ✅ |
| Existing guardian is reused for a sibling | ✅ Except when the student shares the phone number |
| Upload rejects wrong type | ✅ |
| Upload rejects oversize | ⚠️ Files over 2 MB fail anyway because of PHP's `upload_max_filesize=2M`, so valid files up to 5 MB fail too. There's no 1 MB photo limit |
| Upload rejects unconfirmed files | ❌ No confirm step |
| Can't submit without required documents | ❌ |
| Can't submit after the deadline | ❌ |
| Expiry jobs work | ❌ No job exists |
| Every sensitive action has an audit row | ⚠️ Status changes only |
| All screens show loading, error, empty and offline states | ❌ No offline handling found |
| Tests and a recorded E2E run | ❌ 12 tests, no E2E run |

---

## 9. What is missing

Confirm the main gaps with these commands. Today, the first prints `5` (all five paths are missing) and the second prints `0` (those folders are empty).

```bash
ls docker-compose.yml tests/Browser docs/e2e docs/API.md config/admissions.php 2>&1 | grep -c 'cannot access'
find app/Modules/Admissions/Policies app/Modules/Admissions/Listeners app/Modules/Admissions/Notifications app/Modules/Admissions/Rules -type f | wc -l
sql "select concat('classes=',count(*)) from academic_classes"
sql "select concat('programs=',count(*)) from admission_programs"
sql "select concat('permissions=',count(*)) from admissions_permissions"
sql "select count(*) from users where email in ('council@mcc.edu.bd','officer@mcc.edu.bd')"
```

**Files and infrastructure**

- `docker-compose.yml`, the MinIO and Redis setup, and S3 storage
- Model factories, `tests/Browser`, and the recorded run in `docs/e2e`
- `config/admissions.php`, which the code reads for the default gateway and which doesn't exist
- Tailwind and Alpine are loaded from a CDN instead of through Vite, and the icons are Font Awesome rather than Lucide

**API endpoints in the brief with no equivalent**

- `GET /applications/{id}/documents`, `POST /applications/{id}/withdraw`, `POST /track`
- `POST /documents/presign`, `POST /documents/{id}/confirm`
- `GET /payments/{ref}`, `GET /council/dashboard`, `POST /qr/verify`
- `GET /classes`, `GET /sections`, `POST /api/v1/auth/login`, `logout` and `refresh`

Others exist under different paths: for example `POST /apply` instead of `POST /applications`. Either keep those and update the brief, or rename them.

**Behaviour**

- Expiry job and orphan-upload cleanup
- Emails and SMS, set-password link, enrollment letter PDF, onboarding checklist
- CORS allow-list, stricter rate limits on public endpoints
- Waitlist rank, capacity override with an audit reason
- Failed uploads leave orphan files in `storage/app/admissions`

**Seed data**

| Item | Brief | Seeded |
| :--- | :--- | :--- |
| Classes | 15 | 9 (2 to 5, 7 and 8 are missing) |
| Programs | 16 | 6 |
| Sections | Per class and group | Playgroup, Class 1, Class 6 and Class XI only |
| Groups | Class XI and XII | Class XI only |
| Staff accounts | Admin, Officer, Council, Principal | Officer and Council only |
| Permissions | Defined | 0 |

`AdmissionSeeder` also isn't called from `DatabaseSeeder`, so a fresh install gets none of this, and the tests fail on it.

---

## 10. Clean up

Stop the server in terminal 2 with `Ctrl+C`, then run this in terminal 1:

```bash
mysql -h127.0.0.1 -u"$DBU" -e "DROP DATABASE mcc_admission_check;"
rm -f /tmp/mcc_check.json /tmp/mcc_check.png /tmp/mcc_check.txt
```

Failed uploads leave files under `storage/app/admissions/`. Check what's there, and if your real database still has no applications, delete the folder:

```bash
ls storage/app/admissions 2>/dev/null
rm -rf storage/app/admissions
```

---

## 11. Re-check after fixes

Use this as the finish line. Repeat sections 3 to 7 until all of these are true:

1. An application can go from the web wizard through to enrollment in a browser, with **no database edits and no `add_doc`**.
2. Every ❌ under [Access control](#61-access-control) and [Payments](#62-payments) is fixed.
3. A logged-out visitor can't reach any `/admin/admissions` page.
4. New automated tests cover the real upload, the staff pages and the permission failures, so these bugs can't return unnoticed.
5. A test database is set in `phpunit.xml`, so `php artisan test` can't touch `mcc_db_live`.
6. Run `php artisan test`. Then do the SSLCommerz sandbox payment for real, which this check didn't cover.
