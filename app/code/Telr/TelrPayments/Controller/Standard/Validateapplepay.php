<?php

namespace Telr\TelrPayments\Controller\Standard;

class Validateapplepay extends \Telr\TelrPayments\Controller\TelrPayments {

    public function execute()
    {
        $responseParams = $this->getRequest()->getParams();
        $certificate_key =  BP .'/var/upload/telr/certificate_keys/' . $this->getTelrApplePayModel()->getConfig('key_pem');
        $certificate_path = BP .'/var/upload/telr/certificate_keys/' . $this->getTelrApplePayModel()->getConfig('cert_pem');
        $read = $this->_driver->create($certificate_path, \Magento\Framework\Filesystem\DriverPool::FILE);
        $fileData = $read->readAll();
        $merchantidentifier = $this->getTelrApplePayModel()->getConfig('apple_merchant_id');
        $certificate_pass = $this->getTelrApplePayModel()->getConfig('key_password');
        $domainName = $this->getTelrApplePayModel()->getConfig('apple_domain_name');
        $displayName = $this->getTelrApplePayModel()->getConfig('apple_display_name');

        $data = json_decode('{"merchantIdentifier":"'.$merchantidentifier.'", "domainName":"'.$domainName.'", "displayName":"'.$displayName.'"}');

        $result = $this->_telrHelper->callApi($data, $responseParams['valURL'], $certificate_key, $certificate_path, $certificate_pass);
        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($result);
        return $jsonResult;
    }
}
