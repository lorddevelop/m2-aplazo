<?php

namespace Spro\AplazoPayment\Client;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Model\Quote;
use Spro\AplazoPayment\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;


/**
 * Class Client
 * @package Trulieve\Viridian\Client
 */
class Client
{
    const VIRIDIAN_DELIVERY_METHOD = 'delivery_delivery';
    const VIRIDIAN_DELIVERY_SKU = 'DELV-0001';
    const VIRIDIAN_PASSWORD = 'viridian/general/api_password';
    const VIRIDIAN_USER = 'viridian/general/api_user';
    const VIRIDIAN_DOMAIN = 'viridian/general/api_domain';
    const VIRIDIAN_ENABLE = 'viridian/general/enable';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var mixed
     */
    protected $domain;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @var mixed
     */
    protected $password;

    /**
     * @var mixed
     */
    protected $token;
    /**
     * @var\Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    public $endpoints = [
        "auth" => "api/auth",
        "create" => "api/loan",
    ];

    public $probe;

    /**
     * @var Subscriber
     */
    protected $subscriber;
    protected $addressRepository;

    protected $config;

    protected $curl;

    public function __construct(
        Config $config,
        Curl $curl,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->domain = $this->config->getBaseApiUrl();
    }


    public function auth()
    {
        $url = $this->makeUrl("auth");

        $body = [
            "apiToken" => $this->config->getApiToken(),
            "merchantId" => $this->config->getMerchantId()
        ];
        $payload = json_encode($body);
        $this->curl->setHeaders(['Content-Type' => 'application/json']);
        $this->curl->post($url, $payload);
        $result = $this->curl->getBody();
        if ($this->curl->getStatus() == 200) {
            return json_decode($result,true);
        }
        return false;
    }

    public function create($authHeader,$quote)
    {
        $url = $this->makeUrl("create");

        $headers = $authHeader;
        $headers['Content-Type'] = 'application/json';
        $this->curl->setHeaders($headers);

        $body = $this->prepareCreateParams($quote);
        $payload = json_encode($body);
        $this->curl->post($url, $payload);
        $result = $this->curl->getBody();
        if ($this->curl->getStatus()==200){
            return $result;
        }
        return false;

    }

    protected function makeUrl($endpoint)
    {
        return $this->domain . $this->endpoints[$endpoint];
    }

    protected function prepareCreateParams(Quote $quote)
    {
        $products = [];
        foreach ($quote->getAllVisibleItems() as $quoteItem){
            $productArr = [
                "count" => $quoteItem->getQty(),
                "description" => $quoteItem->getProduct()->getName(),
                "id" => $quoteItem->getProduct()->getId(),
                "imageUrl" => "string",
                "price" => $quoteItem->getPrice(),
                "title" => $quoteItem->getName()
            ];
            $products[] = $productArr;
        }
        return [
            "cartId" => $quote->getId(),
            "discount" => [
                "price" => $quote->getShippingAddress()->getDiscountAmount(),
                "title" => $quote->getShippingAddress()->getDiscountDescription()
            ],
            "errorUrl" => $this->storeManager->getStore()->getUrl('aplazopayment/ajax/fail'),
            "products" => $products,
            "shipping" => [
                "price" => $quote->getShippingAddress()->getShippingAmount(),
                "title" => $quote->getShippingAddress()->getShippingAmount()
            ],
            "shopId" => $this->storeManager->getStore()->getName(),
            "successUrl" => $this->storeManager->getStore()->getUrl('aplazopayment/ajax/create'),
            "taxes" => [
                "price" => $quote->getShippingAddress()->getTaxAmount(),
                "title" => __('Tax')
            ],
            "totalPrice" => $quote->getGrandTotal()
        ];
    }
}
