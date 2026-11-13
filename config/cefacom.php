<?php

return [
    // Wallet money is always stored as integer kobo. Never use floats for currency.
    'currency' => 'NGN',
    'kobo_per_naira' => 100,

    // Timestamps are stored in UTC; this zone is used only for display and scheduling input.
    'display_timezone' => env('CEFACOM_DISPLAY_TIMEZONE', 'Africa/Lagos'),
];
