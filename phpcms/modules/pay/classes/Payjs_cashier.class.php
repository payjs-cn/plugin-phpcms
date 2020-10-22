<?php
defined('IN_PHPCMS') or exit('No permission resources.');
if (isset($set_modules) && $set_modules == TRUE) {
    $i = isset($modules) ? count($modules) : 0;

    $modules[$i]['code']    = basename(__FILE__, '.class.php');
    $modules[$i]['name']    = 'PAYJS-微信收银台';
    $modules[$i]['desc']    = 'PAYJS-微信收银台';
    $modules[$i]['is_cod']  = '0';
    $modules[$i]['is_online']  = '1';
    $modules[$i]['author']  = 'PAYJS';
    $modules[$i]['website'] = '';
    $modules[$i]['version'] = '1.0.0';
    $modules[$i]['config']  = array(
        array('name' => 'payjs_mchid', 'type' => 'text', 'value' => ''),
        array('name' => 'payjs_key', 'type' => 'text', 'value' => ''),
    );
    return;
}
pc_base::load_app_class('pay_abstract', '', '0');

class Payjs_cashier extends paymentabstract
{
    public function __construct($config = array())
    {
        if (!empty($config)) $this->set_config($config);
        pc_base::load_app_class('payjs_sdk', 'pay', 0);
        $this->sdk = new payjs_sdk($this->config['payjs_mchid'], $this->config['payjs_key']);
    }

    public function getpreparedata()
    {
        $params = array(
            'total_fee' => intval($this->product_info['price'] * 100),
            'out_trade_no' => $this->order_info['id'],
            'body' => $this->product_info['body'],
            'notify_url' => SITE_PROTOCOL . SITE_URL . '/api/payjs.php',
            'time_expire' => date('YmdHis', SYS_TIME + 1800),
            'callback_url' => SITE_PROTOCOL . SITE_URL . '/index.php?m=member',
            'attach' => __CLASS__
        );
        $this->config['gateway_method'] = 'GET';
        $this->config['gateway_url'] = 'https://payjs.cn/api/cashier';
        return $this->sdk->sign($params);
    }

    /**
     * GET接收数据
     */
    public function receive()
    {
    }

    /**
     * 异步通知
     *
     * @return void
     */
    public function notify()
    {
        unset($_POST['code']);
        pc_base::load_app_class('payjs_sdk', 'pay', 0);
        if (is_array($this->sdk->notify())) {
            return array(
                'order_id' => $_POST['out_trade_no'],
                'price' => round($_POST['total_fee'] / 100),
                'order_status' => 0
            );
        } else return false;
    }

    /**
     * 相应服务器应答状态
     * @param $result
     */
    public function response($result)
    {
        if (FALSE == $result) echo 'fail';
        else echo 'success';
    }
}
