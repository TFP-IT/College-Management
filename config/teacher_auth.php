<?php

return [
    // Lets any email receive a setup OTP and creates a teacher account once the code is verified.
    // Local development only: the controller refuses it on a production server regardless of this value.
    'open_signup' => env('TEACHER_OTP_OPEN_SIGNUP', env('APP_ENV', 'production') === 'local'),

    // School assigned to teacher accounts created through open signup.
    'open_signup_school_id' => env('TEACHER_OTP_OPEN_SIGNUP_SCHOOL_ID', 1),
];
