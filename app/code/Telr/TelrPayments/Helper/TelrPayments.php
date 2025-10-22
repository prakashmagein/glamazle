<?php

namespace Telr\TelrPayments\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Helper\AbstractHelper;

class TelrPayments extends AbstractHelper {
    protected $session;
    protected $_curl;
   //protected $applePayMethodCode = "telr_applepay";
    protected $applePayMethodCode;
    protected $applePayMethod;
    
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Checkout\Model\Session $session
    ) {
        $this->session = $session;
        $this->_curl = $curl;
        parent::__construct($context);
        $this->applePayMethod = $paymentHelper->getMethodInstance('telr_applepay');
    }

    public function cancelCurrentOrder($comment) {
        $order = $this->session->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    public function restoreQuote() {
        return $this->session->restoreQuote();
    }

    public function getUrl($route, $params = []) {
        return $this->_getUrl($route, $params);
    }

    public function callApi($postData, $gatewayUrl, $certificate_key = '', $certificate_path = '', $certificate_pass = '')
    {
        try {
            $this->getCurlClient()->setOption(CURLOPT_FAILONERROR, 1);
            $this->getCurlClient()->setOption(CURLOPT_ENCODING, "compress, gzip");
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->getCurlClient()->setOption(CURLOPT_FOLLOWLOCATION, 1);
            $this->getCurlClient()->setOption(CURLOPT_CONNECTTIMEOUT, 0);
            $this->getCurlClient()->setOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
           
            if (!empty($certificate_key)) {
                $this->getCurlClient()->setOption(CURLOPT_SSLKEY, $certificate_key);
            }
            if (!empty($certificate_path)) {
                $this->getCurlClient()->setOption(CURLOPT_SSLCERT, $certificate_path);
            }
            if (!empty($certificate_pass)) {
                $this->getCurlClient()->setOption(CURLOPT_SSLKEYPASSWD, $certificate_pass);
            }
            $this->getCurlClient()->addHeader("Content-Type", "application/json;charset=UTF-8");
            
            $this->getCurlClient()->post($gatewayUrl, json_encode($postData));
            
            $response = $this->getCurlClient()->getBody();
            $array_result = json_decode($response, true);
            if (empty($array_result)) {
                return false;
            }
            return $array_result;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    private function log($message){
        /*$myfile = fopen("logfile.txt", "a") or die("Unable to open file!");
        $txt = "\n------------------\n";
        fwrite($myfile, $message);
        fclose($myfile);*/
    }

    public function getCurlClient()
    {
        return $this->_curl;
    }

    public function getApplePayConfig($key)
    {
        return $this->applePayMethod->getConfigData($key);
    }
}
