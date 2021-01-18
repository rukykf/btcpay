<?php

use BTCPayServer\Client\BTCPayServerException;
use BTCPayServer\Client\Request;

/**
 * This class simply extends \BTCPayServer\Client\Client in order to fix some
 * bugs And add some extra utility methods
 *
 */
class CRM_BTCPayServer_Client extends \BTCPayServer\Client\Client {


  public function getInvoicePaymentInfo($invoiceId) {
    $this->request = $this->createNewRequest();
    $this->request->setMethod(Request::METHOD_GET);
    if ($this->token && $this->token->getFacade() === 'merchant') {
      $this->request->setPath(sprintf('invoices/%s?token=%s', $invoiceId, $this->token->getToken()));
      $this->addIdentityHeader($this->request);
      $this->addSignatureHeader($this->request);
    }
    else {
      $this->request->setPath(sprintf('invoices/%s', $invoiceId));
    }
    $this->response = $this->sendRequest($this->request);
    $body = json_decode($this->response->getBody(), TRUE);

    if (isset($body['error'])) {
      throw new BTCPayServerException($body['error']);
    }

    $data = $body['data'];

    $paymentInfo = [
      "paymentUrl" => $data["url"],
      "btcPrice" => $data["btcPrice"],
      "btcDue" => $data["btcDue"],
      "bitcoinAddress" => $data["bitcoinAddress"],
      "currency" => $data["currency"],
      "rate" => $data["rate"],
    ];

    return $paymentInfo;
  }

  /**
   * Override the default createNewRequest from the BTCPayServer client
   * To fix a bug in the way BTCPayServer creates it
   * Will probably submit a pull request to the BTCPayServer project with this
   * fix
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
