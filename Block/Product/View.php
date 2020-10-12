<?php

namespace Spro\AplazoPayment\Block\Product;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use Spro\AplazoPayment\Model\Config;

class View extends Template
{
	/**
	 * @var Context
	 */
	private $context;
	/**
	 * @var array
	 */
	private $data;
	/**
	 * @var Config
	 */
	protected $config;


	public function __construct(
        Config $config,
		Context $context,
		array $data = []
	) {
		$this->config = $config;
		$this->data = $data;
		$this->context = $context;
		parent::__construct($context, $data);
	}

	/**
	 * @return string
	 */
	public function getRedirectUrl()
	{

	}
}
