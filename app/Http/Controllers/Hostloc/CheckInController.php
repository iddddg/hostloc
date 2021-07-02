<?php

namespace App\Http\Controllers\Hostloc;

use App\Http\Controllers\Controller;

class CheckInController extends Controller
{
    public function checkIn($account)
    {
        // 登录
        $info = $this->login($account['username'], $account['password']);
        if (!$info) {
            return [];
        }

        // 开始签到
        for ($i = 31190; $i < 31210; $i++) {
            $this->http_get(str_replace('*', $i, config('hostloc.space_url')));
            sleep(rand(10, 30));
        }

        return $this->get_info();
    }


    // 登录
    public function login($username, $password)
    {
        $loginData = array(
            'username' => $username,
            'password' => $password,
            'fastloginfield' => filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username',
            'quickforward' => 'yes',
            'handlekey' => 'ls',
            'cookietime' => 2592000
        );
        $this->http_post(config('hostloc.login_url'), $loginData);
        return $this->get_info();
    }

    // 获取个人信息
    function get_info()
    {
        $html = $this->http_get(config('hostloc.integral_url'));

        preg_match('/\<a.*?title="访问我的空间">(.*)\<\/a\>/', $html, $name);
        if (empty($name[1])) {
            return [];
        }

        preg_match('/.html">\<img src="(.*)" \/>/', $html, $avatar_url);
        if (empty($avatar_url[1])) {
            return [];
        }

        preg_match("/>用户组: (.*?)<\/a>/", $html, $group);
        if (empty($group[1])) {
            return [];
        }

        preg_match("/金钱: <\/em>(\d+)/", $html, $money);
        if (empty($money[1])) {
            return [];
        }

        preg_match("/威望: <\/em>(\d+)/", $html, $prestige);
        if (empty($prestige[1])) {
            return [];
        }

        preg_match("/积分: (\d+)<\/a>/", $html, $integral);
        if (empty($integral[1])) {
            return [];
        }

        return [
            'name' => $name[1],
            'avatar_url' => $avatar_url[1],
            'group' => $group[1],
            'money' => $money[1],
            'prestige' => $prestige[1],
            'integral' => $integral[1]
        ];
    }

    // GET请求
    function http_get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'hostloc.cookie');
        curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36');
        curl_setopt($ch, CURLOPT_REFERER, 'https://hostloc.com/');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    // POST请求
    function http_post($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'hostloc.cookie');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36');
        curl_setopt($ch, CURLOPT_REFERER, 'https://hostloc.com/');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    // 通知
    function notice($err_account)
    {
        $ft_sckey = config('ft_sckey');
        $tg_sckey = config('tg_sckey');


        if (!empty($err_account) && date('H') == 21) {
            $username = array_column($err_account, 'username');
            $title = 'Hostloc签到失败';
            $content = '您的账号（' . implode('，', $username) . '），签到失败';

            // Server酱 通知
            if (!empty($ft_sckey)) {
                $this->http_post('https://sc.ftqq.com/' . $ft_sckey . '.send', [
                    'text' => $title,
                    'desp' => $content
                ]);
            }

            // Telegram 通知
            if (!empty($tg_sckey)) {
                $this->http_post('https://telegram.ddddg.cn/bot.php', [
                    'method' => 'send',
                    'content' => $title . "\n" . $content,
                    'sckey' => $tg_sckey
                ]);
            }
        }
    }
}
