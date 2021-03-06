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
                //$result = "Giao d???ch th??nh c??ng - Approved";
                break;
            case "05" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: Qu?? kh??ch nh???p sai m???t kh???u thanh to??n qu?? s??? l???n quy ?????nh. Xin qu?? kh??ch vui l??ng th???c hi???n l???i giao d???ch ";
                break;
            case "06" :
                $result = "Giao d???ch kh??ng th??nh c??ng do Qu?? kh??ch nh???p sai m???t kh???u x??c th???c giao d???ch (OTP). Xin qu?? kh??ch vui l??ng th???c hi???n l???i giao d???ch. ";
                break;
            case "07" :
                $result = "Tr??? ti???n th??nh c??ng. Giao d???ch b??? nghi ng??? (li??n quan t???i l???a ?????o, giao d???ch b???t th?????ng). ?????i v???i giao d???ch n??y c???n merchant x??c nh???n th??ng qua merchant admin: T??? ch???i/?????ng ?? giao d???ch ";
                break;
            case "12" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: Th???/T??i kho???n c???a kh??ch h??ng b??? kh??a. ";
                break;
            case "09" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: Th???/T??i kho???n c???a kh??ch h??ng ch??a ????ng k?? d???ch v??? InternetBanking t???i ng??n h??ng.";
                break;
            case "10" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: Kh??ch h??ng x??c th???c th??ng tin th???/t??i kho???n kh??ng ????ng qu?? 3 l???n ";
                break;
            case "11" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: ???? h???t h???n ch??? thanh to??n. Xin qu?? kh??ch vui l??ng th???c hi???n l???i giao d???ch. ";
                break;
            case "24" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: Kh??ch h??ng h???y giao d???ch ";
                break;
            case "51" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: T??i kho???n c???a qu?? kh??ch kh??ng ????? s??? d?? ????? th???c hi???n giao d???ch";
                break;
            case "65" :
                $result = "Giao d???ch kh??ng th??nh c??ng do: T??i kho???n c???a Qu?? kh??ch ???? v?????t qu?? h???n m???c giao d???ch trong ng??y. ";
                break;
            case "75" :
                $result = "Ng??n h??ng thanh to??n ??ang b???o tr?? ";
                break;
            case "99" :
                $result = "C??c l???i kh??c (l???i c??n l???i, kh??ng c?? trong danh s??ch m?? l???i ???? li???t k??) ";
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
