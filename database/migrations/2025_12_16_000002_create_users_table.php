<?php
// database/migrations/2024_01_01_000002_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('name', 191);
            $table->string('email', 191)->nullable();
            $table->string('password', 191);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->boolean('two_factor_confirmed')->default(0);
            $table->boolean('two_factor_email_confirmed')->default(0);
            $table->string('image', 191)->nullable();
            $table->integer('country_phonecode')->nullable();
            $table->string('mobile', 191)->nullable();
            $table->enum('gender', ['male', 'female', 'others'])->default('male');
            $table->enum('salutation', ['mr', 'mrs', 'miss', 'dr', 'sir', 'madam'])->nullable();
            $table->string('locale', 191)->default('en');
            $table->enum('status', ['active', 'deactive'])->default('active');
            $table->enum('login', ['enable', 'disable'])->default('enable');
            $table->text('onesignal_player_id')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->boolean('email_notifications')->default(1);
            $table->unsignedInteger('country_id')->nullable();
            $table->boolean('dark_theme')->default(0);
            $table->boolean('rtl')->default(0);
            $table->enum('two_fa_verify_via', ['email', 'google_authenticator', 'both'])->nullable();
            $table->string('two_factor_code', 191)->nullable()->comment('when authenticator is email');
            $table->dateTime('two_factor_expires_at')->nullable();
            $table->boolean('admin_approval')->default(1);
            $table->boolean('permission_sync')->default(1);
            $table->boolean('google_calendar_status')->default(1);
            $table->rememberToken();
            $table->timestamps();
            $table->boolean('customised_permissions')->default(0);
            $table->string('stripe_id', 191)->nullable()->index();
            $table->string('pm_type', 191)->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->text('headers')->nullable();
            $table->string('register_ip', 191)->nullable();
            $table->text('location_details')->nullable();
            $table->date('inactive_date')->nullable();
            $table->string('twitter_id', 191)->nullable();
            $table->unsignedInteger('is_client_contact')->nullable()->index();

            $table->unique(['email', 'company_id']);
            // $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};