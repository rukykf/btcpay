<?php
/**
 * @license Copyright 2019 BTCPayServer, MIT License
 * see https://github.com/btcpayserver/btcpayserver-php-client/blob/master/LICENSE
 */

namespace BTCPayServer\Client;

use BTCPayServer\Client\Adapter\AdapterInterface;
use BTCPayServer\TokenInterface;
use BTCPayServer\InvoiceInterface;
use BTCPayServer\PayoutInterface;
use BTCPayServer\Util\Util;
use BTCPayServer\PublicKey;
use BTCPayServer\PrivateKey;

/**
 * Client used to send requests and receive responses for BTCPayServer's Web API
 *
 * @package BTCPayServer
 */
class Client implements ClientInterface {

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @var PrivateKey
     */
    protected $privateKey;

    /**
     * @var string
     */
    protected $uri;

    public function setUri(string $uri) {
        $this->uri = $uri;
    }

    /**
     * Set the Public Key to use to help identify who you are to BTCPayServer.
     * Please note that you must first pair your keys and get a token in return
     * to use.
     *
     * @param PublicKey $key
     */
    public function setPublicKey(PublicKey $key) {
        $this->publicKey = $key;
    }

    /**
     * Set the Private Key to use, this is used when signing request strings
     *
     * @param PrivateKey $key
     */
    public function setPrivateKey(PrivateKey $key) {
        $this->privateKey = $key;
    }

    /**
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    /**
     * @param TokenInterface $token
     *
     * @return ClientInterface
     */
    public function setToken(TokenInterface $token) {
        $this->token = $token;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function fillInvoiceData(InvoiceInterface $invoice, $data) {
        // Returns the invoice time in milliseconds. PHP's DateTime object expects the time to be in seconds
        $invoiceTime = is_numeric($data['invoiceTime']) ? intval($data['invoiceTime'] / 1000) : $data['invoiceTime'];
        $expirationTime = is_numeric($data['expirationTime']) ? intval($data['expirationTime'] / 1000) : $data['expirationTime'];
        $currentTime = is_numeric($data['currentTime']) ? intval($data['currentTime'] / 1000) : $data['currentTime'];

        $invoiceToken = new \BTCPayServer\Token();
        $invoice
            ->setToken($invoiceToken->setToken($data['token']))
            ->setUrl($data['url'])
            ->setPosData(array_key_exists('posData', $data) ? $data['posData'] : '')
            ->setStatus($data['status'])
            ->setPrice($data['price'])
            ->setCurrency(new \BTCPayServer\Currency($data['currency']))
            ->setOrderId(array_key_exists('orderId', $data) ? $data['orderId'] : '')
            ->setInvoiceTime($invoiceTime)
            ->setExpirationTime($expirationTime)
            ->setCurrentTime($currentTime)
            ->setId($data['id'])
            ->setAmountPaid(array_key_exists('amountPaid', $data) ? $data['amountPaid'] : '')
            ->setExceptionStatus($data['exceptionStatus'])
            ->setRefundAddresses(array_key_exists('refundAddresses', $data) ? $data['refundAddresses'] : '')
            ->setTransactionCurrency(array_key_exists('transactionCurrency', $data) ? $data['transactionCurrency'] : NULL)
            ->setPaymentTotals(array_key_exists('paymentTotals', $data) ? $data['paymentTotals'] : '')
            ->setPaymentSubtotals(array_key_exists('paymentSubtotals', $data) ? $data['paymentSubtotals'] : '')
            ->setExchangeRates(array_key_exists('exchangeRates', $data) ? $data['exchangeRates'] : '');
        return $invoice;
    }

    /**
     * @inheritdoc
     */
    public function createInvoice(InvoiceInterface $invoice) {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_POST);
        $request->setPath('invoices');

        $currency = $invoice->getCurrency();
        $item = $invoice->getItem();
        $buyer = $invoice->getBuyer();
        $buyerAddress = $buyer->getAddress();

        $this->checkPriceAndCurrency($item->getPrice(), $currency->getCode());

        $body = [
            'price' => $item->getPrice(),
            'currency' => $currency->getCode(),
            'posData' => $invoice->getPosData(),
            'notificationURL' => $invoice->getNotificationUrl(),
            'transactionSpeed' => $invoice->getTransactionSpeed(),
            'fullNotifications' => $invoice->isFullNotifications(),
            'extendedNotifications' => $invoice->isExtendedNotifications(),
            'notificationEmail' => $invoice->getNotificationEmail(),
            'redirectURL' => $invoice->getRedirectUrl(),
            'orderID' => $invoice->getOrderId(),
            'itemDesc' => $item->getDescription(),
            'itemCode' => $item->getCode(),
            'physical' => $item->isPhysical(),
            'buyerName' => trim(sprintf('%s %s', $buyer->getFirstName(), $buyer->getLastName())),
            'buyerAddress1' => isset($buyerAddress[0]) ? $buyerAddress[0] : '',
            'buyerAddress2' => isset($buyerAddress[1]) ? $buyerAddress[1] : '',
            'buyerCity' => $buyer->getCity(),
            'buyerState' => $buyer->getState(),
            'buyerZip' => $buyer->getZip(),
            'buyerCountry' => $buyer->getCountry(),
            'buyerEmail' => $buyer->getEmail(),
            'buyerPhone' => $buyer->getPhone(),
            'buyerNotify' => $buyer->getNotify(),
            'guid' => Util::guid(),
            'nonce' => Util::nonce(),
            'token' => $this->token->getToken(),
        ];

        $request->setBody(json_encode($body));
        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);
        $this->request = $request;
        $this->response = $this->sendRequest($request);

        $body = $this->parseResponse();
        $data = $body['data'];

        $invoice = $this->fillInvoiceData($invoice, $data);

        return $invoice;
    }

    /**
     * @inheritdoc
     */
    public function getCurrencies() {
        $this->request = $this->createNewRequest();
        $this->request->setMethod(Request::METHOD_GET);
        $this->request->setPath('currencies');
        $this->response = $this->sendRequest($this->request);
        $body = json_decode($this->response->getBody(), TRUE);
        if (empty($body['data'])) {
            throw new \BTCPayServer\Client\BTCPayServerException('Error with request: no data returned');
        }
        $currencies = $body['data'];
        array_walk($currencies, function (&$value, $key) {
            $currency = new \BTCPayServer\Currency();
            $currency
                ->setCode($value['code'])
                ->setSymbol($value['symbol'])
                ->setPrecision($value['precision'])
                ->setExchangePctFee($value['exchangePctFee'])
                ->setPayoutEnabled($value['payoutEnabled'])
                ->setName($value['name'])
                ->setPluralName($value['plural'])
                ->setAlts($value['alts'])
                ->setPayoutFields($value['payoutFields']);
            $value = $currency;
        });

        return $currencies;
    }

    /**
     * @inheritdoc
     */
    public function createPayout(PayoutInterface $payout) {
        $request = $this->createNewRequest();
        $request->setMethod($request::METHOD_POST);
        $request->setPath('payouts');

        $amount = $payout->getAmount();
        $currency = $payout->getCurrency();
        $effectiveDate = $payout->getEffectiveDate();
        $token = $payout->getToken();

        $body = [
            'token' => $token->getToken(),
            'amount' => $amount,
            'currency' => $currency->getCode(),
            'instructions' => [],
            'effectiveDate' => $effectiveDate,
            'pricingMethod' => $payout->getPricingMethod(),
            'guid' => Util::guid(),
            'nonce' => Util::nonce(),
        ];

        // Optional
        foreach ([
                     'reference',
                     'notificationURL',
                     'notificationEmail',
                 ] as $value) {
            $function = 'get' . ucfirst($value);
            if ($payout->$function() != NULL) {
                $body[$value] = $payout->$function();
            }
        }

        // Add instructions
        foreach ($payout->getInstructions() as $instruction) {
            $body['instructions'][] = [
                'label' => $instruction->getLabel(),
                'address' => $instruction->getAddress(),
                'amount' => $instruction->getAmount(),
            ];
        }

        $request->setBody(json_encode($body));
        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request = $request;
        $this->response = $this->sendRequest($request);
        $body = $this->parseResponse();

        $data = $body['data'];
        $payout
            ->setId($data['id'])
            ->setAccountId($data['account'])
            ->setResponseToken($data['token'])
            ->setStatus($data['status']);

        foreach ($data['instructions'] as $c => $instruction) {
            $payout->updateInstruction($c, 'setId', $instruction['id']);
        }

        return $payout;
    }

    /**
     * @inheritdoc
     */
    public function getPayouts($status = NULL) {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_GET);
        $path = 'payouts?token='
            . $this->token->getToken()
            . (($status == NULL) ? '' : '&status=' . $status);
        $request->setPath($path);

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request = $request;
        $this->response = $this->sendRequest($this->request);
        $body = $this->parseResponse();

        $payouts = [];

        array_walk($body['data'], function ($value, $key) use (&$payouts) {
            $payout = new \BTCPayServer\Payout();
            $payout
                ->setId($value['id'])
                ->setAccountId($value['account'])
                ->setCurrency(new \BTCPayServer\Currency($value['currency']))
                ->setEffectiveDate($value['effectiveDate'])
                ->setRequestdate($value['requestDate'])
                ->setPricingMethod($value['pricingMethod'])
                ->setStatus($value['status'])
                ->setAmount($value['amount'])
                ->setResponseToken($value['token'])
                ->setRate(@$value['rate'])
                ->setBtcAmount(@$value['btc'])
                ->setReference(@$value['reference'])
                ->setNotificationURL(@$value['notificationURL'])
                ->setNotificationEmail(@$value['notificationEmail']);

            array_walk($value['instructions'], function ($value, $key) use (&$payout) {
                $instruction = new \BTCPayServer\PayoutInstruction();
                $instruction
                    ->setId($value['id'])
                    ->setLabel($value['label'])
                    ->setAddress($value['address'])
                    ->setAmount($value['amount'])
                    ->setStatus($value['status']);

                array_walk($value['transactions'], function ($value, $key) use (&$instruction) {
                    $transaction = new \BTCPayServer\PayoutTransaction();
                    $transaction
                        ->setTransactionId($value['txid'])
                        ->setAmount($value['amount'])
                        ->setDate($value['date']);

                    $instruction->addTransaction($transaction);
                });

                $payout->addInstruction($instruction);
            });

            $payouts[] = $payout;
        });

        return $payouts;
    }

    /**
     * @inheritdoc
     */
    public function deletePayout(PayoutInterface $payout) {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_DELETE);
        $request->setPath(sprintf('payouts/%s?token=%s', $payout->getId(), $payout->getResponseToken()));

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request = $request;
        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), TRUE);
        if (empty($body['data'])) {
            throw new \BTCPayServer\Client\BTCPayServerException('Error with request: no data returned');
        }

        $data = $body['data'];

        $payout->setStatus($data['status']);

        return $payout;
    }

    /**
     * @inheritdoc
     */
    public function getPayout($payoutId) {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_GET);
        $request->setPath(sprintf('payouts/%s?token=%s', $payoutId, $this->token->getToken()));
        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request = $request;
        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), TRUE);
        if (empty($body['data'])) {
            throw new \BTCPayServer\Client\BTCPayServerException('Error with request: no data returned');
        }
        $data = $body['data'];

        $payout = new \BTCPayServer\Payout();
        $payout
            ->setId($data['id'])
            ->setAccountId($data['account'])
            ->setStatus($data['status'])
            ->setCurrency(new \BTCPayServer\Currency($data['currency']))
            ->setRate(@$data['rate'])
            ->setAmount($data['amount'])
            ->setBtcAmount(@$data['btc'])
            ->setPricingMethod(@$data['pricingMethod'])
            ->setReference(@$data['reference'])
            ->setNotificationEmail(@$data['notificationEmail'])
            ->setNotificationUrl(@$data['notificationURL'])
            ->setRequestDate($data['requestDate'])
            ->setEffectiveDate($data['effectiveDate'])
            ->setResponseToken($data['token']);

        array_walk($data['instructions'], function ($value, $key) use (&$payout) {
            $instruction = new \BTCPayServer\PayoutInstruction();
            $instruction
                ->setId($value['id'])
                ->setLabel($value['label'])
                ->setAddress($value['address'])
                ->setStatus($value['status'])
                ->setAmount($value['amount'])
                ->setBtc($value['btc']);

            array_walk($value['transactions'], function ($value, $key) use (&$instruction) {
                $transaction = new \BTCPayServer\PayoutTransaction();
                $transaction
                    ->setTransactionId($value['txid'])
                    ->setAmount($value['amount'])
                    ->setDate($value['date']);

                $instruction->addTransaction($transaction);
            });

            $payout->addInstruction($instruction);
        });

        return $payout;
    }

    /**
     * @inheritdoc
     */
    public function getTokens() {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_GET);
        $request->setPath('tokens');
        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request = $request;
        $this->response = $this->sendRequest($this->request);
        $body = json_decode($this->response->getBody(), TRUE);
        if (empty($body['data'])) {
            throw new \BTCPayServer\Client\BTCPayServerException('Error with request: no data returned');
        }

        $tokens = [];

        array_walk($body['data'], function ($value, $key) use (&$tokens) {
            $key = current(array_keys($value));
            $value = current(array_values($value));
            $token = new \BTCPayServer\Token();
            $token
                ->setFacade($key)
                ->setToken($value);

            $tokens[$token->getFacade()] = $token;
        });

        return $tokens;
    }

    /**
     * @inheritdoc
     */
    public function createToken(array $payload = []) {
        if (isset($payload['pairingCode']) && 1 !== preg_match('/^[a-zA-Z0-9]{7}$/', $payload['pairingCode'])) {
            throw new \InvalidArgumentException("pairing code is not legal");
        }

        $this->request = $this->createNewRequest();
        $this->request->setMethod(Request::METHOD_POST);
        $this->request->setPath('tokens');
        $payload['guid'] = Util::guid();
        $this->request->setBody(json_encode($payload));
        printf("/n/n/n===============================");
        var_dump($this->request);
        printf("/n/n/n===============================");
        $this->response = $this->sendRequest($this->request);
        $body = json_decode($this->response->getBody(), TRUE);

        var_dump($body);
        if (isset($body['error'])) {
            throw new \BTCPayServer\Client\BTCPayServerException($this->response->getStatusCode() . ": " . $body['error']);
        }

        if ($this->response->getStatusCode() >= 400) {
            throw new BTCPayServerException('invalid status code: ' . $this->response->getStatusCode());
        }

        $tkn = $body['data'][0];
        $createdAt = new \DateTime();
        $pairingExpiration = new \DateTime();

        $token = new \BTCPayServer\Token();
        $token
            ->setPolicies($tkn['policies'])
            ->setToken($tkn['token'])
            ->setFacade($tkn['facade'])
            ->setCreatedAt($createdAt->setTimestamp(floor($tkn['dateCreated'] / 1000)));

        if (isset($tkn['resource'])) {
            $token->setResource($tkn['resource']);
        }

        if (isset($tkn['pairingCode'])) {
            $token->setPairingCode($tkn['pairingCode']);
            $token->setPairingExpiration($pairingExpiration->setTimestamp(floor($tkn['pairingExpiration'] / 1000)));
        }

        return $token;
    }

    /**
     * Returns the Response object that BTCPayServer returned from the request
     * that was sent
     *
     * @return ResponseInterface
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Returns the request object that was sent to BTCPayServer
     *
     * @return RequestInterface
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @inheritdoc
     */
    public function getInvoice($invoiceId) {
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
        \Civi::log()
            ->debug("=====================================================Client Get Invoice");
        \Civi::log()->debug(print_r($this->response));

        $body = json_decode($this->response->getBody(), TRUE);

        \Civi::log()->debug(print_r($body));
        if (isset($body['error'])) {
            throw new BTCPayServerException($body['error']);
        }

        $data = $body['data'];

        $invoice = new \BTCPayServer\Invoice();
        $invoice = $this->fillInvoiceData($invoice, $data);

        return $invoice;
    }


    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request) {
        if (NULL === $this->adapter) {
            // Uses the default adapter
            $this->adapter = new \BTCPayServer\Client\Adapter\CurlAdapter();
        }

        return $this->adapter->sendRequest($request);
    }

    /**
     * @param RequestInterface $request
     */
    protected function addIdentityHeader(RequestInterface $request) {
        if (NULL === $this->publicKey) {
            throw new \BTCPayServer\Client\BTCPayServerException('Please set your Public Key.');
        }

        $request->setHeader('x-identity', (string) $this->publicKey);
    }

    /**
     * @param RequestInterface $request
     */
    protected function addSignatureHeader(RequestInterface $request) {
        if (NULL === $this->privateKey) {
            throw new BTCPayServerException('Please set your Private Key');
        }

        $url = $request->getUri();

        $message = sprintf(
            '%s%s',
            $url,
            $request->getBody()
        );

        $signature = $this->privateKey->sign($message);
        $request->setHeader('x-signature', $signature);
    }

    /**
     * @return RequestInterface
     */
    protected function createNewRequest() {
        $request = new Request();

        var_dump($this->uri);

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

    /**
     * Prepares the request object by adding additional headers
     *
     * @param RequestInterface $request
     */
    protected function prepareRequestHeaders(RequestInterface $request) {
        // @see http://en.wikipedia.org/wiki/User_agent
        $request->setHeader(
            'User-Agent',
            sprintf('%s/%s (PHP %s)', self::NAME, self::VERSION, phpversion())
        );
        $request->setHeader('X-BTCPayServer-Plugin-Info', sprintf('%s/%s', self::NAME, self::VERSION));
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('X-Accept-Version', '2.0.0');
    }

    protected function checkPriceAndCurrency($price, $currency) {
        $decimalPosition = strpos($price, '.');
        if ($decimalPosition == 0) {
            $decimalPrecision = 0;
        }
        else {
            $decimalPrecision = strlen(substr($price, $decimalPosition + 1));
        }
        if ($currency !== 'BTC' && $decimalPrecision > 2) {
            throw new \BTCPayServer\Client\BTCPayServerException('Incorrect price format or currency type.');
        }
        elseif ($decimalPrecision > 6) {
            throw new \BTCPayServer\Client\BTCPayServerException('Incorrect price format or currency type.');
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function parseResponse() {
        $bodyString = $this->response->getBody();
        if ($this->response->getStatusCode() === 401) {
            throw new \Exception($bodyString);
        }
        $body = json_decode($bodyString, TRUE);
        $error_message = FALSE;
        $error_message = (!empty($body['error'])) ? $body['error'] : $error_message;
        $error_message = (!empty($body['errors'])) ? $body['errors'] : $error_message;
        $error_message = (is_array($error_message)) ? implode("\n", $error_message) : $error_message;
        if (FALSE !== $error_message) {
            throw new \BTCPayServer\Client\BTCPayServerException($error_message);
        }
        return $body;
    }

}
