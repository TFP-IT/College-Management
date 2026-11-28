<?php

/*
| Campus modules. Each entry describes one kind of record: who it is about, its fields, its statuses,
| who may see or create it, and any approval chain. The office portal, the app and the permissions are
| all drawn from this file, so a rule is written once.
|
| Field shorthand: "label|type|required|option,option". Types: text, textarea, date, datetime, number, money, select.
| Keys title, starts_at, ends_at and amount are real columns; other keys are stored in the data column.
*/

$leaveTypes = 'casual,sick,earned,maternity,study,official,unpaid';

return [

    // Role keys that may manage each group. "admin" covers superadmin and school admin accounts.
    'groups' => [
        'academic' => ['label' => 'Academics', 'roles' => ['admin', 'Principal']],
        'finance' => ['label' => 'Scholarship and waiver', 'roles' => ['admin', 'accounts', 'Principal']],
        'welfare' => ['label' => 'Conduct and welfare', 'roles' => ['admin', 'Principal', 'Discipline Officer']],
        // Confidential groups are not open to general administrators.
        'safeguarding' => ['label' => 'Safeguarding (confidential)', 'roles' => ['Safeguarding Officer'], 'confidential' => true],
        'counselling' => ['label' => 'Counselling (confidential)', 'roles' => ['Counsellor'], 'confidential' => true],
        'transport' => ['label' => 'Transport', 'roles' => ['admin', 'Transport Coordinator']],
        'services' => ['label' => 'Campus services', 'roles' => ['admin', 'Operations Manager', 'librarian']],
        'activities' => ['label' => 'Sports, culture and clubs', 'roles' => ['admin', 'Principal', 'Sports Coordinator', 'Cultural Coordinator']],
        'future' => ['label' => 'Career and future', 'roles' => ['admin', 'Principal', 'Career Coordinator']],
        'innovation' => ['label' => 'Innovation and competitions', 'roles' => ['admin', 'Principal', 'Innovation Coordinator']],
        'hr' => ['label' => 'Staff and HR', 'roles' => ['admin', 'HR Officer', 'Principal']],
        'operations' => ['label' => 'Operations and compliance', 'roles' => ['admin', 'Operations Manager', 'accounts', 'Principal']],
        'alumni' => ['label' => 'Graduation and alumni', 'roles' => ['admin', 'Registrar', 'Principal']],
    ],

    'types' => [

        // ---- Academics ---------------------------------------------------
        'calendar_event' => [
            'group' => 'academic', 'label' => 'Calendar entry', 'plural' => 'Academic calendar', 'icon' => 'calendar', 'broadcast' => ['published'],
            'fields' => ['title' => 'Title|text|1', 'starts_at' => 'Date|date|1', 'ends_at' => 'Last day|date', 'kind' => 'Kind|select|1|holiday,exam,deadline,event,study leave,result day', 'details' => 'Details|textarea'],
            'statuses' => ['published' => ['Published', 'ok'], 'cancelled' => ['Cancelled', 'bad']],
        ],

        // ---- Finance -----------------------------------------------------
        'fee_plan' => [
            'group' => 'finance', 'label' => 'Monthly fee plan', 'plural' => 'Monthly fee plans', 'icon' => 'repeat', 'manage_roles' => ['admin', 'accounts'],
            'fields' => ['title' => 'Fee name (month is added)|text|1', 'section' => 'Section as classId-sectionId|section|1', 'amount' => 'Amount per month|money|1', 'due_day' => 'Due day of month (1-28)|number|1', 'late_fee' => 'Late fee|money', 'grace_days' => 'Grace days|number'],
            'statuses' => ['active' => ['Active', 'ok'], 'paused' => ['Paused', '']],
        ],
        'scholarship_program' => [
            'group' => 'finance', 'label' => 'Scholarship programme', 'plural' => 'Scholarship programmes', 'icon' => 'ribbon', 'broadcast' => ['open'],
            'fields' => ['title' => 'Programme name|text|1', 'kind' => 'Type|select|1|merit,need based,sports,cultural,special talent,institutional,sponsor', 'percent' => 'Percent of tuition (1-100)|number|1', 'ends_at' => 'Apply before|date', 'details' => 'Who can apply|textarea|1'],
            'statuses' => ['open' => ['Open', 'ok'], 'closed' => ['Closed', '']],
            'join' => ['type' => 'scholarship_application', 'label' => 'Apply'],
        ],
        'scholarship_application' => [
            'group' => 'finance', 'label' => 'Scholarship application', 'plural' => 'Scholarship applications', 'icon' => 'ribbon', 'subject' => 'student',
            'family' => ['create' => ['reason', 'household']], 'unique_per_parent' => true,
            'fields' => ['title' => 'Programme|text|1', 'reason' => 'Why are you applying?|textarea|1', 'household' => 'Family circumstances (optional)|textarea', 'percent' => 'Awarded percent|number', 'ends_at' => 'Award ends|date'],
            'statuses' => ['pending' => ['Under review', 'warn'], 'approved' => ['Awarded', 'ok'], 'rejected' => ['Not awarded', 'bad'], 'ended' => ['Ended', '']],
            'workflow' => ['accounts', 'Principal'],
        ],
        'fee_waiver' => [
            'group' => 'finance', 'label' => 'Fee waiver request', 'plural' => 'Fee waivers', 'icon' => 'pricetag', 'subject' => 'student',
            'family' => ['create' => ['title', 'invoice', 'amount', 'reason']],
            'fields' => ['title' => 'Subject|text|1', 'invoice' => 'Invoice number (INV-000000)|text|1', 'amount' => 'Amount to waive|money|1', 'reason' => 'Reason|textarea|1'],
            'statuses' => ['pending' => ['Under review', 'warn'], 'approved' => ['Approved', 'ok'], 'rejected' => ['Not approved', 'bad']],
            'workflow' => ['accounts', 'Principal'],
        ],

        // ---- Conduct and welfare ------------------------------------------
        'discipline_case' => [
            'group' => 'welfare', 'label' => 'Discipline case', 'plural' => 'Discipline cases', 'icon' => 'alert-circle', 'subject' => 'student', 'sensitive' => true,
            'teacher' => ['create' => ['title', 'kind', 'severity', 'starts_at', 'place', 'details']],
            // Families see a case only once the office has decided to inform them.
            'family' => ['view_statuses' => ['guardian_informed', 'action', 'appealed', 'closed']],
            'fields' => ['title' => 'Short summary|text|1', 'kind' => 'Type|select|1|late attendance,uniform,class misconduct,bullying,damage to property,academic misconduct,unauthorised absence,fighting,harassment,prohibited item,serious violation', 'severity' => 'Severity|select|1|minor,moderate,major,critical', 'starts_at' => 'When it happened|date|1', 'place' => 'Where|text', 'details' => 'What happened|textarea|1', 'action' => 'Corrective action|textarea', 'student_response' => "Student's response|textarea"],
            'statuses' => ['reported' => ['Reported', 'warn'], 'review' => ['Under review', 'info'], 'guardian_informed' => ['Guardian informed', 'warn'], 'action' => ['Action taken', 'info'], 'appealed' => ['Appealed', 'warn'], 'closed' => ['Closed', ''], 'dismissed' => ['Dismissed', '']],
            'notify_statuses' => ['guardian_informed', 'action', 'closed'], 'notify_text' => 'A conduct update is available. Please sign in to read it.',
        ],
        'commendation' => [
            'group' => 'welfare', 'label' => 'Commendation', 'plural' => 'Commendations', 'icon' => 'thumbs-up', 'subject' => 'student',
            'teacher' => ['create' => ['title', 'kind', 'details']], 'family' => [],
            'fields' => ['title' => 'Commended for|text|1', 'kind' => 'Type|select|1|conduct,leadership,helping others,academic honesty,community service,sportsmanship,attendance', 'details' => 'Details|textarea'],
            'statuses' => ['recorded' => ['Recorded', 'ok'], 'withdrawn' => ['Withdrawn', '']],
            'notify_on_create' => 'Your child received a commendation.',
        ],
        'safeguarding_concern' => [
            'group' => 'safeguarding', 'label' => 'Safety concern', 'plural' => 'Safety concerns', 'icon' => 'shield-checkmark', 'subject' => 'student', 'sensitive' => true,
            // Only the person who raised it can follow it from the app, and only its status.
            'family' => ['create' => ['kind', 'title', 'details'], 'own_only' => true], 'teacher' => ['create' => ['kind', 'title', 'details'], 'own_only' => true],
            'fields' => ['title' => 'In a few words|text|1', 'kind' => 'About|select|1|bullying,harassment,safety,wellbeing,family,health,child protection', 'details' => 'What is happening?|textarea|1', 'risk' => 'Risk level|select||low,medium,high,immediate', 'plan' => 'Action plan|textarea'],
            'statuses' => ['reported' => ['Received', 'warn'], 'assessing' => ['Being looked at', 'info'], 'plan' => ['Support in place', 'info'], 'closed' => ['Closed', '']],
            'private_fields' => ['risk', 'plan'],
        ],
        'health_record' => [
            'group' => 'safeguarding', 'label' => 'Health or screening record', 'plural' => 'Health and screening vault', 'icon' => 'medkit', 'subject' => 'student', 'sensitive' => true,
            'fields' => ['title' => 'Programme or event|text|1', 'starts_at' => 'Date|date|1', 'consent' => 'Guardian consent|select|1|received,not received,not required', 'result' => 'Result|textarea', 'followup' => 'Follow-up|textarea'],
            'statuses' => ['open' => ['Open', 'warn'], 'closed' => ['Closed', '']],
        ],
        'counselling_request' => [
            'group' => 'counselling', 'label' => 'Counselling appointment', 'plural' => 'Counselling', 'icon' => 'heart', 'subject' => 'student', 'sensitive' => true,
            'family' => ['create' => ['title', 'category', 'starts_at'], 'own_only' => true],
            'fields' => ['title' => 'What would you like to talk about?|text|1', 'category' => 'Category|select|1|study stress,friendship,family,wellbeing,future plans,other', 'starts_at' => 'Preferred time|datetime|1', 'notes' => 'Private session notes|textarea'],
            'statuses' => ['requested' => ['Requested', 'warn'], 'accepted' => ['Booked', 'ok'], 'completed' => ['Completed', ''], 'declined' => ['Not available', 'bad']],
            'private_fields' => ['notes'], 'notify_statuses' => ['accepted', 'declined'], 'notify_text' => 'Your appointment has an update. Please sign in.',
        ],

        // ---- Transport ---------------------------------------------------
        'transport_route' => [
            'group' => 'transport', 'label' => 'Bus route', 'plural' => 'Bus routes', 'icon' => 'bus', 'broadcast' => ['running'],
            'fields' => ['title' => 'Route name|text|1', 'stops' => 'Stops and pick-up times|textarea|1', 'vehicle' => 'Vehicle registration|text|1', 'driver' => 'Driver name|text', 'assistant' => 'Assistant|text', 'seats' => 'Seats|number|1', 'amount' => 'Monthly fee|money'],
            'statuses' => ['running' => ['Running', 'ok'], 'paused' => ['Paused', 'warn']],
            'join' => ['type' => 'transport_application', 'label' => 'Apply for this route', 'capacity_field' => 'seats'],
        ],
        'transport_application' => [
            'group' => 'transport', 'label' => 'Transport application', 'plural' => 'Transport riders', 'icon' => 'bus', 'subject' => 'student',
            'family' => ['create' => ['stop']], 'unique_per_parent' => true,
            'fields' => ['title' => 'Route|text|1', 'stop' => 'Your stop|text|1', 'amount' => 'Fee to bill on approval|money'],
            'statuses' => ['pending' => ['Waiting', 'warn'], 'approved' => ['On the route', 'ok'], 'rejected' => ['Not approved', 'bad'], 'ended' => ['Ended', '']],
            'workflow' => ['office'],
        ],
        'transport_incident' => [
            'group' => 'transport', 'label' => 'Transport alert', 'plural' => 'Transport alerts', 'icon' => 'warning', 'parent_type' => 'transport_route',
            'fields' => ['title' => 'Message to riders|text|1', 'kind' => 'Kind|select|1|bus delay,breakdown,route change,missed pick-up,emergency', 'details' => 'Details|textarea'],
            'statuses' => ['active' => ['Active', 'bad'], 'resolved' => ['Resolved', 'ok']],
        ],
        'boarding_log' => [
            'group' => 'transport', 'label' => 'Boarding record', 'plural' => 'Boarding records', 'icon' => 'log-in', 'subject' => 'student', 'family' => [],
            'fields' => ['title' => 'Bus or route|text|1', 'kind' => 'Event|select|1|boarded,dropped off', 'starts_at' => 'Time|datetime|1'],
            'statuses' => ['recorded' => ['Recorded', 'ok']], 'notify_on_create' => 'A bus boarding update was recorded.',
        ],

        // ---- Campus services ----------------------------------------------
        'uniform_order' => [
            'group' => 'services', 'label' => 'Uniform order', 'plural' => 'Uniform orders', 'icon' => 'shirt', 'subject' => 'student',
            'family' => ['create' => ['title', 'size', 'quantity', 'measurements']],
            'fields' => ['title' => 'Item|select|1|school uniform,sports kit,college blazer,tie and badge', 'size' => 'Size|text|1', 'quantity' => 'Quantity|number|1', 'measurements' => 'Measurements or notes|textarea', 'amount' => 'Charge when ready|money'],
            'statuses' => ['requested' => ['Requested', 'warn'], 'measuring' => ['Measurement', 'info'], 'ready' => ['Ready to collect', 'ok'], 'collected' => ['Collected', ''], 'cancelled' => ['Cancelled', 'bad']],
            'notify_statuses' => ['measuring', 'ready'], 'charge_on' => 'ready',
        ],
        'stationery_request' => [
            'group' => 'services', 'label' => 'Stationery request', 'plural' => 'Stationery', 'icon' => 'pencil', 'subject' => 'student',
            'family' => ['create' => ['title', 'quantity']],
            'fields' => ['title' => 'Item|select|1|textbook,exercise book,pens,lab materials,college diary,ID accessory', 'quantity' => 'Quantity|number|1', 'amount' => 'Charge when issued|money'],
            'statuses' => ['requested' => ['Requested', 'warn'], 'issued' => ['Issued', 'ok'], 'cancelled' => ['Cancelled', 'bad']],
            'notify_statuses' => ['issued'], 'charge_on' => 'issued',
        ],
        'grooming_booking' => [
            'group' => 'services', 'label' => 'Grooming appointment', 'plural' => 'Grooming', 'icon' => 'cut', 'subject' => 'student',
            'family' => ['create' => ['title', 'starts_at']],
            'fields' => ['title' => 'Service|select|1|haircut,grooming check', 'starts_at' => 'Preferred time|datetime|1'],
            'statuses' => ['requested' => ['Requested', 'warn'], 'booked' => ['Booked', 'ok'], 'completed' => ['Completed', ''], 'cancelled' => ['Cancelled', 'bad']],
            'notify_statuses' => ['booked', 'cancelled'],
        ],
        'facility_request' => [
            'group' => 'services', 'label' => 'Maintenance request', 'plural' => 'Maintenance', 'icon' => 'construct',
            'teacher' => ['create' => ['title', 'kind', 'place', 'priority', 'details'], 'own_only' => true], 'family' => ['create' => ['title', 'kind', 'place', 'details'], 'own_only' => true],
            'fields' => ['title' => 'What is wrong?|text|1', 'kind' => 'Area|select|1|building,electrical,IT,vehicle,laboratory,furniture,air conditioning,water,cleanliness,safety', 'place' => 'Room or place|text|1', 'priority' => 'Priority|select||normal,high,urgent', 'details' => 'Details|textarea', 'technician' => 'Technician or vendor|text'],
            'statuses' => ['open' => ['Reported', 'warn'], 'assigned' => ['Assigned', 'info'], 'repaired' => ['Repaired', 'ok'], 'closed' => ['Closed', '']],
            'notify_statuses' => ['assigned', 'repaired'],
        ],

        // ---- Activities --------------------------------------------------
        'activity' => [
            'group' => 'activities', 'label' => 'Club or team', 'plural' => 'Clubs and teams', 'icon' => 'football', 'broadcast' => ['open'],
            'fields' => ['title' => 'Name|text|1', 'kind' => 'Type|select|1|sport,debate,music,drama,art,public speaking,recitation,photography,writing,science,society', 'coach' => 'Coach or coordinator|text', 'schedule' => 'Practice times|text', 'seats' => 'Places|number', 'details' => 'About|textarea', 'committee' => 'Committee (president, secretary...)|textarea'],
            'statuses' => ['open' => ['Open to join', 'ok'], 'closed' => ['Closed', '']],
            'join' => ['type' => 'activity_membership', 'label' => 'Ask to join', 'capacity_field' => 'seats'],
        ],
        'activity_membership' => [
            'group' => 'activities', 'label' => 'Membership', 'plural' => 'Memberships', 'icon' => 'people', 'subject' => 'student',
            'family' => ['create' => ['note']], 'unique_per_parent' => true,
            'fields' => ['title' => 'Club or team|text|1', 'note' => 'Experience or position (optional)|textarea', 'role' => 'Role|select||member,captain,president,vice president,secretary,treasurer,event coordinator'],
            'statuses' => ['pending' => ['Waiting', 'warn'], 'approved' => ['Member', 'ok'], 'rejected' => ['Not accepted', 'bad'], 'left' => ['Left', '']],
            'workflow' => ['office'],
        ],
        'activity_event' => [
            'group' => 'activities', 'label' => 'Activity event', 'plural' => 'Activity events', 'icon' => 'flag', 'broadcast' => ['scheduled', 'completed'],
            'fields' => ['title' => 'Event|text|1', 'starts_at' => 'Date and time|datetime|1', 'place' => 'Venue|text', 'budget' => 'Budget|money', 'details' => 'Details|textarea', 'result' => 'Result and report|textarea'],
            'statuses' => ['proposed' => ['Proposed', 'warn'], 'scheduled' => ['Scheduled', 'info'], 'completed' => ['Completed', 'ok'], 'cancelled' => ['Cancelled', 'bad']],
        ],
        'achievement' => [
            'group' => 'activities', 'label' => 'Achievement', 'plural' => 'Achievements and certificates', 'icon' => 'trophy', 'subject' => 'student',
            'teacher' => ['create' => ['title', 'kind', 'starts_at', 'details']], 'family' => [],
            'fields' => ['title' => 'Achievement|text|1', 'kind' => 'Area|select|1|academic,sports,culture,innovation,leadership,community,skill badge,certificate', 'starts_at' => 'Date|date|1', 'details' => 'Criteria or details|textarea'],
            'statuses' => ['verified' => ['Verified', 'ok'], 'withdrawn' => ['Withdrawn', '']],
            'notify_on_create' => 'A new achievement was added to the portfolio.',
        ],

        // ---- Career and future -------------------------------------------
        'career_profile' => [
            'group' => 'future', 'label' => 'Career plan', 'plural' => 'Career plans', 'icon' => 'compass', 'subject' => 'student', 'one_per_student' => true,
            'family' => ['create' => ['title', 'field', 'universities', 'country', 'skills'], 'edit' => true],
            'fields' => ['title' => 'Career goal|text|1', 'field' => 'Preferred subject area|select|1|engineering,medicine,business,defence,technology,law,public service,creative,undecided', 'universities' => 'Target universities|textarea', 'country' => 'Preferred country|text', 'skills' => 'Strengths and skills|textarea', 'roadmap' => 'Roadmap and action plan (coordinator)|textarea', 'readiness' => 'Readiness percent (coordinator)|number'],
            'statuses' => ['active' => ['Active', 'info'], 'reviewed' => ['Reviewed', 'ok']],
        ],
        'defence_preparation' => [
            'group' => 'future', 'label' => 'Defence preparation', 'plural' => 'Defence preparation', 'icon' => 'medal', 'subject' => 'student', 'one_per_student' => true,
            'family' => ['create' => ['title', 'notes']],
            'fields' => ['title' => 'Service of interest|select|1|army,navy,air force,police,undecided', 'notes' => 'Why this path?|textarea', 'eligibility' => 'Eligibility|select||not checked,eligible,not yet eligible', 'progress' => 'Preparation percent|number', 'sessions' => 'Plan and upcoming sessions|textarea', 'mock' => 'Mock scores|textarea'],
            'statuses' => ['registered' => ['Registered', 'warn'], 'preparing' => ['Preparing', 'info'], 'applied' => ['Applied', 'ok'], 'stopped' => ['Stopped', '']],
        ],
        'university_application' => [
            'group' => 'future', 'label' => 'University application', 'plural' => 'University applications', 'icon' => 'school', 'subject' => 'student',
            'family' => ['create' => ['title', 'program', 'country', 'ends_at', 'documents'], 'edit' => true, 'status' => ['planning', 'application_ready', 'submitted', 'exam_scheduled', 'waiting_result', 'offered', 'accepted', 'rejected']],
            'fields' => ['title' => 'University|text|1', 'program' => 'Programme|text|1', 'country' => 'Country|text|1', 'ends_at' => 'Deadline|date', 'documents' => 'Documents ready|textarea', 'advice' => 'Coordinator advice|textarea'],
            'statuses' => ['planning' => ['Planning', ''], 'eligible' => ['Eligible', 'info'], 'application_ready' => ['Ready', 'info'], 'submitted' => ['Submitted', 'info'], 'exam_scheduled' => ['Exam set', 'warn'], 'waiting_result' => ['Waiting', 'warn'], 'offered' => ['Offer', 'ok'], 'accepted' => ['Accepted', 'ok'], 'rejected' => ['Not offered', 'bad']],
        ],
        'ielts_test' => [
            'group' => 'future', 'label' => 'IELTS and English test', 'plural' => 'IELTS and English lab', 'icon' => 'language', 'subject' => 'student', 'family' => [],
            'teacher' => ['create' => ['title', 'starts_at', 'listening', 'reading', 'writing', 'speaking', 'target', 'feedback']],
            'fields' => ['title' => 'Test|select|1|diagnostic,mock test,speaking session,final', 'starts_at' => 'Date|date|1', 'listening' => 'Listening band|number', 'reading' => 'Reading band|number', 'writing' => 'Writing band|number', 'speaking' => 'Speaking band|number', 'target' => 'Target band|number', 'feedback' => 'Feedback and practice plan|textarea'],
            'statuses' => ['recorded' => ['Recorded', 'ok'], 'scheduled' => ['Scheduled', 'info']],
        ],
        'goal' => [
            'group' => 'future', 'label' => 'Personal goal', 'plural' => 'Student goals', 'icon' => 'flag', 'subject' => 'student',
            'family' => ['create' => ['title', 'kind', 'current', 'target', 'ends_at', 'actions'], 'edit' => true, 'status' => ['active', 'completed', 'revised']],
            'fields' => ['title' => 'Goal|text|1', 'kind' => 'Area|select|1|academic,career,language,leadership,innovation,sports,personal', 'current' => 'Where are you now?|text', 'target' => 'Target|text|1', 'ends_at' => 'By when|date', 'actions' => 'Actions each week|textarea', 'review' => 'Mentor review|textarea'],
            'statuses' => ['active' => ['In progress', 'info'], 'completed' => ['Achieved', 'ok'], 'revised' => ['Revised', 'warn']],
        ],
        'mentoring' => [
            'group' => 'future', 'label' => 'Mentoring session', 'plural' => 'Mentoring', 'icon' => 'people-circle', 'subject' => 'student', 'staff_field' => 'Mentor',
            'teacher' => ['view' => 'assigned', 'update' => ['notes', 'actions']], 'family' => [],
            'fields' => ['title' => 'Focus|text|1', 'starts_at' => 'Session|datetime', 'actions' => 'Action items|textarea', 'notes' => 'Mentor notes (private)|textarea'],
            'statuses' => ['planned' => ['Planned', 'info'], 'held' => ['Held', 'ok'], 'cancelled' => ['Cancelled', '']],
            'private_fields' => ['notes'],
        ],
        'opportunity' => [
            'group' => 'future', 'label' => 'Opportunity', 'plural' => 'Opportunities', 'icon' => 'megaphone', 'broadcast' => ['open'],
            'fields' => ['title' => 'Title|text|1', 'kind' => 'Type|select|1|scholarship,university admission,competition,internship,seminar,career talk,exchange,IELTS session,leadership', 'ends_at' => 'Deadline|date', 'link' => 'Link|text', 'details' => 'Details|textarea|1'],
            'statuses' => ['open' => ['Open', 'ok'], 'closed' => ['Closed', '']], 'announce' => 'New opportunity',
        ],

        // ---- Innovation --------------------------------------------------
        'innovation_track' => [
            'group' => 'innovation', 'label' => 'Innovation track', 'plural' => 'Innovation tracks', 'icon' => 'hardware-chip', 'broadcast' => ['open'],
            'fields' => ['title' => 'Track|text|1', 'level' => 'Level|select|1|beginner,intermediate,advanced', 'mentor' => 'Supervisor|text', 'schedule' => 'Lab times|text', 'details' => 'Modules and projects|textarea'],
            'statuses' => ['open' => ['Enrolling', 'ok'], 'closed' => ['Closed', '']],
            'join' => ['type' => 'innovation_enrollment', 'label' => 'Enrol'],
        ],
        'innovation_enrollment' => [
            'group' => 'innovation', 'label' => 'Track enrolment', 'plural' => 'Track enrolments', 'icon' => 'school', 'subject' => 'student', 'family' => ['create' => ['note']], 'unique_per_parent' => true,
            'fields' => ['title' => 'Track|text|1', 'note' => 'What do you want to build?|textarea'],
            'statuses' => ['pending' => ['Waiting', 'warn'], 'approved' => ['Enrolled', 'ok'], 'rejected' => ['Not accepted', 'bad'], 'completed' => ['Completed', '']],
            'workflow' => ['office'],
        ],
        'innovation_project' => [
            'group' => 'innovation', 'label' => 'Project', 'plural' => 'Student projects', 'icon' => 'bulb', 'subject' => 'student', 'staff_field' => 'Supervisor',
            'family' => ['create' => ['title', 'category', 'objective', 'team', 'ends_at'], 'edit' => true], 'teacher' => ['view' => 'assigned', 'update' => ['evaluation']],
            'fields' => ['title' => 'Project title|text|1', 'category' => 'Category|select|1|robotics,drone,programming,app,web,AI,IoT,electronics,3D design,research,cybersecurity', 'objective' => 'What will it do?|textarea|1', 'team' => 'Team members and roles|textarea', 'ends_at' => 'Target date|date', 'link' => 'Files or demo link|text', 'evaluation' => 'Evaluation|textarea'],
            'statuses' => ['proposed' => ['Proposed', 'warn'], 'approved' => ['Approved', 'info'], 'in_progress' => ['In progress', 'info'], 'testing' => ['Testing', 'info'], 'completed' => ['Completed', 'ok'], 'showcased' => ['Showcased', 'ok'], 'awarded' => ['Awarded', 'ok'], 'rejected' => ['Not approved', 'bad']],
            'notify_statuses' => ['approved', 'completed', 'awarded', 'rejected'],
        ],
        'competition' => [
            'group' => 'innovation', 'label' => 'Competition', 'plural' => 'Competitions', 'icon' => 'trophy', 'broadcast' => ['open', 'judging', 'finished'],
            'fields' => ['title' => 'Competition|text|1', 'kind' => 'Type|select|1|science fair,programming,robotics,debate,essay,business idea,app development,culture,sports', 'starts_at' => 'Date|date|1', 'ends_at' => 'Register before|date', 'details' => 'Eligibility and rules|textarea|1', 'results' => 'Results|textarea'],
            'statuses' => ['open' => ['Registration open', 'ok'], 'judging' => ['Judging', 'warn'], 'finished' => ['Finished', '']],
            'join' => ['type' => 'competition_entry', 'label' => 'Register'], 'announce' => 'New competition',
        ],
        'competition_entry' => [
            'group' => 'innovation', 'label' => 'Entry', 'plural' => 'Competition entries', 'icon' => 'ticket', 'subject' => 'student', 'family' => ['create' => ['team']], 'unique_per_parent' => true,
            'fields' => ['title' => 'Competition|text|1', 'team' => 'Team (leave empty if solo)|textarea', 'score' => 'Judges score|number', 'award' => 'Award|text'],
            'statuses' => ['registered' => ['Registered', 'info'], 'participated' => ['Took part', ''], 'winner' => ['Winner', 'ok'], 'withdrawn' => ['Withdrawn', 'bad']],
            'initial' => 'registered', 'notify_statuses' => ['winner'],
        ],

        // ---- Appointments (any group of staff) -----------------------------
        'appointment' => [
            'group' => 'academic', 'label' => 'Appointment', 'plural' => 'Appointments', 'icon' => 'time', 'subject' => 'student', 'staff_field' => 'Meet with',
            'family' => ['create' => ['title', 'staff_id', 'mode', 'starts_at']], 'teacher' => ['view' => 'assigned', 'status' => ['accepted', 'declined', 'completed']],
            'fields' => ['title' => 'Purpose|text|1', 'mode' => 'How|select|1|in person,voice call,video call', 'starts_at' => 'Preferred time|datetime|1', 'meeting_link' => 'Meeting link or room|text'],
            'statuses' => ['requested' => ['Requested', 'warn'], 'accepted' => ['Confirmed', 'ok'], 'declined' => ['Declined', 'bad'], 'completed' => ['Completed', '']],
            'notify_statuses' => ['accepted', 'declined'], 'notify_staff' => true,
        ],

        // ---- Staff and HR --------------------------------------------------
        'leave_request' => [
            'group' => 'hr', 'label' => 'Leave request', 'plural' => 'Leave requests', 'icon' => 'airplane', 'subject' => 'staff',
            'teacher' => ['create' => ['kind', 'starts_at', 'ends_at', 'title'], 'own_only' => true],
            'fields' => ['title' => 'Reason|text|1', 'kind' => 'Leave type|select|1|' . $leaveTypes, 'starts_at' => 'From|date|1', 'ends_at' => 'To|date|1', 'substitute' => 'Substitute teacher arranged|textarea'],
            'statuses' => ['pending' => ['Waiting', 'warn'], 'approved' => ['Approved', 'ok'], 'rejected' => ['Not approved', 'bad']],
            'workflow' => ['HR Officer', 'Principal'],
        ],
        'payslip' => [
            'group' => 'hr', 'label' => 'Payslip', 'plural' => 'Payroll', 'icon' => 'cash', 'subject' => 'staff', 'table' => 'staff_payslips', 'manage_roles' => ['admin', 'HR Officer'], 'sensitive' => true,
            'fields' => ['period' => 'Salary month (first day)|date|1', 'basic' => 'Basic salary|money|1', 'allowances' => 'Allowances|money', 'deductions' => 'Deductions|money'],
            'statuses' => ['draft' => ['Draft', 'warn'], 'issued' => ['Issued', 'info'], 'paid' => ['Paid', 'ok']], 'title_field' => 'period',
        ],
        'appraisal' => [
            'group' => 'hr', 'label' => 'Appraisal', 'plural' => 'Appraisals', 'icon' => 'stats-chart', 'subject' => 'staff', 'table' => 'staff_appraisals', 'sensitive' => true,
            'fields' => ['period_label' => 'Period|text|1', 'reviewer_name' => 'Reviewer|text', 'review_date' => 'Review date|date', 'summary' => 'Summary|textarea', 'strengths' => 'Strengths|textarea', 'development_areas' => 'Development plan|textarea'],
            'statuses' => ['scheduled' => ['Scheduled', 'info'], 'in_progress' => ['In progress', 'warn'], 'completed' => ['Completed', 'ok']], 'title_field' => 'period_label',
        ],
        'training' => [
            'group' => 'hr', 'label' => 'Training', 'plural' => 'Staff training', 'icon' => 'book', 'subject' => 'staff', 'table' => 'staff_trainings',
            'fields' => ['title' => 'Training|text|1', 'provider' => 'Provider|text', 'start_date' => 'Date|date', 'hours' => 'Hours|number', 'completed_on' => 'Completed on|date'],
            'statuses' => ['enrolled' => ['Assigned', 'info'], 'completed' => ['Completed', 'ok'], 'cancelled' => ['Cancelled', '']], 'title_field' => 'title',
        ],
        'staff_conduct' => [
            'group' => 'hr', 'label' => 'Staff record', 'plural' => 'Staff warnings and commendations', 'icon' => 'document-text', 'subject' => 'staff', 'table' => 'staff_conduct_records', 'sensitive' => true,
            'fields' => ['title' => 'Title|text|1', 'type' => 'Type|select|1|warning,discipline,commendation', 'issued_on' => 'Date|date|1', 'issued_by' => 'Issued by|text', 'details' => 'Details|textarea'],
            'statuses' => ['open' => ['Open', 'warn'], 'resolved' => ['Resolved', 'ok'], 'expired' => ['Expired', '']], 'title_field' => 'title',
        ],

        // ---- Operations, governance --------------------------------------
        'purchase_request' => [
            'group' => 'operations', 'label' => 'Purchase request', 'plural' => 'Procurement', 'icon' => 'cart',
            'teacher' => ['create' => ['title', 'items', 'amount', 'reason'], 'own_only' => true],
            'fields' => ['title' => 'What is needed|text|1', 'items' => 'Items and quantities|textarea|1', 'amount' => 'Estimated cost|money|1', 'reason' => 'Why|textarea', 'quotations' => 'Quotations received|textarea', 'vendor' => 'Chosen vendor|text', 'order' => 'Purchase order and delivery|textarea'],
            'statuses' => ['pending' => ['Waiting approval', 'warn'], 'approved' => ['Approved', 'info'], 'rejected' => ['Rejected', 'bad'], 'ordered' => ['Ordered', 'info'], 'received' => ['Received', 'ok'], 'paid' => ['Paid', 'ok']],
            'workflow' => ['office', 'accounts', 'Principal'],
        ],
        'vendor' => [
            'group' => 'operations', 'label' => 'Vendor', 'plural' => 'Vendors', 'icon' => 'storefront',
            'fields' => ['title' => 'Vendor name|text|1', 'category' => 'Category|select|1|stationery,uniform,catering,transport,technology,maintenance,security,printing,events,training', 'contact' => 'Contact|textarea', 'performance' => 'Performance notes|textarea'],
            'statuses' => ['applied' => ['Applied', 'warn'], 'approved' => ['Approved', 'ok'], 'suspended' => ['Suspended', 'bad']],
        ],
        'contract' => [
            'group' => 'operations', 'label' => 'Contract or MoU', 'plural' => 'Contracts', 'icon' => 'document-attach',
            'fields' => ['title' => 'Contract|text|1', 'partner' => 'Partner|text|1', 'starts_at' => 'Starts|date|1', 'ends_at' => 'Expires|date|1', 'amount' => 'Value|money', 'owner' => 'Responsible department|text'],
            'statuses' => ['active' => ['Active', 'ok'], 'renewal' => ['Renewal due', 'warn'], 'ended' => ['Ended', '']], 'expiry_alert' => 90,
        ],
        'asset' => [
            'group' => 'operations', 'label' => 'Asset', 'plural' => 'Assets', 'icon' => 'cube',
            'fields' => ['title' => 'Asset|text|1', 'tag' => 'Asset number|text|1', 'category' => 'Category|select|1|computer,projector,lab equipment,furniture,vehicle,sports equipment,robotics,network,office', 'department' => 'Assigned to|text', 'amount' => 'Purchase value|money', 'ends_at' => 'Next maintenance|date'],
            'statuses' => ['in_use' => ['In use', 'ok'], 'maintenance' => ['Maintenance', 'warn'], 'retired' => ['Retired', '']], 'expiry_alert' => 30,
        ],
        'inventory_item' => [
            'group' => 'operations', 'label' => 'Stock item', 'plural' => 'Inventory', 'icon' => 'layers',
            'fields' => ['title' => 'Item|text|1', 'opening' => 'Opening stock|number|1', 'received' => 'Received|number', 'issued' => 'Issued|number', 'damaged' => 'Damaged or expired|number', 'reorder_at' => 'Reorder level|number'],
            'statuses' => ['stocked' => ['Stocked', 'ok'], 'low' => ['Reorder', 'bad'], 'closed' => ['Closed', '']],
        ],
        'compliance_item' => [
            'group' => 'operations', 'label' => 'Compliance requirement', 'plural' => 'Compliance calendar', 'icon' => 'checkbox',
            'fields' => ['title' => 'Requirement|text|1', 'kind' => 'Kind|select|1|licence,registration,inspection,insurance,vehicle fitness,staff certification,policy review,accreditation,regulatory report', 'ends_at' => 'Due or expires|date|1', 'owner' => 'Owner|text', 'evidence' => 'Evidence and gaps|textarea'],
            'statuses' => ['open' => ['Open', 'warn'], 'ready' => ['Ready', 'info'], 'done' => ['Done', 'ok']], 'expiry_alert' => 90,
        ],
        'policy_document' => [
            'group' => 'operations', 'label' => 'Document', 'plural' => 'Policies and documents', 'icon' => 'documents',
            'fields' => ['title' => 'Title|text|1', 'category' => 'Category|select|1|policy,procedure,agreement,circular,academic regulation,HR,compliance,board', 'version' => 'Version|text|1', 'starts_at' => 'Effective from|date', 'body' => 'Text or link|textarea|1'],
            'statuses' => ['draft' => ['Draft', 'warn'], 'review' => ['Under review', 'info'], 'active' => ['Active', 'ok'], 'superseded' => ['Superseded', ''], 'archived' => ['Archived', '']],
        ],

        // ---- Graduation, alumni and partners -------------------------------
        'graduation_clearance' => [
            'group' => 'alumni', 'label' => 'Graduation clearance', 'plural' => 'Graduation clearance', 'icon' => 'school', 'subject' => 'student', 'one_per_student' => true, 'family' => [],
            'fields' => ['title' => 'Qualification|text|1', 'academic' => 'Academic requirements|select|1|pending,complete', 'property' => 'College property returned|select|1|pending,complete', 'documents' => 'Mandatory documents|select|1|pending,complete', 'remarks' => 'Remarks|textarea'],
            'statuses' => ['pending' => ['Clearance pending', 'warn'], 'approved' => ['Graduation approved', 'ok'], 'rejected' => ['Not approved', 'bad']],
            'workflow' => ['Registrar', 'Principal'],
        ],
        'credential' => [
            'group' => 'alumni', 'label' => 'Certificate', 'plural' => 'Digital certificates', 'icon' => 'ribbon', 'subject' => 'student', 'family' => [], 'verifiable' => true,
            'fields' => ['title' => 'Certificate title|text|1', 'kind' => 'Type|select|1|completion,achievement,transcript,activity transcript,leadership,innovation,language', 'starts_at' => 'Issue date|date|1', 'details' => 'Details|textarea'],
            'statuses' => ['active' => ['Valid', 'ok'], 'replaced' => ['Replaced', 'warn'], 'revoked' => ['Revoked', 'bad']], 'notify_on_create' => 'A certificate was issued.',
        ],
        'transcript_request' => [
            'group' => 'alumni', 'label' => 'Transcript request', 'plural' => 'Transcript requests', 'icon' => 'document', 'subject' => 'student', 'family' => ['create' => ['title', 'delivery']],
            'fields' => ['title' => 'Purpose|text|1', 'delivery' => 'Delivery|select|1|in app,collect at office,send to institution', 'notes' => 'Office notes|textarea'],
            'statuses' => ['pending' => ['Requested', 'warn'], 'approved' => ['Approved', 'info'], 'delivered' => ['Delivered', 'ok'], 'rejected' => ['Rejected', 'bad']],
            'workflow' => ['Registrar'],
        ],
        'alumni_profile' => [
            'group' => 'alumni', 'label' => 'Alumni profile', 'plural' => 'Alumni', 'icon' => 'people', 'subject' => 'student', 'one_per_student' => true,
            'family' => ['create' => ['title', 'university', 'organisation', 'country', 'mentoring', 'visibility'], 'edit' => true],
            'fields' => ['title' => 'Profession or study|text|1', 'university' => 'University and degree|text', 'organisation' => 'Organisation|text', 'country' => 'Country|text', 'mentoring' => 'Willing to mentor|select||no,yes', 'visibility' => 'Who can see this|select|1|only the college,classmates,all alumni'],
            'statuses' => ['active' => ['Active', 'ok'], 'inactive' => ['Inactive', '']],
        ],
        'alumni_event' => [
            'group' => 'alumni', 'label' => 'Alumni event', 'plural' => 'Alumni events', 'icon' => 'calendar', 'broadcast' => ['scheduled'],
            'fields' => ['title' => 'Event|text|1', 'starts_at' => 'Date|datetime|1', 'kind' => 'Kind|select|1|reunion,career seminar,networking,guest lecture,fundraising,sports reunion', 'details' => 'Details|textarea'],
            'statuses' => ['scheduled' => ['Scheduled', 'info'], 'held' => ['Held', 'ok'], 'cancelled' => ['Cancelled', 'bad']],
        ],
        'partner' => [
            'group' => 'alumni', 'label' => 'Partner', 'plural' => 'Employers and universities', 'icon' => 'business',
            'fields' => ['title' => 'Organisation|text|1', 'kind' => 'Kind|select|1|employer,university,training provider,publisher,technology', 'country' => 'Country|text', 'contact' => 'Contact|textarea', 'agreement' => 'Agreement or MoU|textarea', 'ends_at' => 'Agreement expires|date'],
            'statuses' => ['review' => ['Under review', 'warn'], 'approved' => ['Approved', 'ok'], 'ended' => ['Ended', '']], 'expiry_alert' => 90,
        ],
    ],
];
