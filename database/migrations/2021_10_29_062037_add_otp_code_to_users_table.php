<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtpCodeToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code', 10)
                ->nullable(true)
                ->default(null)
                ->after('two_factor_confirmed_at');

            $table->timestamp('otp_expiration')
                ->nullable(true)
                ->default(null)
                ->after('otp_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('otp_code');
            $table->dropColumn('otp_expiration');
        });
    }
}
