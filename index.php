<?php
// 设置默认时区
date_default_timezone_set("PRC");
// 引入配置文件
$config = require __DIR__ . '/config.php';

// 初始换环境变量
init_env();
// 开始刷分
brush();

/**
 * 刷分（每天登录+访问别人空间）
 * @return void
 */
function brush()
{
    global $config;
    $accounts = $config['accounts'];
    // 判断是否为Github Actions环境
    $is_actions = (bool)getenv('LOC_ACCOUNTS');

    $err_accounts = [];
    foreach ($accounts as $key => $account) {
        if ($account['last_brush'] === date("Y-m-d")) {
            continue;
        }
        echo "----------------------------------------------------------\n";
        $data = login($account['username'], $account['password']);
        if ($data['username'] !== $account['username']) {
            echo "登录失败（" . ($is_actions ? mb_substr($account['username'], 0, 1) . '***' : $account['username']) . "）\n";
            echo date("Y-m-d H:i:s\n");
            echo "----------------------------------------------------------\n";
            $err_accounts[] = $account;
            continue;
        }
        echo "登录成功（" . ($is_actions ? mb_substr($account['username'], 0, 1) . '***' : $account['username']) . "）\n";
        if (!$is_actions) {
            echo "初始信息（用户组:{$data['group']},金钱:{$data['money']},威望:{$data['prestige']},积分:{$data['point']}）\n";
        }
        echo "刷分中 ";
        for ($i = 31180; $i < 31210; $i++) {
            http_get(str_replace('*', $i, 'https://hostloc.com/space-uid-*.html'));
            echo $i == 31209 ? "+ 完成\n" : "+";
            sleep(rand(5, 10));
        }
        $data = get_info();
        if (!$is_actions) {
            echo "结束信息（用户组:{$data['group']},金钱:{$data['money']},威望:{$data['prestige']},积分:{$data['point']}）\n";
        }
        echo date("Y-m-d H:i:s\n");
        echo "----------------------------------------------------------\n";
        $accounts[$key]['last_brush'] = date("Y-m-d");
        sleep(rand(5, 30));
    }

    // 更新最后刷分日期
    $config['accounts'] = $accounts;
    $data = var_export($config, true);
    file_put_contents(__DIR__ . '/config.php', "<?php\nreturn $data;");

    // 发送当天刷分失败通知
    notice($err_accounts);
}


/**
 * 登录
 * @param $username
 * @param $password
 * @return array
 */
function login($username, $password)
{
    global $cookie;
    $cookie = '';
    $loginData = array(
        'fastloginfield' => 'username',
        'username' => $username,
        'cookietime' => 2592000,
        'password' => $password,
        // 'formhash' => '751cf73e',
        'quickforward' => 'yes',
        'handlekey' => 'ls'
    );
    $cc_cookie = get_cc_cookie();
    $response = http_post('https://hostloc.com/member.php?mod=logging&action=login&loginsubmit=yes&infloat=yes&lssubmit=yes&inajax=1', $loginData, $cc_cookie);
    // 获取Cookie
    preg_match_all('/set-cookie: (.*?);/i', $response, $matches);
    if (count($cc_cookie) > 0) {
        $cookie = http_build_query($cc_cookie, '', ';') . ';';
    }
    $cookie .= implode(';', $matches[1]);
    return get_info();
}

/**
 * 获取CC防护的Cookie参数
 * @return array
 */
function get_cc_cookie()
{
    $cc_cookie = [];
    // 检测防CC机制的参数
    $data = check_cc();
    // 开启了防CC机制
    if (count($data) === 4) {
        // 密钥
        $a = hex2bin($data['a']);
        // 初始化向量 (IV)
        $b = hex2bin($data['b']);
        // 加密后的数据
        $c = hex2bin($data['c']);
        // AES CBC 解密
        $value = openssl_decrypt($c, 'AES-128-CBC', $a, OPENSSL_NO_PADDING, $b);

        $cc_cookie[$data['cookie']] = bin2hex($value);
    }

    return $cc_cookie;
}


/**
 * 检查是否开启CC防护
 * @return array
 */
function check_cc()
{
    $data = [];
    $html = file_get_contents('https://hostloc.com/forum.php');

    preg_match_all("/toNumbers\(\"(.*?)\"\)/", $html, $matches);
    if (isset($matches[1]) && count($matches[1]) === 3) {
        $data['a'] = $matches[1][0];
        $data['b'] = $matches[1][1];
        $data['c'] = $matches[1][2];
    }

    preg_match("/cookie=\"(.*?)=\"/", $html, $matches);
    if (isset($matches[1])) {
        $data['cookie'] = $matches[1];
    }

    return $data;
}

/**
 * 获取个人信息
 * @return array
 */
function get_info()
{
    $data = [];
    $html = http_get('https://hostloc.com/home.php?mod=spacecp&ac=credit');
    preg_match('/<a.*?title="访问我的空间">(.*)<\/a>/', $html, $matches);
    if (isset($matches[1])) {
        $data['username'] = $matches[1];
    } else {
        $data['username'] = '';
    }

    preg_match("/>用户组: (.*?)<\/a>/", $html, $matches);
    if (isset($matches[1])) {
        $data['group'] = $matches[1];
    } else {
        $data['group'] = '?';
    }

    preg_match("/金钱: <\/em>(\d+)/", $html, $matches);
    if (isset($matches[1])) {
        $data['money'] = $matches[1];
    } else {
        $data['money'] = '?';
    }

    preg_match("/威望: <\/em>(\d+)/", $html, $matches);
    if (isset($matches[1])) {
        $data['prestige'] = $matches[1];
    } else {
        $data['prestige'] = '?';
    }

    preg_match("/积分: (\d+)<\/a>/", $html, $matches);
    if (isset($matches[1])) {
        $data['point'] = $matches[1];
    } else {
        $data['point'] = '?';
    }

    return $data;
}

/**
 * GET请求
 * @param $url
 * @return bool|string
 */
function http_get($url)
{
    global $cookie;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
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

/**
 * POST请求
 * @param $url
 * @param $data
 * @return bool|string
 */
function http_post($url, $data, $cookie = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_COOKIE, http_build_query($cookie, '', '; '));
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

/**
 * 初始化环境变量到配置文件
 * @return void
 */
function init_env()
{
    global $config;

    // 获取环境变量中的账号密码（格式user1@@@pass1---user2@@@pass2）
    $env_loc_accounts = getenv('LOC_ACCOUNTS');
    if ($env_loc_accounts) {
        $new_accounts = [];
        foreach (explode("---", $env_loc_accounts) as $env_account) {
            $account_parts = explode("@@@", $env_account);
            if (count($account_parts) !== 2) {
                continue;
            }
            $new_accounts[$account_parts[0]] = array(
                'username' => $account_parts[0],
                'password' => $account_parts[1],
                'last_brush' => '',
            );
        }
        // 更新和添加账户
        foreach ($config['accounts'] as $account) {
            if (isset($new_accounts[$account['username']])) {
                $new_accounts[$account['username']]['last_brush'] = $account['last_brush'];
            }
        }
        // 最新的账号密码
        $config['accounts'] = array_values($new_accounts);
    }

    // 获取环境变量中的TG推送Key
    $env_tg_push_key = getenv('TG_PUSH_KEY');
    if ($env_tg_push_key) {
        $config['tg_push_key'] = $env_tg_push_key;
    }
}

/**
 * 通知
 * @param $err_accounts
 * @return void
 */
function notice($err_accounts)
{
    global $config;
    $tg_push_key = $config['tg_push_key'];

    // 最后一次执行且有刷分失败的账号且TG推送Key不为空才推送
    if (date('G') < 18 || empty($err_accounts) || empty($tg_push_key)) {
        return;
    }
    $username = array_column($err_accounts, 'username');
    $title = 'Hostloc 刷分失败';
    $content = '账号（' . implode('，', $username) . '）刷分失败，请检查账号配置或程序';
    $data = array(
        "key" => $tg_push_key,
        "text" => $title . "\n" . $content
    );
    // Telegram 通知
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