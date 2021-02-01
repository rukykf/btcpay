<?php

/**
 * Package: BTCPay (CiviCRM Extension)
 * Copyright (C) 2020, Kofi Oghenerukevwe <rukykf@gmail.com>
 * Licensed under the GNU Affero Public License 3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, version 3 of the license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 **/

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
