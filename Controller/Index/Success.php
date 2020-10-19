<?php

namespace Spro\AplazoPayment\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Psr\Log\LoggerInterface;

class Success extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
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
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * Create constructor.
     * @param Context $context
     * @param RedirectFactory $redirectFactory
     * @param JsonFactory $jsonFactory
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param LoggerInterface $logger
     * @param QuoteManagement $quoteManagement
     */
    public function __construct(
        Context $context,
        RedirectFactory $redirectFactory,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement
    ) {
        $this->_logger = $logger;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteRepository = $quoteRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->_jsonFactory = $jsonFactory;
        $this->_redirectFactory = $redirectFactory;
        $this->quoteManagement = $quoteManagement;

        parent::__construct($context);
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        $result->setData([
            "success" => false,
            "message" => __('Invalid token'),
            "data" => [
                "order_id" => null
            ],
            "errors" => []
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
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $quote = $this->_checkoutSession->getQuote();
            //$order = $this->quoteManagement->submit($quote);
            $result->setUrl('/checkout/onepage/success');
            return $result;
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
            $result->setUrl('/checkout/cart');
            return $result;
        }
    }
}
