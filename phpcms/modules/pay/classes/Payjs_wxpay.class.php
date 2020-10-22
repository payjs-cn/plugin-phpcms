<?php
defined('IN_PHPCMS') or exit('No permission resources.');
if (isset($set_modules) && $set_modules == TRUE) {
    $i = isset($modules) ? count($modules) : 0;
    $modules[$i]['code']    = basename(__FILE__, '.class.php');
    $modules[$i]['name']    = 'PAYJS-微信支付';
    $modules[$i]['desc']    = 'PAYJS-微信支付';
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

class Payjs_wxpay extends paymentabstract
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

        $result = $this->sdk->native($params);
        if (!$result || !$result['return_code']) {
            return '<font color="red">支付网关请求失败' . (!empty($result['return_msg']) ? ':' . $result['return_msg'] : '') . '</font>';
        }
        $check_trade_url = APP_PATH . 'api/payjs.php?act=check_trade';
        return <<<EOF
            <style>
            .payjs_warp{width:200px;text-align:center;font-size:14px}
            .payjs_qrcode{position:relative}
            .payjs_qrcode img{border:1px solid #ddd;width:200px;box-sizing:border-box}
            .payjs_status_mask{position:absolute;top:0;left:0;width:200px;background:#212121;height:200px;z-index:100;opacity:.7}
            .payjs_status_text{position:absolute;top:88px;left:1px;width:200px;color:#fff;height:30px;font-weight:700;z-index:101}
            .payjs_tip{font-size:14px;margin-top:8px}
            </style>
            <div class="payjs_warp">
                <div class="payjs_qrcode">
                <img src="{$result['qrcode']}" />
                <div class="payjs_status payjs_status_mask" style="display:none"></div>
                <div class="payjs_status payjs_status_text" style="display:none"></div>
                </div>
                <div class="payjs_tip">请使用<font color="red">微信</font>扫码</div>
                <script type="text/javascript">
                var payjs_order_id = "{$result['payjs_order_id']}", out_trade_no = "{$result['out_trade_no']}";
                function check_trade(){
                    $.getJSON("{$check_trade_url}&trade_sn=" + out_trade_no, function(rs){
                        if(rs.status) {
                            $('.payjs_status_text').text("支付成功");
                            $('.payjs_status').show();
                            setTimeout(function(){
                                location.href = './index.php?m=member';
                            }, 2000)
                        } else {
                            setTimeout('check_trade()', 2000)
                        }
                    })
                }
                check_trade();
                </script>
            </div>
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
