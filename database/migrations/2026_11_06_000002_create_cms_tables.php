<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 100)->unique();
            $table->string('title', 255);
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cms_notices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('body');
            $table->date('publish_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cms_gallery_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->string('category', 100)->nullable()->index();
            $table->string('image_path', 500);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cms_staff_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('designation', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->enum('group', ['GOVERNING_BODY', 'TEACHER', 'STAFF'])->index();
            $table->string('photo_path', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_staff_members');
        Schema::dropIfExists('cms_gallery_items');
        Schema::dropIfExists('cms_notices');
        Schema::dropIfExists('cms_pages');
    }
};
