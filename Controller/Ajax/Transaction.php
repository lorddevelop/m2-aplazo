<?php

namespace Spro\AplazoPayment\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Spro\AplazoPayment\Client\Client;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteManagement;

class Transaction extends Action
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $_logger;
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * Transaction constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param Client $client
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        Client $client,
        QuoteManagement $quoteManagement
    ) {
        $this->_logger = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->client = $client;
        $this->quoteManagement = $quoteManagement;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $data = [
            'error' => true,
            'message' => __('Service temporarily unavailable. Please try again later.'),
            'transactionId' => null
        ];
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $auth = $this->client->auth();
            $quote = $this->_checkoutSession->getQuote();
            if ($auth && is_array($auth)) {
                $resultUrl = $this->client->create($auth, $quote);
                $shippingAddress = $quote->getShippingAddress();
                if (!$shippingAddress || !$shippingAddress->getStreet()){
                    $this->fillDummyAddress($quote);
                }

                $quote->setPaymentMethod(\Spro\AplazoPayment\Model\Payment::CODE);
                $quote->getPayment()->importData(['method' => \Spro\AplazoPayment\Model\Payment::CODE]);
                $order = $this->quoteManagement->submit($quote);

                if ($resultUrl) {
                    $data = [
                        'error' => false,
                        'message' => '',
                        'redirecturl' => $resultUrl
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
        $resultJson->setData($data);
        return $resultJson;
    }

    protected function fillDummyAddress(&$quote)
    {
        $quote->setCustomerIsGuest(true);
        $quote->getShippingAddress()->setEmail('aplazoclient@aplazo.com');
        $quote->getShippingAddress()->setEmail('aplazoclient@aplazo.com');
        $quote->getShippingAddress()->setFirstname('Aplazo');
        $quote->getBillingAddress()->setFirstname('Aplazo');
        $quote->getShippingAddress()->setLastname('Client');
        $quote->getBillingAddress()->setLastname('Client');
        $quote->getShippingAddress()->setCity('Aplazocity');
        $quote->getBillingAddress()->setCity('Aplazocity');
        $quote->getShippingAddress()->setPostcode('12345');
        $quote->getBillingAddress()->setPostcode('12345');
        $quote->getShippingAddress()->setCountryId('MX');
        $quote->getBillingAddress()->setCountryId('MX');
        $quote->getShippingAddress()->setRegionId(664);
        $quote->getBillingAddress()->setRegionId(664);
        $quote->getShippingAddress()->setStreet('Aplazostreet');
        $quote->getBillingAddress()->setStreet('Aplazostreet');
        $quote->getShippingAddress()->setTelephone('1234567890');
        $quote->getBillingAddress()->setTelephone('1234567890');
        $quote->collectTotals();
        $rates = $quote->getShippingAddress()->getAllShippingRates();
        if (is_array($rates)){
            $quote->getShippingAddress()->setShippingMethod($rates[0]->getCode());
        }
        $quote->setCustomerEmail('aplazoclient@aplazo.com');
        $quote->setPaymentMethod(\Spro\AplazoPayment\Model\Payment::CODE);
        $quote->getPayment()->importData(['method' => \Spro\AplazoPayment\Model\Payment::CODE]);
    }

}
