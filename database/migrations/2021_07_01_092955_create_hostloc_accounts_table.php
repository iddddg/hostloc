<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostlocAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hostloc_accounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->string('username')->unique()->comment('用户名');
            $table->string('password')->comment('密码');
            $table->string('name')->nullable()->comment('名称');
            $table->string('avatar_url')->nullable()->comment('头像链接');
            $table->string('group')->nullable()->comment('用户组');
            $table->integer('money')->nullable()->comment('金钱');
            $table->integer('prestige')->nullable()->comment('威望');
            $table->integer('integral')->nullable()->comment('积分');
            $table->timestamp('last_check_in_time', 0)->nullable()->comment('最后签到时间');
            $table->enum('state', [0, 1, 2])->comment('状态：0新添加、1签到成功、2签到失败');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hostloc_accounts');
    }
}
