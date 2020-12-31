<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeSettingsValueNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('voyagerBreads')->table('settings', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('voyagerBreads')->table('settings')->whereNull('value')->update(['value' => '']);

        Schema::connection('voyagerBreads')->table('settings', function (Blueprint $table) {
            $table->text('value')->nullable(false)->change();
        });
    }
}
