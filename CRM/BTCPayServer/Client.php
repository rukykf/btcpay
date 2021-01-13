<?php

use BTCPayServer\Client\BTCPayServerException;
use BTCPayServer\Client\Request;

/**
 * This class simply extends \BTCPayServer\Client\Client in order to fix some
 * bugs And add some extra utility methods
 *
 */
class CRM_BTCPayServer_Client extends \BTCPayServer\Client\Client {

  /**
   * Override the default createNewRequest from the BTCPayServer client
   * To fix a bug in the way BTCPayServer creates its
   *
   * @return \BTCPayServer\Client\Request
   */
  protected function createNewRequest() {
    $request = new Request();

    $host = parse_url($this->uri, PHP_URL_HOST);
    $port = parse_url($this->uri, PHP_URL_PORT);
    $scheme = parse_url($this->uri, PHP_URL_SCHEME);

    $request->setHost($host);
    if ($port !== NULL) {
      $request->setPort($port);
    }

    $request->setScheme($scheme);
    $this->prepareRequestHeaders($request);

    return $request;
  }

}
