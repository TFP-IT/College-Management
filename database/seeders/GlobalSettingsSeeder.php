<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GlobalSettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            ['key' => 'system_name', 'value' => 'Maestro Crown College'],
            ['key' => 'system_title', 'value' => 'Maestro Crown College'],
            ['key' => 'system_email', 'value' => 'maestrocrown@example.com'],
            ['key' => 'phone', 'value' => '900500000'],
            ['key' => 'address', 'value' => '107/2, Dogormura, Radio Colony,\r\\nDhaka Aricha Highway, Savar,\r\\nBangladesh, 1343'],
            ['key' => 'purchase_code', 'value' => '0000'],
            ['key' => 'system_currency', 'value' => 'BDT'],
            ['key' => 'currency_position', 'value' => 'left-space'],
            ['key' => 'running_session', 'value' => '1'],
            ['key' => 'language', 'value' => 'english'],
            ['key' => 'payment_settings', 'value' => '[]'],
            ['key' => 'footer_text', 'value' => 'Versatilo Group'],
            ['key' => 'footer_link', 'value' => 'https://versatilo.group/'],
            ['key' => 'version', 'value' => '2.3'],
            ['key' => 'fax', 'value' => '1234567890'],
            ['key' => 'timezone', 'value' => 'Asia/Dhaka'],
            ['key' => 'smtp_protocol', 'value' => ''],
            ['key' => 'smtp_crypto', 'value' => ''],
            ['key' => 'smtp_host', 'value' => ''],
            ['key' => 'smtp_port', 'value' => ''],
            ['key' => 'smtp_user', 'value' => ''],
            ['key' => 'smtp_pass', 'value' => ''],
            ['key' => 'offline', 'value' => '{"status":"1"}'],
            ['key' => 'light_logo', 'value' => '17880717462.png'],
            ['key' => 'dark_logo', 'value' => '17880717461.png'],
            ['key' => 'favicon', 'value' => '17880717463.png'],
            ['key' => 'randCallRange', 'value' => '30'],
            ['key' => 'help_link', 'value' => 'https://versatilo.group/'],
            ['key' => 'youtube_api_key', 'value' => 'youtube-api-key'],
            ['key' => 'vimeo_api_key', 'value' => 'vimeo-api-key'],
            ['key' => 'banner_title', 'value' => 'Bringing Excellence To Students'],
            ['key' => 'banner_subtitle', 'value' => 'Empowering and inspiring all students to excel as life long learners'],
            ['key' => 'facebook_link', 'value' => 'https://www.facebook.com/Maestrocrown/'],
            ['key' => 'twitter_link', 'value' => 'https://www.facebook.com/Maestrocrown/'],
            ['key' => 'linkedin_link', 'value' => 'https://www.facebook.com/Maestrocrown/'],
            ['key' => 'instagram_link', 'value' => 'https://www.facebook.com/Maestrocrown/'],
            ['key' => 'price_subtitle', 'value' => 'Choose the best subscription plan for your school'],
            ['key' => 'copyright_text', 'value' => '2024 Academy, All rights reserved'],
            ['key' => 'contact_email', 'value' => 'maestrocrown@gmail.com'],
            ['key' => 'frontend_footer_text', 'value' => 'Maestro Crown is a collection of programs designed to assist schools in administering their executive responsibilities on a day-to-day basis. It is designed for SAAS (Software as a Service) projects.'],
            ['key' => 'faq_subtitle', 'value' => 'Frequently asked questions'],
            ['key' => 'frontend_view', 'value' => '1'],
            ['key' => 'white_logo', 'value' => '17880717464.png'],
            ['key' => 'navbar_title', 'value' => 'MCC'],
            ['key' => 'email_title', 'value' => 'Subscription'],
            ['key' => 'email_details', 'value' => 'Feel free to reach out to us anytime if you have questions or feedback. We value your input and strive to provide the best experience possible.'],
            ['key' => 'warning_text', 'value' => 'This email is from an automat'],
            ['key' => 'email_logo', 'value' => '17880718031.png'],
            ['key' => 'socialLogo1', 'value' => '16907191042.png'],
            ['key' => 'socialLogo2', 'value' => '16907191913.png'],
            ['key' => 'socialLogo3', 'value' => '16907194544.png'],
            ['key' => 'paypal', 'value' => '{"status":"0","mode":"test","test_client_id":"snd_cl_id_xxxxxxxxxxxxx","test_secret_key":"snd_cl_sid_xxxxxxxxxxxx","live_client_id":"lv_cl_id_xxxxxxxxxxxxxxx","live_secret_key":"lv_cl_sid_xxxxxxxxxxxxxx"}'],
            ['key' => 'stripe', 'value' => '{"status":"0","mode":"test","test_key":"pk_test_xxxxxxxxxxxxx","test_secret_key":"sk_test_xxxxxxxxxxxxxx","public_live_key":"pk_live_xxxxxxxxxxxxxx","secret_live_key":"sk_live_xxxxxxxxxxxxxx"}'],
            ['key' => 'razorpay', 'value' => '{"status":"0","mode":"test","test_key":"rzp_test_xxxxxxxxxxxxx","test_secret_key":"rzs_test_xxxxxxxxxxxxx","live_key":"rzp_live_xxxxxxxxxxxxx","live_secret_key":"rzs_live_xxxxxxxxxxxxx","theme_color":"#00ffff"}'],
            ['key' => 'paytm', 'value' => '{"status":"0","mode":"test","test_merchant_id":"tm_id_xxxxxxxxxxxx","test_merchant_key":"tm_key_xxxxxxxxxx","live_merchant_id":"lv_mid_xxxxxxxxxxx","live_merchant_key":"lv_key_xxxxxxxxxxx","environment":"provide-a-environment","merchant_website":"merchant-website","channel":"provide-channel-type","industry_type":"provide-industry-type"}'],
            ['key' => 'flutterwave', 'value' => '{"status":"0","mode":"test","test_key":"flwp_test_xxxxxxxxxxxxx","test_secret_key":"flws_test_xxxxxxxxxxxxx","test_encryption_key":"flwe_test_xxxxxxxxxxxxx","public_live_key":"flwp_live_xxxxxxxxxxxxxx","secret_live_key":"flws_live_xxxxxxxxxxxxxx","encryption_live_key":"flwe_live_xxxxxxxxxxxxxx"}'],
            ['key' => 'front_logo', 'value' => '17880721075.png'],
            ['key' => 'features_title', 'value' => 'Features'],
            ['key' => 'features_subtitle', 'value' => 'Make your application more advanced with Ekattor 8'],
            ['key' => 'off_pay_ins_text', 'value' => 'You can make payments using your mobile banking number.'],
            ['key' => 'off_pay_ins_file', 'value' => ''],
            ['key' => 'recaptcha_site_key', 'value' => null],
            ['key' => 'recaptcha_secret_key', 'value' => null],
            ['key' => 'recaptcha_switch_value', 'value' => 'No'],
        ];

        foreach ($settings as $setting) {
            DB::table('global_settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
