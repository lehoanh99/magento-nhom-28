<?php

class TTS_Vnpay_Model_Vnpay extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'vnpay';
    protected $_formBlockType = 'vnpay/form';
    protected $_infoBlockType = 'vnpay/info';

    public function getTitle() {
        return $this->getConfigData('title');
    }

    public function get_icon() {
        return $this->getConfigData('icon');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('vnpay/standard/redirect', array('_secure' => true));
    }

    public function getUrlVnpay($orderid) {
        Mage::log('begin creare url', null, 'vnpay.log', TRUE);
        $_order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        $_order->sendNewOrderEmail();
        $getGrandTotal = $_order->getGrandTotal();
        $getGrandTotalArr = explode(".", $getGrandTotal);
        $getGrandTotalArr0 = $getGrandTotalArr[0];
        $getGrandTotalArr1 = $getGrandTotalArr[1];
        $getGrandTotalArr2 = substr($getGrandTotalArr1, 0, 2);
        $amount_total = $getGrandTotalArr0 . '.' . $getGrandTotalArr2;
        $date = new DateTime(); //this returns the current date time
        $result = $date->format('Y-m-d-H-i-s');
        $krr = explode('-', $result);
        $result1 = implode("", $krr);
        $vnp_Url = $this->getConfigData('vnp_Url');
        $vnp_Returnurl = Mage::getUrl('vnpay/standard/success');
        $hashSecret = $this->getConfigData('hash_code');
        $vnp_Locale = $this->getConfigData('vnp_Locale');
        $vnp_OrderInfo = 'Thanh toan voi ma don hang ' . $orderid;
        $vnp_OrderType = 'other';
        $vnp_CurrCode = $this->getConfigData('vnp_Currency');
        $vnp_Amount = $amount_total * 100;
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $terminalCode=$this->getConfigData('vnp_Terminal');
        $Odarray = array(
            "vnp_TmnCode" => $terminalCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $result1,
            "vnp_CurrCode" => $vnp_CurrCode,
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $orderid,
            "vnp_Version" => "2.0.0",
        );
        ksort($Odarray);
        $query = "";
        $i = 0;
        $data = "";
        foreach ($Odarray as $key => $value) {
            if ($i == 1) {
                $data .= '&' . $key . "=" . $value;
            } else {
                $data .= $key . "=" . $value;
                $i = 1;
            }

            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        $vnp_Url .='?';
        $vnp_Url .=$query;
        if (isset($hashSecret)) {
            $vnpSecureHash = hash('sha256', $hashSecret . $data);
            $vnp_Url .= 'vnp_SecureHashType=SHA256&vnp_SecureHash=' . $vnpSecureHash;
        }
        Mage::log('Url Payment=' . $vnp_Url, null, 'vnpay.log', TRUE);
        Mage::log('End Create Url Payment', null, 'vnpay.log', TRUE);
        return $vnp_Url;
    }

    public function getResponseDescription($responseCode) {

        switch ($responseCode) {
            case "00" :
                //$result = "Giao dịch thành công - Approved";
                break;
            case "05" :
                $result = "Giao dịch không thành công do: Quý khách nhập sai mật khẩu thanh toán quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch ";
                break;
            case "06" :
                $result = "Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP). Xin quý khách vui lòng thực hiện lại giao dịch. ";
                break;
            case "07" :
                $result = "Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường). Đối với giao dịch này cần merchant xác nhận thông qua merchant admin: Từ chối/Đồng ý giao dịch ";
                break;
            case "12" :
                $result = "Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa. ";
                break;
            case "09" :
                $result = "Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng.";
                break;
            case "10" :
                $result = "Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần ";
                break;
            case "11" :
                $result = "Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch. ";
                break;
            case "24" :
                $result = "Giao dịch không thành công do: Khách hàng hủy giao dịch ";
                break;
            case "51" :
                $result = "Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch";
                break;
            case "65" :
                $result = "Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày. ";
                break;
            case "75" :
                $result = "Ngân hàng thanh toán đang bảo trì ";
                break;
            case "99" :
                $result = "Các lỗi khác (lỗi còn lại, không có trong danh sách mã lỗi đã liệt kê) ";
                break;
            default :
            //$result = "Failured";
        }
        return $result;
    }

    public function transStatus($txnResponseCode) {
        $transStatus = "";
        if ($txnResponseCode == "00") {
            $transStatus = "Transaction Success";
        } else {
            $transStatus = "Transaction Fail </br>" . $this->getResponseDescription($txnResponseCode);
        }
        return $transStatus;
    }

}
