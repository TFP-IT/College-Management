<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodsSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            ['id' => 1, 'name' => 'offline', 'payment_keys' => '{}', 'image' => 'offline.png', 'status' => 1, 'mode' => 'offline', 'school_id' => null],
            ['id' => 2, 'name' => 'paypal', 'payment_keys' => '{"test_client_id":"snd_cl_id_xxxxxxxxxxxxx","test_secret_key":"snd_cl_sid_xxxxxxxxxxxx","live_client_id":"lv_cl_id_xxxxxxxxxxxxxxx","live_secret_key":"lv_cl_sid_xxxxxxxxxxxxxx"}', 'image' => 'paypal.png', 'status' => 1, 'mode' => 'test', 'school_id' => 1],
            ['id' => 3, 'name' => 'stripe', 'payment_keys' => '{"test_key":"pk_test_xxxxxxxxxxxxx","test_secret_key":"sk_test_xxxxxxxxxxxxxx","public_live_key":"pk_live_xxxxxxxxxxxxxx","secret_live_key":"sk_live_xxxxxxxxxxxxxx"}', 'image' => 'stripe.png', 'status' => 1, 'mode' => 'test', 'school_id' => 1],
            ['id' => 4, 'name' => 'razorpay', 'payment_keys' => '{"test_key":"rzp_test_xxxxxxxxxxxxx","test_secret_key":"rzs_test_xxxxxxxxxxxxx","live_key":"rzp_live_xxxxxxxxxxxxx","live_secret_key":"rzs_live_xxxxxxxxxxxxx","theme_color":"#c7a600"}', 'image' => 'razorpay.png', 'status' => 1, 'mode' => 'test', 'school_id' => 1],
            ['id' => 5, 'name' => 'paytm', 'payment_keys' => '{"test_merchant_id":"tm_id_xxxxxxxxxxxx","test_merchant_key":"tm_key_xxxxxxxxxx","live_merchant_id":"lv_mid_xxxxxxxxxxx","live_merchant_key":"lv_key_xxxxxxxxxxx","environment":"provide-a-environment","merchant_website":"merchant-website","channel":"provide-channel-type","industry_type":"provide-industry-type"}', 'image' => 'paytm.png', 'status' => 1, 'mode' => 'test', 'school_id' => 1],
            ['id' => 6, 'name' => 'flutterwave', 'payment_keys' => '{"test_key":"flwp_test_xxxxxxxxxxxxx","test_secret_key":"flws_test_xxxxxxxxxxxxx","test_encryption_key":"flwe_test_xxxxxxxxxxxxx","public_live_key":"flwp_live_xxxxxxxxxxxxxx","secret_live_key":"flws_live_xxxxxxxxxxxxxx","encryption_live_key":"flwe_live_xxxxxxxxxxxxxx"}', 'image' => 'flutterwave.png', 'status' => 1, 'mode' => 'test', 'school_id' => 1],
            ['id' => 7, 'name' => 'paystack', 'payment_keys' => '{"test_key":"pk_test_xxxxxxxxxx","test_secret_key":"sk_test_xxxxxxxxxxxxxx","public_live_key":"pk_live_xxxxxxxxxxxxxx","secret_live_key":"sk_live_xxxxxxxxxxxxxx"}', 'image' => 'paystack.png', 'status' => 1, 'mode' => 'test', 'school_id' => 1],
            ['id' => 8, 'name' => 'paypal', 'payment_keys' => '{"test_client_id":"snd_cl_id_xxxxxxxxxxxxx","test_secret_key":"snd_cl_sid_xxxxxxxxxxxx","live_client_id":"lv_cl_id_xxxxxxxxxxxxxxx","live_secret_key":"lv_cl_sid_xxxxxxxxxxxxxx"}', 'image' => 'paypal.png', 'status' => 1, 'mode' => 'test', 'school_id' => 2],
            ['id' => 9, 'name' => 'stripe', 'payment_keys' => '{"test_key":"pk_test_xxxxxxxxxxxxx","test_secret_key":"sk_test_xxxxxxxxxxxxxx","public_live_key":"pk_live_xxxxxxxxxxxxxx","secret_live_key":"sk_live_xxxxxxxxxxxxxx"}', 'image' => 'stripe.png', 'status' => 1, 'mode' => 'test', 'school_id' => 2],
            ['id' => 10, 'name' => 'razorpay', 'payment_keys' => '{"test_key":"rzp_test_xxxxxxxxxxxxx","test_secret_key":"rzs_test_xxxxxxxxxxxxx","live_key":"rzp_live_xxxxxxxxxxxxx","live_secret_key":"rzs_live_xxxxxxxxxxxxx","theme_color":"#c7a600"}', 'image' => 'razorpay.png', 'status' => 1, 'mode' => 'test', 'school_id' => 2],
            ['id' => 11, 'name' => 'paytm', 'payment_keys' => '{"test_merchant_id":"tm_id_xxxxxxxxxxxx","test_merchant_key":"tm_key_xxxxxxxxxx","live_merchant_id":"lv_mid_xxxxxxxxxxx","live_merchant_key":"lv_key_xxxxxxxxxxx","environment":"provide-a-environment","merchant_website":"merchant-website","channel":"provide-channel-type","industry_type":"provide-industry-type"}', 'image' => 'paytm.png', 'status' => 1, 'mode' => 'test', 'school_id' => 2],
            ['id' => 12, 'name' => 'flutterwave', 'payment_keys' => '{"test_key":"flwp_test_xxxxxxxxxxxxx","test_secret_key":"flws_test_xxxxxxxxxxxxx","test_encryption_key":"flwe_test_xxxxxxxxxxxxx","public_live_key":"flwp_live_xxxxxxxxxxxxxx","secret_live_key":"flws_live_xxxxxxxxxxxxxx","encryption_live_key":"flwe_live_xxxxxxxxxxxxxx"}', 'image' => 'flutterwave.png', 'status' => 1, 'mode' => 'test', 'school_id' => 2],
            ['id' => 13, 'name' => 'paystack', 'payment_keys' => '{"test_key":"pk_test_xxxxxxxxxx","test_secret_key":"sk_test_xxxxxxxxxxxxxx","public_live_key":"pk_live_xxxxxxxxxxxxxx","secret_live_key":"sk_live_xxxxxxxxxxxxxx"}', 'image' => 'paystack.png', 'status' => 1, 'mode' => 'test', 'school_id' => 2],
        ];

        foreach ($methods as $method) {
            DB::table('payment_methods')->updateOrInsert(
                ['id' => $method['id']],
                $method
            );
        }
    }
}
