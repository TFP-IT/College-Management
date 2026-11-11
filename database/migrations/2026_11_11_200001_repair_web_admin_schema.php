<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Some databases were created from an older schema: `enrollment` instead of `enrollments`
 * and several columns the web admin reads are missing. Only adds what is absent.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'schools' => [
            'running_session' => ['integer'], 'school_currency' => ['string'], 'currency_position' => ['string'],
            'school_logo' => ['string'], 'email_title' => ['string'], 'email_details' => ['string'],
            'warning_text' => ['string'], 'socialLink1' => ['string'], 'socialLink2' => ['string'],
            'socialLink3' => ['string'], 'email_logo' => ['string'], 'socialLogo1' => ['string'],
            'socialLogo2' => ['string'], 'socialLogo3' => ['string'], 'off_pay_ins_text' => ['string'],
            'off_pay_ins_file' => ['string'],
        ],
        'sessions' => ['school_id' => ['integer']],
        'subscriptions' => ['studentLimit' => ['string'], 'status' => ['integer', 0]],
        'packages' => ['studentLimit' => ['string'], 'features' => ['string']],
        'exams' => ['exam_category_id' => ['integer'], 'room_number' => ['string']],
        'student_fee_managers' => [
            'amount' => ['integer', 0], 'discounted_price' => ['integer'],
            'parent_id' => ['integer'], 'document_image' => ['string'],
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('enrollments') && Schema::hasTable('enrollment')) {
            Schema::rename('enrollment', 'enrollments');
        }

        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('class_id');
                $table->integer('section_id');
                $table->integer('school_id');
                $table->integer('department_id')->nullable();
                $table->integer('session_id');
                $table->timestamps();
            });
        }

        foreach (self::COLUMNS as $tableName => $columns) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column => $definition) {
                    if (Schema::hasColumn($tableName, $column)) {
                        continue;
                    }
                    [$type] = $definition;
                    $col = $table->{$type}($column);
                    array_key_exists(1, $definition) ? $col->default($definition[1]) : $col->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Repairs are not rolled back: the columns belong to the original schema.
    }
};
