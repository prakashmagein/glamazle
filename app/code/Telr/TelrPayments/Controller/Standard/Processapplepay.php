<?php

namespace Telr\TelrPayments\Controller\Standard;

use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\App\Filesystem\DirectoryList as FileSystem;
class Processapplepay extends \Telr\TelrPayments\Controller\TelrPayments {
	
    protected $quoteManagement;
	protected $cart;
	protected $orderSender;
    protected $orderModel;					  

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Telr\TelrPayments\Model\TelrPayments $telrModel,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Telr\TelrPayments\Model\TelrApplePay $telrApplePayModel,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Telr\TelrPayments\Helper\TelrPayments $telrHelper,
        \Magento\Framework\DB\Transaction $transaction,
        \Psr\Log\LoggerInterface $logger,
	    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
	    FileSystem $fileSystem,
        \Magento\Framework\Filesystem\File\ReadFactory $driver,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        QuoteManagement $quoteManagement,
        \Magento\Checkout\Model\Cart $cart,
	    \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
	    \Magento\Sales\Model\OrderFactory $orderModel
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->cart = $cart;
	    $this->orderSender = $orderSender;
	    $this->orderModel = $orderModel;
		
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $messageManager,
            $orderFactory,
            $telrModel,
            $invoiceService,
            $telrApplePayModel,
            $invoiceSender,
            $telrHelper,
            $transaction,
            $logger,
	        $resultJsonFactory,
	        $fileSystem,
            $driver,
            $transactionBuilder,
            $orderCollectionFactory
        );
    }
    public function execute()
    {
        $responseParams = $this->getRequest()->getParams();
        if(isset($responseParams['data'])){
            $jsonData = json_decode($responseParams['data'], true);
            $jsonData = $jsonData['data'];
            $order = $this->_checkoutSession->getLastRealOrder();
            if (!empty($order->getRealOrderId())) {
                $orderId = $order->getRealOrderId();
            } else {
               $quote = $this->cart->getQuote();
	       if ($quote->getCustomerEmail() === null && $quote->getBillingAddress()->getEmail() !== null) {
	            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
	            $quote->setCheckoutMethod('guest');
	            $quote->setCustomerGroupId(null);
	            $quote->setCustomerFirstname($quote->getBillingAddress()->getFirstname());
	            $quote->setCustomerLastname($quote->getBillingAddress()->getLastname());
               }
                
               $order = $this->quoteManagement->submit($quote);
               $orderId = $order->getRealOrderId();
		        		
	       $this->_checkoutSession->setLastRealOrderId($order->getRealOrderId());
               $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
               $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
               $this->_checkoutSession->setLastOrderId($order->getEntityId());			
            }									  
            $telrApplePayModel = $this->getTelrApplePayModel();
            $billing_address = $order->getBillingAddress();


            $params = array(
              'ivp_method'      => 'applepay',
              'ivp_store'       =>  $telrApplePayModel->getConfig('store_id'),
              'ivp_authkey'     => $telrApplePayModel->getConfig('remote_api_auth_key'),
              'ivp_amount'     => round($order->getGrandTotal(), 2),
              'ivp_test'       => '0',
              'ivp_desc'       => $this->getTelrModel()->getConfig("transaction_desc"),
              'ivp_currency'   => $order->getOrderCurrencyCode(),
              'ivp_cart'       => $order->getRealOrderId().'_'.(string)time(),
              'ivp_trantype'   => 'sale',
              'ivp_tranclass'  => 'ecom',
              'bill_fname'  => $billing_address->getName(),
              'bill_sname'  => $billing_address->getName(),
              'bill_addr1'  => $billing_address->getStreet()[0],
              'bill_city'  => $billing_address->getCity(),
              'bill_region'  => $billing_address->getRegion(),
              'bill_country'  => $billing_address->getCountryId(),
              'bill_zip'  => $billing_address->getPostcode(),
              'bill_email'  => $order->getCustomerEmail(),
              'applepay_enc_version' => $jsonData['paymentData']['version'],
              'applepay_enc_paydata' => urlencode($jsonData['paymentData']['data']),
              'applepay_enc_paysig' => urlencode($jsonData['paymentData']['signature']),
              'applepay_enc_pubkey' => urlencode($jsonData['paymentData']['header']['ephemeralPublicKey']),
              'applepay_enc_keyhash' => $jsonData['paymentData']['header']['publicKeyHash'],
              'applepay_tran_id' => $jsonData['paymentData']['header']['transactionId'],
              'applepay_card_desc' => $jsonData['paymentMethod']['type'],
              'applepay_card_scheme' => $jsonData['paymentMethod']['displayName'],
              'applepay_card_type' => $jsonData['paymentMethod']['network'],
              'applepay_tran_id2' => $jsonData['transactionIdentifier'],
            );

            $response = $this->remoteApiRequest($params);
            $results = json_decode($response, true);

            $objTransaction='';
            $objError='';
            if (isset($results['transaction'])) { $objTransaction = $results['transaction']; }
            if (isset($results['error'])) { $objError = $results['error']; }
            if (is_array($objError)) {
                $errorMessage = "Unable to process your payment. Error: " . $objError['message'] . ' ' . $objError['note'] . ' ' . $objError['details'];
                $this->messageManager->addError($errorMessage);
                $this->_checkoutSession->restoreQuote();
                $this->getResponse()->setRedirect(
                    $this->getTelrHelper()->getUrl('checkout/cart')
                );
            }else{
                $transactionStatus = $objTransaction['status'];
                if($transactionStatus == 'A'){
                    $transactionReference = $objTransaction['ref'];

                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->setStatus('processing');
                    $message = 'Payment completed by Telr: ' . $transactionReference;
                    $order->addStatusHistoryComment($message);
                    $order->save();
                    $this->orderSender->send($order, true);
                    if($order->canInvoice()) {
                        $invoice = $this->_invoiceService->prepareInvoice($order);
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                        $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
                        $invoice->setLastTransId($transactionReference);
                        $invoice->setTransactionId($transactionReference);
                        $invoice->register();
                        $invoice->save();
                        $transactionSave = $this->_transaction->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );
                        $transactionSave->save();
                        $this->_invoiceSender->send($invoice);
                        //send notification code
                        $order->addStatusHistoryComment(
                            __('Notified customer about invoice #%1.', $invoice->getId())
                        )
                        ->setIsCustomerNotified(true)
                        ->save();

                        try {
                            //get payment object from order object
                            $payment = $order->getPayment();
                            $payment->setLastTransId($transactionReference);
                            $payment->setTransactionId($transactionReference);
                            /*$payment->setAdditionalInformation(
                                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $objOrder]
                            );*/
                            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                                $order->getGrandTotal()
                            );
                 
                            $message = __('The authorized amount is %1.', $formatedPrice);
                            //get the object of builder class
                            $trans = $this->_transactionBuilder;
                            $transaction = $trans->setPayment($payment)
                            ->setOrder($order)
                            ->setTransactionId($transactionReference)
                            /*->setAdditionalInformation(
                                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $objOrder]
                            )*/
                            ->setFailSafe(true)
                            //build method creates the transaction and returns the object
                            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
                 
                            $payment->addTransactionCommentsToOrder(
                                $transaction,
                                $message
                            );
                            $payment->setParentTransactionId($transactionReference);
                            $payment->save();
                            $order->save();
                            $transaction->save();
                        } catch (Exception $e) {
                            
                        }
                    }

                    $returnUrl = $this->getTelrHelper()->getUrl('checkout/onepage/success');
                    $this->getResponse()->setRedirect(
                        $returnUrl
                    );
                }else{
                    $errorMessage = "Unable to process your payment. Error: " . $objTransaction['message'];
                    $this->messageManager->addError($errorMessage);
                    $this->_checkoutSession->restoreQuote();
                    $this->getResponse()->setRedirect(
                        $this->getTelrHelper()->getUrl('checkout/cart')
                    );
                }
            }
        }else{
            $returnUrl = $this->getTelrHelper()->getUrl('checkout/cart');
        }

        $this->getResponse()->setRedirect(
            $returnUrl
        );
    }

    function remoteApiRequest($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://secure.telr.com/gateway/remote.json');
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $results = curl_exec($ch);
        curl_close($ch);
        return $results;
    }
}
