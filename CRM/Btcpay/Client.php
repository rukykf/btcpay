<?php

class CRM_Btcpay_Client {

  /**
   * @var array Payment processor
   */
  private $_paymentProcessor = NULL;

  /**
   * CRM_Btcpay_Client constructor.
   *
   * @param $paymentProcessor
   */
  public function __construct($paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * Get the bitpay processor client object
   *
   * @return \BTCPayServer\Client\Client
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function getClient() {
    $storageEngine = new \BTCPayServer\Storage\EncryptedFilesystemStorage(CRM_Btcpay_Keys::getKeyPassword($this->_paymentProcessor['id'])); // Password may need to be updated if you changed it
    $privateKey = $storageEngine->load(CRM_Btcpay_Keys::getKeyPath($this->_paymentProcessor['id']));
    $publicKey = $storageEngine->load(CRM_Btcpay_Keys::getKeyPath($this->_paymentProcessor['id'], FALSE));
    $client = new CRM_BTCPayServer_Client();
    $adapter = new CRM_Btcpay_Utils_GuzzleAdapter();
    $client->setPrivateKey($privateKey);
    $client->setPublicKey($publicKey);
    $client->setAdapter($adapter);
    $client->setUri($this->_paymentProcessor["url_site"]);
    // ---------------------------
    /**
     * The last object that must be injected is the token object.
     */
    $token = new \BTCPayServer\Token();
    $token->setToken($this->_paymentProcessor['signature']);
    $token->setFacade("merchant");
    /**
     * Token object is injected into the client
     */
    $client->setToken($token);

    return $client;
  }

}
