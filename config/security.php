<?php

return [

    /*
    | The web installer can create a superadmin and rewrite configuration, so it stays off.
    | Turn it on only while setting up a brand new, empty installation, then turn it off.
    */
    'installer_enabled' => env('APP_INSTALLER_ENABLED', false),

    /*
    | The addon / product updater runs PHP from an uploaded zip file. Keep it off and
    | deploy code changes through git instead.
    */
    'updater_enabled' => env('APP_UPDATER_ENABLED', false),

    // How long a signed admission slip download link stays valid.
    'slip_link_hours' => (int) env('ADMISSION_SLIP_LINK_HOURS', 24),

];
