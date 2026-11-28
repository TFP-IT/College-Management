<?php

return [
    'library_limit' => (int) env('LIBRARY_BORROW_LIMIT', 3),
    'library_fine_per_day' => (int) env('LIBRARY_FINE_PER_DAY', 5),
];
