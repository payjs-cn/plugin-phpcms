<?php
defined('IN_PHPCMS') or exit('No permission resources.');
if (isset($set_modules) && $set_modules == TRUE) {
    $i = isset($modules) ? count($modules) : 0;

    $modules[$i]['code']    = basename(__FILE__, '.class.php');
    $modules[$i]['name']    = 'Payjs支付-JSAPI';
    $modules[$i]['desc']    = 'Payjs支付-JSAPI';
    $modules[$i]['is_cod']  = '0';
    $modules[$i]['is_online']  = '1';
    $modules[$i]['author']  = '';
    $modules[$i]['website'] = '';
    $modules[$i]['version'] = '1.0.0';
    $modules[$i]['config']  = array(
        array('name' => 'payjs_mchid', 'type' => 'text', 'value' => ''),
        array('name' => 'payjs_key', 'type' => 'text', 'value' => ''),
    );

    return;
}
pc_base::load_app_class('pay_abstract', '', '0');

class Payjs_jsapi extends paymentabstract
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
            'attach' => __CLASS__
        );
        if (empty($_POST['openid'])) {
            $openid_url = "https://payjs.cn/api/openid?mchid={$this->config['payjs_mchid']}&callback_url=" . urlencode(SITE_PROTOCOL . SITE_URL . '/api/payjs.php?act=jsapi&params=' . base64_encode(json_encode($_POST)));
            header('Location: ' . $openid_url);
            exit;
        }
        $params['openid'] = $_POST['openid'];
        $result = $this->sdk->jsapi($params);
        if (!$result || !$result['return_code']) {
            return '<font color="red">支付网关请求失败' . (!empty($result['return_msg']) ? ':' . $result['return_msg'] : '') . '</font>';
        }
        $jsapi = json_encode($result['jsapi']);
        return <<<EOF
            <input type="button" value="确定支付" class="button J_jsapiPay">
            <script type="text/javascript">
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
                } else if (document.attachEvent) {
                    document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
                    document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
                }
            }
            function onBridgeReady() {
                WeixinJSBridge.call('hideOptionMenu');
            }
            $('.J_jsapiPay').on('click', function () {
                WeixinJSBridge.invoke(
                    'getBrandWCPayRequest', {$jsapi},
                    function (res) {
                        if (res.err_msg == "get_brand_wcpay_request:ok") {
                            WeixinJSBridge.call('closeWindow');
                            location.href = '/index.php?m=member';
                        }
                    }
                );
            });
            </script>            
EOF;
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
