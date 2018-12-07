<?php

namespace TRTLServices\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use stdClass;

class Client
{
    protected $client = null;
    protected $host = 'https://api.trtl.services/v1';
    protected $token = null;
    protected $timeout = 2000;

    public function __construct(array $config = [])
    {
        $this->configure($config);

        $headers = [
            'Authorization' => 'Bearer ' . $config['token'],        
            'Accept'        => 'application/json',
        ];

        $this->client = new GuzzleClient([
            'base_url' => $this->host,
            'defaults' => [
                'headers' => $headers
            ]
        ]);
    }

    public function configure(array $config = []):void
    {
        $config = array_intersect_key($config, array_flip(['token', 'timeout']));

        foreach ($config as $key => $value)
        {
            $this->{$key} = $value;
        }
    }

    public function config():array
    {
        return [
            'token'     => $this->token,
            'timeout'   => $this->timeout
        ];
    }

    public function _get(string $method):Response
    {
        $response = $this->client->get($this->host . '/' . $method, [
            'headers'   => [
                'Authorization' => 'Bearer ' .  $this->config()['token'],        
                'Accept'        => 'application/json',
            ],
            'timeout'   => $this->config()['timeout']
        ]);
        
        return new Response($response);
    }

    public function _post(string $method, array $params = []):Response
    {
        $params = $this->prepareParams($params);
        
        $response = $this->client->post($this->host . '/' . $method, [
            'headers'   => [
                'Authorization' => 'Bearer ' .  $this->config()['token'],        
                'Accept'        => 'application/json',
            ],
            'json'      => $params,
            'timeout'   => $this->config()['timeout'],
        ]);

        return new Response($response);
    }

    public function _delete(string $method):Response
    {
        $response = $this->client->delete($this->host . '/' . $method, [
            'headers'   => [
                'Authorization' => 'Bearer ' .  $this->config()['token'],        
                'Accept'        => 'application/json',
            ],
            'timeout'   => $this->config()['timeout'],
        ]);
        
        return new Response($response);
    }

    protected function prepareParams(array $params)
    {
        return empty($params) ? new stdClass() : $params;
    }

    public function client():ClientInterface
    {
        return $this->client;
    }
}