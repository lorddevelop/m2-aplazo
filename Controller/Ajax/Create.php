<?php

namespace Spro\AplazoPayment\Controller\Ajax;

use Aheadworks\OneStepCheckout\Block\Checkout;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Avve\AvvePayment\Helper\Data;
use Avve\AvvePayment\Helper\EstimateShippingMethods;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Webapi\Response;
use Magento\Quote\Model\QuoteManagement;
use Avve\AvvePayment\Helper\Order as AvveOrderHelper;

/**
 * Class Create
 * @package Avve\AvvePayment\Controller\Ajax
 */
class Create extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
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

	protected $quoteManagement;

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
        QuoteManagement $quoteManagement
    )
	{
		$this->_logger						=	$logger;
		$this->_quoteFactory				=	$quoteFactory;
		$this->_quoteRepository				=	$quoteRepository;
		$this->_checkoutSession				=	$checkoutSession;
		$this->_estimateShippingMethods		=	$estimateShippingMethods;
		$this->_avveDataHelper				=	$dataHelper;
		$this->_jsonFactory					=	$jsonFactory;
		$this->_redirectFactory				=	$redirectFactory;
		$this->quoteManagement = $quoteManagement;

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
	    return true;
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
		$result	=	$this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
		try {
			$quote = $this->_checkoutSession->getQuote();
            $order = $this->quoteManagement->submit($quote);
			return $result->redirect('checkout/onepage/success');
		} catch (\Exception $e) {
			$this->_logger->debug($e->getMessage());
            return $result->redirect('checkout/cart');
		}
	}
}
