<?php

return [
    // Provider used when a request does not name one.
    'default_payment_provider' => env('ADMISSIONS_PAYMENT_PROVIDER', 'mock'),

    // The mock gateway lets anyone 'pay' without money. It is on only for local development and tests.
    // It must never be enabled on a live server.
    'mock_payments_enabled' => env('ADMISSIONS_MOCK_PAYMENTS', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)),

    // Shared secret for the mock gateway's signed callbacks. Set a long random value in .env.
    'mock_secret' => env('ADMISSIONS_MOCK_SECRET', 'local-only-mock-secret-change-me'),

    // Application and admission fee amounts are read from the program, never from the request.
    'otp_expose_debug_code' => env('ADMISSIONS_OTP_DEBUG', false),

    // Fixed test code for development and tests only (e.g. 123456). Never enabled on a live server.
    'test_otp_enabled' => env('ADMISSIONS_TEST_OTP_ENABLED', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)),
    'test_otp_code' => env('ADMISSIONS_TEST_OTP_CODE', '123456'),

    // Applicant session/token lifetime in minutes.
    'applicant_token_minutes' => 60 * 24,

    // Document limits.
    'max_document_bytes' => 5 * 1024 * 1024,
    'max_photo_bytes' => 1024 * 1024,
    'max_documents_per_application' => 10,
];
