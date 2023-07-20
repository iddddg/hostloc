<?php
// 设置默认时区
date_default_timezone_set("PRC");

// ↓↓↓↓↓↓↓↓↓↓配置开始（修改配置后，如果当前目录下有account.dat文件需删除！）↓↓↓↓↓↓↓↓↓↓

// 多账号复制多个，一行一个账号密码
$account[] = ['账号1', '密码1'];
$account[] = ['账号2', '密码2'];
// 签到失败通知KEY 获取方法：Telegram关注 @onePushBot 发送/key即可获取
$tg_push_key = '';

// ↑↑↑↑↑↑↑↑↑↑配置结束（修改配置后，如果当前目录下有account.dat文件需删除！）↑↑↑↑↑↑↑↑↑↑

// Go
$cookie = "";
$need_brush = need_brush($account);
brush($need_brush);

// 获取今日需要刷分的账号
function need_brush($account)
{
    $file = 'account.dat';
    if (file_exists($file)) {
        $dat = json_decode(file_get_contents($file), 1);
    } else {
        foreach ($account as $key => $value) {
            $dat[$key] = [
                'username' => $value[0],
                'password' => $value[1],
                'status' => 'err',
                'date' => date('Y-m-d')
            ];
        }
        file_put_contents($file, json_encode($dat));
    }

    foreach ($dat as $key => $value) {
        if ($value['status'] == 'suc' && $value['date'] == date('Y-m-d')) {
            unset($dat[$key]);
        }
    }

    return $dat;
}

// 刷分
function brush($need_brush)
{
    foreach ($need_brush as $key => $value) {
        echo "----------------------------------------------------------\n";
        $data = login($value['username'], $value['password']);
        if ($data['username'] == $value['username']) {
            echo "登录成功（{$value['username']}）\n";
            echo "初始信息（用户组:{$data['group']},金钱:{$data['money']},威望:{$data['prestige']},积分:{$data['point']}）\n";
            echo "刷分中 ";
            for ($i = 31180; $i < 31210; $i++) {
                $html = http_get(str_replace('*', $i, 'https://hostloc.com/space-uid-*.html'));
                echo $i == 31209 ? "+ 完成\n" : "+";
                sleep(rand(5, 10));
            }
            $data = get_info();
            echo "结束信息（用户组:{$data['group']},金钱:{$data['money']},威望:{$data['prestige']},积分:{$data['point']}）\n";
            echo date("Y-m-d H:i:s\n");
            echo "----------------------------------------------------------\n";
            success($key);
            unset($need_brush[$key]);
        } else {
            echo "登录失败（{$value['username']}）\n";
            echo date("Y-m-d H:i:s\n");
            echo "----------------------------------------------------------\n";
        }
        sleep(rand(5, 30));
    }
    notice($need_brush);
}

// 登录
function login($username, $password)
{
    global $cookie;
    $loginData = array(
        "username" => $username,
        "password" => $password,
        "fastloginfield" => "username",
        "quickforward" => "yes",
        "handlekey" => "ls",
        'cookietime' => 2592000
    );
    $login = http_post('https://hostloc.com/member.php?mod=logging&action=login&loginsubmit=yes&infloat=yes&lssubmit=yes&inajax=1', $loginData, $cookie);
    // preg_match("/cookie=\"(\w*?)\=(\w*)/", $login, $cookie);
    // preg_match("/href=\"(.*?)\"/", $login, $url);

    // if (!empty($cookie[1])) {
    //     $cookie = "{$cookie[1]}={$cookie[2]};";
    //     http_post($url[1], $loginData, $cookie);
    // }
    return get_info();
}

// 获取个人信息
function get_info()
{
    global $cookie;
    $data = [];
    $html = http_get('https://hostloc.com/home.php?mod=spacecp&ac=credit', $cookie);

    preg_match('/\<a.*?title="访问我的空间">(.*)\<\/a\>/', $html, $preg);
    if (isset($preg[1])) {
        $data['username'] = $preg[1];
    } else {
        $data['username'] = '';
    }

    preg_match("/>用户组: (.*?)<\/a>/", $html, $preg);
    if (isset($preg[1])) {
        $data['group'] = $preg[1];
    } else {
        $data['group'] = '?';
    }

    preg_match("/金钱: <\/em>(\d+)/", $html, $preg);
    if (isset($preg[1])) {
        $data['money'] = $preg[1];
    } else {
        $data['money'] = '?';
    }

    preg_match("/威望: <\/em>(\d+)/", $html, $preg);
    if (isset($preg[1])) {
        $data['prestige'] = $preg[1];
    } else {
        $data['prestige'] = '?';
    }

    preg_match("/积分: (\d+)<\/a>/", $html, $preg);
    if (isset($preg[1])) {
        $data['point'] = $preg[1];
    } else {
        $data['point'] = '?';
    }

    return $data;
}

// GET请求
function http_get($url)
{
    global $cookie;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'hostloc.cookie');
    if (!empty($cookie)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36');
    curl_setopt($ch, CURLOPT_REFERER, 'https://hostloc.com/');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// POST请求
function http_post($url, $data)
{
    global $cookie;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'hostloc.cookie');
    if (!empty($cookie)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36');
    curl_setopt($ch, CURLOPT_REFERER, 'https://hostloc.com/');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// 成功更新状态和日期
function success($key)
{
    $file = 'account.dat';
    $dat = json_decode(file_get_contents($file), 1);
    $dat[$key]['status'] = 'suc';
    $dat[$key]['date'] = date('Y-m-d');
    file_put_contents($file, json_encode($dat));
}

// 通知
function notice($err_account)
{
    global $tg_push_key;
    if (!empty($err_account) && date('H') == 21) {
        $username = array_column($err_account, 'username');
        $title = 'Hostloc签到失败';
        $content = '您的账号（' . implode('，', $username) . '），签到失败';
        $data = array(
            "key" => $tg_push_key,
            "text" => $title . "\n" . $content
        );
        // Telegram 通知
        if ($tg_push_key) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://tg-bot.t04.net/push',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 600,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            curl_exec($curl);
            curl_close($curl);
        }
    }
}
