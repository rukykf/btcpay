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

    return $body['data'];
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
