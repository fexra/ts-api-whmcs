<?php

namespace TRTLServices\Http;

use Psr\Http\Message\ResponseInterface;
use TRTLServices\Contracts\Arrayable;
use TRTLServices\Contracts\Jsonable;

class Response implements Jsonable, Arrayable
{
    protected $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function toJson():string
    {
        return $this->response->getBody()->getContents();
    }

    public function toArray():array
    {
        return json_decode($this->response->getBody()->getContents(), true);
    }

    public function result()
    {
        $result = $this->toArray();

        return $result['result'] ?? $result;
    }

    public function response():ResponseInterface
    {
        return $this->response;
    }

    public function __call($method, $parameters)
    {
        return $this->response->{$method}(...$parameters);
    }

    public function __toString()
    {
        return $this->toJson();
    }
}