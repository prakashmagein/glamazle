<?php

namespace Telr\TelrPayments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;
use Telr\TelrPayments\Helper\TelrPayments as TelrPaymentsHelper;

class TelrPaymentsConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = "telr_telrpayments";
    protected $applePayMethodCode = "telr_applepay";

    protected $method;
    protected $applePayMethod;
    protected $paymentTokenManagement;
    protected $customerSession;
    private $scopeConfig;
    private $telrPaymentsHelper;
    protected $_curl;

    public function __construct(
        PaymentHelper $paymentHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Telr\TelrPayments\Helper\TelrPayments $telrPaymentsHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerSession = $customerSession;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->scopeConfig = $scopeConfig;
        $this->telrPaymentsHelper = $telrPaymentsHelper;
        $this->_curl = $curl;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->applePayMethod = $paymentHelper->getMethodInstance($this->applePayMethodCode);
    }

    public function getConfig()
    {
        $savedCards = array();

        if($this->customerSession->isLoggedIn()){
            $customerId = $this->customerSession->getCustomerId();

            //Telr Saved Cards
            $savedCards = $this->getTelrSavedCards($customerId);

            $savedCardsList = $this->paymentTokenManagement->getListByCustomerId($customerId);
            foreach ($savedCardsList as $currentCard) {
                if($currentCard['is_active'] == 1 && $currentCard['is_visible'] == 1 && $currentCard['payment_method_code'] == 'telr_telrpayments'){
                    $cardDetails = json_decode($currentCard->getDetails(), true);

                    $cardName = (isset($cardDetails['type'])) ? $cardDetails['type'] : '';
                    $cardEnding = (isset($cardDetails['last4'])) ? $cardDetails['last4'] : '';
                    $cardExpMonth = (isset($cardDetails['expiry_month'])) ? $cardDetails['expiry_month'] : '';
                    $cardExpYear = (isset($cardDetails['expiry_year'])) ? $cardDetails['expiry_year'] : '';

                    $cardObj = array(
                        'txn_id' => $currentCard->getGatewayToken(),
                        'name' => $cardName . " ending with " . $cardEnding . " Expiry(" . $cardExpMonth . "/" . $cardExpYear . ")"
                    );

                    $savedCards[] = $cardObj;
                }   
            }
        }

        /* Apple Pay Configs */
        $country = $this->scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_WEBSITES);
        $storeName = $this->applePayMethod->getConfigData('apple_display_name');
        $appleSupportedNetwork = $this->applePayMethod->getConfigData('apple_supported_networks');
        $appleValidationUrl = $this->telrPaymentsHelper->getUrl('telr/standard/validateapplepay');
        $applePaymentProcessUrl = $this->telrPaymentsHelper->getUrl('telr/standard/processapplepay');
        $appleFailedUrl = $this->telrPaymentsHelper->getUrl('telr/standard/cancelapplepay');


        return $this->method->isAvailable() ? [
            'payment' => [
                'telr_telrpayments' => [
                    'redirectUrl' => $this->getRedirectUrl(),
                    'iframeUrl' => $this->getIframeUrl(),
                    'frameMode' => $this->getFramedMode(),
                    'seamlessIframe' => $this->getIframeMode(),
                    'language' => $this->getLanguage(),
                    'storeId' => $this->method->getConfigData("store_id"),
                    'testMode' => $this->method->getConfigData("sandbox"),
                    'savedCards' => $savedCards
                ],
                'telr_applepay' => [
                    'storeCountryCode' => $country,
                    'storeName' => $storeName,
                    'appleValidationUrl' => $appleValidationUrl,
                    'applePaymentProcessUrl' => $applePaymentProcessUrl,
                    'appleFailedUrl' => $appleFailedUrl,
                ]
            ]
        ] : [];
    }

    protected function getTelrSavedCards($custId)
    {
        $telrCards = array();

        $storeId = $this->method->getConfigData("store_id");
        $authKey = $this->method->getConfigData("auth_key");
        $testMode = $this->method->getConfigData("sandbox");

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://secure.telr.com/gateway/savedcardslist.json",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "api_storeid=" . $storeId . "&api_authkey=" . $authKey . "&api_testmode=" . $testMode . "&api_custref=" . $custId,
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if (!$err) {
            $resp = json_decode($response, true);
            if(isset($resp['SavedCardListResponse']) && $resp['SavedCardListResponse']['Code'] == 200){
                if(isset($resp['SavedCardListResponse']['data'])){
                    foreach ($resp['SavedCardListResponse']['data'] as $key => $row) {
                        $telrCards[] = array(
                            'txn_id' => $row['Transaction_ID'],
                            'name' => $row['Name']
                        );
                    }
                }
            }
        }

        return $telrCards;
    }

    protected function getRedirectUrl()
    {
        return $this->method->getRedirectUrl();
    }

    protected function getLanguage()
    {
        return $this->method->getLanguage();
    }

    protected function getIframeUrl()
    {
        return $this->method->getIframeUrl();
    }

    protected function getFramedMode()
    {
        return $this->method->getFramedMode();
    }

    protected function getIframeMode()
    {
        return $this->method->getIframeMode();
    }
}
