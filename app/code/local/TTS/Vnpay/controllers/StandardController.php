<?php

class TTS_Vnpay_StandardController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {

        $session = Mage::getSingleton('checkout/session');
        $url = Mage::getModel('vnpay/vnpay')->getUrlVnpay($session->getLastRealOrderId());
        $this->_redirectUrl($url);
    }

    /**
     * When a customer cancel payment from paypal.
     */
    public function successAction() {
        $SECURE_SECRET = Mage::getStoreConfig('payment/vnpay/hash_code', Mage::app()->getStore());
        if (isset($_GET['vnp_TxnRef'])) {
            $order_id = $_GET['vnp_TxnRef'];
        } else {
            $message = '';
            $order_id = 0;
        }
        $vnp_SecureHash = $_GET['vnp_SecureHash'];
        $vnp_TxnResponseCode = $_GET['vnp_ResponseCode'];
        $hashSecret = $SECURE_SECRET;
        $get = $_GET;
        $data = array();
        foreach ($get as $key => $value) {
            $data[$key] = $value;
        }
        unset($data["vnp_SecureHashType"]);
        unset($data["vnp_SecureHash"]);
        ksort($data);
        $i = 0;
        $data2 = "";
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $data2 .= '&' . $key . "=" . $value;
            } else {
                $data2 .= $key . "=" . $value;
                $i = 1;
            }
        }
        $secureHash = hash('sha256', $hashSecret . $data2);
        Mage::getSingleton('checkout/session')->addSuccess(Mage::getModel('vnpay/vnpay')->transStatus($vnp_TxnResponseCode));
        if ($vnp_SecureHash == $secureHash) {
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        }
    }

    public function ipnAction() {
        if (isset($_GET['vnp_TxnRef'])) {
            $order_id = $_GET['vnp_TxnRef'];
        } else {
            $message = '';
            $order_id = 0;
        }
        $returnData = array();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $orderData = $order->getData();
        // var_dump($orderData);
        $SECURE_SECRET = Mage::getStoreConfig('payment/vnpay/hash_code', Mage::app()->getStore());
        $vnp_SecureHash = $_GET['vnp_SecureHash'];
        $vnp_TxnResponseCode = $_GET['vnp_ResponseCode'];
        $hashSecret = $SECURE_SECRET;
        $get = $_GET;
        $data = array();
        foreach ($get as $key => $value) {
            $data[$key] = $value;
        }
        unset($data["vnp_SecureHashType"]);
        unset($data["vnp_SecureHash"]);
        ksort($data);
        $i = 0;
        $data2 = "";
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $data2 .= '&' . $key . "=" . $value;
            } else {
                $data2 .= $key . "=" . $value;
                $i = 1;
            }
        }
        $secureHash = hash('sha256', $hashSecret . $data2);
        $ipn = array();
        $ipn['vnp_OrderInfo'] = ($_GET ["vnp_OrderInfo"]);
        $ipn['vnp_SecureHash'] = $hashSecret;
        $ipn['vnp_TmnCode'] = $_GET ["vnp_TmnCode"];
        $ipn['vnp_ResponseCode'] = ($_GET ["vnp_ResponseCode"]);
        $ipn['vnp_TransactionNo'] = ($_GET ["vnp_TransactionNo"]);
        $ipn['vnp_TxnRef'] = ($_GET ["vnp_TxnRef"]);
        $model = Mage::getModel('vnpay/success');
        $model->setData($ipn);
        try {
            $model->save();
        } catch (Exception $e) {
            
        }
        //Check Orderid 
        if ($orderData['entity_id'] != NULL && $orderData['entity_id'] != '') {
            //Check chữ ký
            if ($secureHash == $vnp_SecureHash) {
                //Check Status của đơn hàng
                if ($orderData["status"] != NULL && $orderData["status"] != 'processing') {
                    if ($vnp_TxnResponseCode == '00') {
                        $returnData['RspCode'] = '00';
                        $returnData['Message'] = 'Confirm Success';
                        $returnData['Signature'] = $secureHash;
                        $order = Mage::getModel('sales/order')->loadByIncrementId($data['vnp_TxnRef']);
                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                        $order->addStatusHistoryComment('Thanh toán thành công qua VNPAY', TRUE);
                        $order->save();
                    } else {
                        $returnData['RspCode'] = '00';
                        $returnData['Message'] = 'Confirm Success';
                        $returnData['Signature'] = $secureHash;
                        $order = Mage::getModel('sales/order')->loadByIncrementId($data['vnp_TxnRef']);
                        //$order->setState("payment_fail", true);
                        $order->setStatus("payment_fail");
                        $order->addStatusHistoryComment('Giao dịch không thành công', TRUE);
                        $order->save();
                    }
                } else {
                    $returnData['RspCode'] = '02';
                    $returnData['Message'] = 'Order already confirmed';
                }
            } else {
                $returnData['RspCode'] = '97';
                $returnData['Message'] = 'Chu ky khong hop le';
                $returnData['Signature'] = $secureHash;
            }
        } else {
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
        }
        echo json_encode($returnData);
    }

}
