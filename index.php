<?php
// 设置默认时区
date_default_timezone_set("PRC");
// 留空勿填
$cookie = "";

// 多账号复制多个,一行一个账号密码
// $account[num] = ['账号', '密码'];
$account[] = ['账号1', 'password'];
$account[] = ['账号2', 'password1'];
// 【注意】增加/删除/修改账号等操作请删除代码目录下的account.dat(如果有的话)文件

// 签到失败通知
// Server酱 SCKEY 获取方法：http://sc.ftqq.com
$ft_sckey = "******";
// TgBot SCKEY 获取方法：Telegram关注 @onePushBot 发送/start即可获取
$tg_sckey = "******";

// Go
$need_brush = need_brush($account);
brush($need_brush);

// 获取今日需要刷分的账号
function need_brush($account)
{
    $file = 'account.dat';
    if (file_exists($file)) {
        $dat = json_decode(file_get_contents($file), 1);
    }else{ 
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
        } else {
            echo "登录失败（{$value['username']}）\n";
            echo date("Y-m-d H:i:s\n");
            echo "----------------------------------------------------------\n";
            continue;
        }
        echo "初始信息（用户组:{$data['group']},金钱:{$data['money']},威望:{$data['prestige']},积分:{$data['point']}）\n";
        echo "刷分中 ";
        for ($i = 31180; $i < 31210; $i++) {
            $html = http_get(str_replace('*', $i, 'https://www.hostloc.com/space-uid-*.html'));
            echo $i == 31209 ? "+ 完成\n" : "+";
            sleep(rand(5, 10));
        }
        $data = get_info();
        echo "结束信息（用户组:{$data['group']},金钱:{$data['money']},威望:{$data['prestige']},积分:{$data['point']}）\n";
        echo date("Y-m-d H:i:s\n");
        echo "----------------------------------------------------------\n";

        success($key);
        unset($need_brush[$key]);
        sleep(rand(5, 10));
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
    $login = http_post('https://www.hostloc.com/member.php?mod=logging&action=login&loginsubmit=yes&infloat=yes&lssubmit=yes&inajax=1', $loginData, $cookie);

    preg_match("/cookie=\"(\w*?)\=(\w*)/", $login, $cookie);
    preg_match("/href=\"(.*?)\"/", $login, $url);

    if (!empty($cookie[1])) {
        $cookie = "{$cookie[1]}={$cookie[2]};";
        http_post($url[1], $loginData, $cookie);
    }

    $data = get_info();

    return $data;
}

// 获取个人信息
function get_info()
{
    global $cookie;
    $data = [];
    $html = http_get('https://www.hostloc.com/home.php?mod=spacecp&ac=credit', $cookie);

    preg_match('/\<a.*?title="访问我的空间">(.*)\<\/a\>/', $html, $preg);
    if (!empty($preg[1])) {
        $data['username'] = $preg[1];
    } else {
        $data['username'] = '';
    }

    preg_match("/>用户组: (.*?)<\/a>/", $html, $preg);
    if (!empty($preg[1])) {
        $data['group'] = $preg[1];
    } else {
        $data['group'] = '?';
    }

    preg_match("/金钱: <\/em>(\d+)/", $html, $preg);
    if (!empty($preg[1])) {
        $data['money'] = $preg[1];
    } else {
        $data['money'] = '?';
    }

    preg_match("/威望: <\/em>(\d+)/", $html, $preg);
    if (!empty($preg[1])) {
        $data['prestige'] = $preg[1];
    } else {
        $data['prestige'] = '?';
    }

    preg_match("/积分: (\d+)<\/a>/", $html, $preg);
    if (!empty($preg[1])) {
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
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.hostloc.com/');
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
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.hostloc.com/');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
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
    global $ft_sckey;
    global $tg_sckey;

    if (date('H') == 21) {
        $username = array_column($err_account, 'username');
        $title = 'Hostloc签到失败';
        $content = '您的账号（' . implode('，', $username) . '），签到失败';
        
        // Server酱 通知
        if ($ft_sckey) {
            http_post("https://sc.ftqq.com/" . $ft_sckey . ".send", [
                "text" => $title,
                "desp" => $content
            ]);
        }

        // Telegram 通知
        if ($tg_sckey) {
            http_post("https://asorry.com/bot.php", [
                "method" => "send",
                "content" => $title . "\n" .$content,
                "sckey" => $tg_sckey
            ]);
        }
    }
}