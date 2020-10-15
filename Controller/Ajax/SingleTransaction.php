<?php

namespace Spro\AplazoPayment\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Spro\AplazoPayment\Client\Client;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Transaction
 * @package Avve\AvvePayment\Controller\Ajax
 */
class SingleTransaction extends Action
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
	 * Transaction constructor.
	 * @param Context           $context
	 * @param CheckoutSession   $checkoutSession
	 * @param LoggerInterface   $logger
	 * @param Client	$client
	 */
	public function __construct(
		Context				$context,
		CheckoutSession		$checkoutSession,
		LoggerInterface		$logger,
        Client	$client
	)
	{
		$this->_logger				=	$logger;
		$this->_checkoutSession		=	$checkoutSession;
        $this->client	=	$client;
		parent::__construct($context);
	}

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
	 */
	public function execute()
	{
		$data		= [
			'error'			=>	true,
			'message'		=>	__('Service temporarily unavailable. Please try again later.'),
			'transactionId'	=>	null
		];
		$resultJson	=	$this->resultFactory->create(ResultFactory::TYPE_JSON);
		try {
			$response	=	$this->_transactionHelper->getTransactionId($this->_transactionHelper->getLastAddedProduct());
			if (isset($response['data']['id'])) {
				$transactionId = $response['data']['id'];
				$this->_checkoutSession->setAvveTransactionId($transactionId);
				$data = [
					'error'			=>	false,
					'message'		=>	'',
					'transactionId'	=>	$transactionId
				];
			}
		} catch (\Exception $e) {
			$this->_logger->debug($e->getMessage());
		}
		$resultJson->setData($data);
		return $resultJson;
	}
}
