<?php

namespace Spro\AplazoPaymemt\Model;

use Magento\Checkout\Model\Session;

/**
 * Class Payment
 * @package Avve\AvvePayment\Model
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
	const CODE								=	'avve_payment';

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;
	/**
	 * @var
	 */
	protected $_countryFactory;
	/**
	 * @var Session
	 */
	protected $_checkoutSession;
	/**
	 * @var string
	 */
	protected $_code						=	self::CODE;
	/**
	 * @var bool
	 */
	protected $_isGateway                   =	true;
	/**
	 * @var bool
	 */
	protected $_canAuthorize				=	true;
	/**
	 * @var bool
	 */
	protected $_canCapture                  =	true;
	/**
	 * @var bool
	 */
	protected $_canCapturePartial           =	true;
	/**
	 * @var bool
	 */
	protected $_canRefund                   =	true;
	/**
	 * @var bool
	 */
	protected $_canRefundInvoicePartial     =	true;
	/**
	 * @var string[]
	 */
	protected $_supportedCurrencyCodes		=	array('USD');

	/**
	 * Payment constructor.
	 * @param \Magento\Framework\Model\Context                   $context
	 * @param \Magento\Framework\Registry                        $registry
	 * @param \Magento\Framework\Api\ExtensionAttributesFactory  $extensionFactory
	 * @param \Magento\Framework\Api\AttributeValueFactory       $customAttributeFactory
	 * @param \Magento\Payment\Helper\Data                       $paymentData
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Payment\Model\Method\Logger               $logger
	 * @param \Magento\Directory\Model\CountryFactory            $countryFactory
	 * @param \Psr\Log\LoggerInterface                           $psrLogger
	 * @param Session                                            $_checkoutSession
	 * @param array                                              $data
	 */
	public function __construct(
		\Magento\Framework\Model\Context					$context,
		\Magento\Framework\Registry							$registry,
		\Magento\Framework\Api\ExtensionAttributesFactory	$extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory		$customAttributeFactory,
		\Magento\Payment\Helper\Data						$paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface	$scopeConfig,
		\Magento\Payment\Model\Method\Logger				$logger,
		\Magento\Directory\Model\CountryFactory				$countryFactory,
		\Psr\Log\LoggerInterface							$psrLogger,
		Session												$_checkoutSession,
		array $data = array()
	) {
		parent::__construct(
			$context,
			$registry,
			$extensionFactory,
			$customAttributeFactory,
			$paymentData,
			$scopeConfig,
			$logger,
			null,
			null,
			$data,
			null
		);
		$this->_logger			=	$psrLogger;
		$this->_checkoutSession	=	$_checkoutSession;
	}

	/**
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float                                $amount
	 * @return $this|Payment
	 * @throws \Magento\Framework\Validator\Exception
	 */
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		try {
			$transactionID = uniqid('aplazo_');
			$payment
				->setTransactionId($transactionID)
				->setIsTransactionClosed(0);

		} catch (\Exception $e) {
			$this->debugData(['exception' => $e->getMessage()]);
			$this->_logger->error(__('Payment capturing error.'));
			throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
		}
		return $this;
	}

	/**
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float                                $amount
	 * @return $this|Payment
	 * @throws \Magento\Framework\Validator\Exception
	 */
	public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		try {
			$transactionId = $payment->getParentTransactionId();
		} catch (\Exception $e) {
			$this->debugData(['exception' => $e->getMessage()]);
			$this->_logger->error(__('Payment refunding error.'));
			throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
		}
		return $this;
	}

	/**
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float                                $amount
	 * @return $this|Payment
	 */
	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		return $this;
	}

	/**
	 * @param \Magento\Quote\Api\Data\CartInterface|null $quote
	 * @return bool
	 */
	public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
	{
		return parent::isAvailable($quote);
	}

	/**
	 * @param string $currencyCode
	 * @return bool
	 */
	public function canUseForCurrency($currencyCode)
	{
		if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
			return false;
		}
		return true;
	}
}
