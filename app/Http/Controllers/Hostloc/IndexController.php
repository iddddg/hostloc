<?php

namespace App\Http\Controllers\Hostloc;

use App\Http\Controllers\Controller;
use App\Models\HostlocAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class IndexController extends Controller
{
    /**
     * 仪表盘
     * @return \Inertia\Response
     */
    public function index()
    {
        $user_id = Auth::id();
        $hostloc_accounts = HostlocAccount::where('user_id', $user_id)->get();

        return Inertia::render('Dashboard', [
            'hostlocAccounts' => $hostloc_accounts
        ]);
    }

    /**
     * 储存账号
     * @param Request $request
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * 更新账号信息
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除账号
     * @param $id
     */
    public function destroy($id)
    {
        $hostloc_account = HostlocAccount::find($id);
        $user_id = Auth::id();

        if ($hostloc_account->user_id != $user_id) {
            return Redirect::route('dashboard');
        }

        $hostloc_account->delete();
        return Redirect::route('dashboard');
    }
}
