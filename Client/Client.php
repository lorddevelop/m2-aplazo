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
use Magento\Catalog\Helper\ImageFactory ;


/**
 * Class Client
 * @package Trulieve\Viridian\Client
 */
class Client
{
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
        ManagerInterface $messageManager,
        ImageFactory $imageHelperFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->imageHelperFactory = $imageHelperFactory;
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
                "description" => $quoteItem->getProduct()->getShortDescription(),
                "id" => $quoteItem->getProduct()->getId(),
                "imageUrl" => $this->imageHelperFactory->create()
                    ->init($quoteItem->getProduct(), 'product_small_image')->getUrl(),
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
