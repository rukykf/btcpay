<?php

use BTCPayServer\Client\RequestInterface;
use BTCPayServer\Client\ResponseInterface;
use BTCPayServer\Client\Response;
use BTCPayServer\Client\Adapter\AdapterInterface;
use GuzzleHttp\Client;

class CRM_Btcpay_Utils_GuzzleAdapter implements AdapterInterface {

  /**
   * @var array
   */
  protected $guzzleOptions;

  /**
   * Guzzle client configuration settings,
   * see Guzzle docs for options
   *
   * @param array $guzzleOptions
   */
  public function __construct($guzzleOptions = []) {
    $this->guzzleOptions = $guzzleOptions;
  }

  /**
   * Send request to BTCPayServer
   *
   * @param BTCPayServer\Client\RequestInterface $request
   *
   * @return BTCPayServer\Client\ResponseInterface
   */
  public function sendRequest(RequestInterface $request) {
    $client = new Client($this->guzzleOptions);

    $guzzleRequest = $this->toPSRRequest($request);
    $response = $client->sendRequest($guzzleRequest);

    return $this->toBTCServerResponse($response);
    return $response;
  }

  /**
   * convert the request from the BTCPayServer client to the PSR request
   * required to work with guzzle
   *
   * @param BTCPayServer\Client\RequestInterface $request
   *
   * @return GuzzleHttp\Psr7\Request
   *
   */
    private function toPSRRequest($request) {
      $method = $request->getMethod();
      $url = $request->getUri();
      $headers = $request->getHeaders();
      $body = $request->getBody();
      $guzzleRequest = new GuzzleHttp\Psr7\Request($method, $url, $headers, $body);

      return $guzzleRequest;
    }

  /**
   * Convert the guzzle response to the format required by BTCPayServer client
   *
   * @param $response GuzzleHttp\Psr7\Response
   *
   * @return BTCPayServer\Client\Response
   *
   */
  private function toBTCServerResponse($response) {
    $btcpayserverResponse = new BTCPayServer\Client\Response();

    $body = (string) $response->getBody();
    $btcpayserverResponse->setBody($body);

    $headers = $response->getHeaders();
    foreach ($headers as $header => $value) {
      $btcpayserverResponse->setHeader($header, $value);
    }

    $statusCode = $response->getStatusCode();
    $btcpayserverResponse->setStatusCode($statusCode);
    return $btcpayserverResponse;

  }

}
