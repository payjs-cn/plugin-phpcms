<?php
define('PHPCMS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . '../');
include PHPCMS_PATH . 'phpcms/base.php';
$param = pc_base::load_sys_class('param');
//检查订单状态
if ($_GET['act'] == 'check_trade') {
    $trade = pc_base::load_model('pay_account_model')->get_one(array('trade_sn' => $_GET['trade_sn']));
    exit(json_encode(array('status' => $trade['status'] === 'succ')));
}
//JSAPI openid
elseif ($_GET['act'] == 'jsapi') {
    if (empty($_GET['openid']) || empty($_GET['params'])) {
        header('Location: /index.php');
        exit;
    }
    $params = json_decode(base64_decode($_GET['params']), true);
    $params['openid'] = $_GET['openid'];
    $str = '<form name="gopay" method="post" action="' . SITE_PROTOCOL . SITE_URL . '/index.php?m=pay&c=deposit&a=pay_recharge">';
    foreach ($params as $key => $val) {
        $str .= '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
    }
    $str .= '<input type="submit" value="redirect" style="display:none" ></form><script>document.forms[0].submit();</script>';
    echo $str;
}
//异步通知转发
else {
    //@file_put_contents(CACHE_PATH . 'payjs.log', json_encode($_POST) . "\n\n", 8);
    if (!empty($_POST['attach'])) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, SITE_PROTOCOL . SITE_URL . '/index.php?m=pay&c=respond&a=respond_post&code=' . $_POST['attach']);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HTTP CLIENT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);

        echo curl_exec($ch);
        curl_close($ch);
    }
}
