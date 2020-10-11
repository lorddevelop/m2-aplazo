<?php

namespace Avve\AvvePayment\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Avve\AvvePayment\Helper\Data;
use Avve\AvvePayment\Helper\EstimateShippingMethods;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Webapi\Response;
use Avve\AvvePayment\Helper\Order as AvveOrderHelper;

/**
 * Class Create
 * @package Avve\AvvePayment\Controller\Ajax
 */
class Create extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{

	const PARAM_NAME_TOKEN = 'token';

	/**
	 * @var RedirectFactory
	 */
	protected $_redirectFactory;
	/**
	 * @var JsonFactory
	 */
	protected $_jsonFactory;
	/**
	 * @var Data
	 */
	protected $_avveDataHelper;
	/**
	 * @var EstimateShippingMethods
	 */
	protected $_estimateShippingMethods;
	/**
	 * @var CheckoutSession
	 */
	protected $_checkoutSession;
	/**
	 * @var CartRepositoryInterface
	 */
	protected $_quoteRepository;
	/**
	 * @var QuoteFactory
	 */
	protected $_quoteFactory;
	/**
	 * @var LoggerInterface
	 */
	protected $_logger;
	/**
	 * @var AvveOrderHelper
	 */
	protected $_avveOrderHelper;
	/**
	 * @var
	 */
	protected $orderAvveInterface;

	/**
	 * Create constructor.
	 * @param Context                 $context
	 * @param RedirectFactory         $redirectFactory
	 * @param JsonFactory             $jsonFactory
	 * @param Data                    $dataHelper
	 * @param EstimateShippingMethods $estimateShippingMethods
	 * @param CheckoutSession         $checkoutSession
	 * @param CartRepositoryInterface $quoteRepository
	 * @param QuoteFactory            $quoteFactory
	 * @param LoggerInterface         $logger
	 * @param AvveOrderHelper         $orderHelper
	 */
	public function __construct(
		Context						$context,
		RedirectFactory				$redirectFactory,
		JsonFactory					$jsonFactory,
		Data						$dataHelper,
		EstimateShippingMethods		$estimateShippingMethods,
		CheckoutSession				$checkoutSession,
		CartRepositoryInterface		$quoteRepository,
		QuoteFactory				$quoteFactory,
		LoggerInterface				$logger,
		AvveOrderHelper				$orderHelper
	)
	{
		$this->_avveOrderHelper				=	$orderHelper;
		$this->_logger						=	$logger;
		$this->_quoteFactory				=	$quoteFactory;
		$this->_quoteRepository				=	$quoteRepository;
		$this->_checkoutSession				=	$checkoutSession;
		$this->_estimateShippingMethods		=	$estimateShippingMethods;
		$this->_avveDataHelper				=	$dataHelper;
		$this->_jsonFactory					=	$jsonFactory;
		$this->_redirectFactory				=	$redirectFactory;

		parent::__construct($context);
	}


	/**
	 * @param RequestInterface $request
	 * @return InvalidRequestException|null
	 */
	public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
	{
		$result	=	$this->resultFactory->create(ResultFactory::TYPE_JSON);
		$result->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
		$result->setData([
			"success"	=>	false,
			"message"	=> 	__('Invalid token'),
			"data"		=>	[
				"order_id"	=>	null
			],
			"errors"	=>	[]
		]);

		return new InvalidRequestException($result, [new Phrase('Invalid token')]);
	}


	/**
	 * @param RequestInterface $request
	 * @return bool|null
	 */
	public function validateForCsrf(RequestInterface $request): ?bool
	{
		$requestToken	=	$this->getRequest()->getParam(self::PARAM_NAME_TOKEN);
		if ($requestToken) {
			try {
				$apiKey	=	$this->_avveDataHelper->getUserToken();
				if ((strlen($apiKey) == strlen($requestToken)) &&
					strcmp($apiKey, $requestToken) == 0) {
					return true;
				}
			} catch (NoSuchEntityException $e) {
				$this->_logger->debug($e->getMessage());
			}
		}
		return false;
	}

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
	 */
	public function execute()
	{
		$orderData	=	[];
		$result		=	$this->_jsonFactory->create();
		$orderData	=	$this->getRequest()->getParams();
		$resultJson	=	$this->resultFactory->create(ResultFactory::TYPE_JSON);
		try {
			$result->setHttpResponseCode(Response::HTTP_OK);
			$response	=	$this->_avveOrderHelper->createOrder($orderData);
			$resultJson->setData($response);
			return $resultJson;
		} catch (\Exception $e) {
			$this->_logger->debug($e->getMessage());
			$result->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
			$response = [
				"success"	=>	false,
				"message"	=>	$e->getMessage(),
				"data"		=>	[
					"order_id"	=>	null
				],
				"errors"	=>	[]
			];
			$resultJson->setData($response);
			return $resultJson;
		}
	}
}
