<?php

namespace Spro\AplazoPayment\Model;

use Spro\AplazoPayment\Model\Config;

class Client
{

    protected $config;

    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    public function auth()
    {
        $httpHeaders = new \Laminas\Http\Headers();
        $httpHeaders->addHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $request = new \Laminas\Http\Request();
        $request->setHeaders($httpHeaders);
        $request->setUri($this->config->getBaseUrl());
        $request->setMethod(\Laminas\Http\Request::METHOD_POST);


        $params = [
            "apiToken" => $this->config->getApiToken(),
            "merchantId" => $this->config->getMerchantId(),
        ];
        $request->setContent(json_encode($params));


        $client = new \Laminas\Http\Client();
        $options = [
            'adapter'   => 'Laminas\Http\Client\Adapter\Curl',
            'curloptions' => [CURLOPT_FOLLOWLOCATION => true],
            'maxredirects' => 0,
            'timeout' => 5
        ];
        $client->setOptions($options);

        $response = $client->send($request);

        return ($response->getStatusCode()==200);
    }

    public function create()
    {

    }

}