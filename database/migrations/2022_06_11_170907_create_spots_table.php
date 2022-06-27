<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spots', function (Blueprint $table) {
            $table->bigIncrements('spots_id');
            $table->integer('users_id');
            $table->string('spots_name');
            $table->string('spots_latitude');
            $table->string('spots_longitude');
            $table->string('spots_address');
            $table->string('spots_url');
            $table->string('spots_status')->defalut('None');
            $table->integer('spots_count')->defalut(0);
            $table->integer('spots_over_time')->defalut(0);
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spots');
    }
}
