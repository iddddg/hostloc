<?php

namespace App\Console\Commands;

use App\Http\Controllers\Hostloc\CheckInController;
use App\Models\HostlocAccount;
use Illuminate\Console\Command;

class CheckInHostloc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostloc:checkin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hostloc 签到';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(CheckInController $checkInController)
    {
        $hour = date('H');
        $today = date('Y-m-d', time()) . ' 00:00:00';

        // 今日已签到账号ID
        $ids = HostlocAccount::where('last_check_in_time', '>=', $today)
            ->where('state', '=', '1')
            ->pluck('id');


        // 晚上9点发送当日目前签到情况
        if ($hour == '21'){
            // TODO
        }

        // 今日未签到账号
        $accounts = HostlocAccount::whereNotIn('id', $ids)->get();
        foreach ($accounts as $account) {
            $result = $checkInController->checkIn($account);

            // 签到成功
            if ($result){
                $account->name = $result['name'];
                $account->avatar_url = $result['avatar_url'];
                $account->group = $result['group'];
                $account->money = $result['money'];
                $account->prestige = $result['prestige'];
                $account->integral = $result['integral'];
            }

            $account->last_check_in_time = date('Y-m-d H:i:s');
            $account->state =  $result ? '1' : '2';
            $account->save();

            sleep(rand(10, 30));
        }
    }
}
