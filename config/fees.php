<?php

/*
| Admission fees in BDT. The server alone decides what is charged; an amount sent by
| a browser or the mobile app is ignored.
*/
return [
    'application_fee' => (float) env('ADMISSION_APPLICATION_FEE', 500),
    'admission_fee' => (float) env('ADMISSION_ADMISSION_FEE', 3000),
];
