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

class CRM_Btcpay_Keys {

  /**
   * Get the path for the public/private key file
   *
   * @param $processorId
   * @param bool $private
   *
   * @return mixed|string
   */
  public static function getKeyPath($processorId, $private = TRUE) {
    if ($private) {
      $ext = '.pri';
    }
    else {
      $ext = '.pub';
    }
    return Civi::paths()
      ->getPath("[civicrm.files]/persist/btcpay{$processorId}{$ext}");
  }

  /**
   * Get the password to decrypt the public/private key files. This is the
   * payment processor password field
   *
   * @param $processorId
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Core_Exception
   */
  public static function getKeyPassword($processorId) {
    $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', [
      'id' => $processorId,
    ]);
    if ($paymentProcessor['class_name'] !== CRM_Core_Payment_Btcpay::$className) {
      $msg = "Payment Processor ID {$processorId} is not of type Btcpay!";
      Civi::log()->warning($msg);
      throw new CRM_Core_Exception($msg);
    }
    if (empty($paymentProcessor['password'])) {
      $msg = "Payment Processor ID {$processorId} has no password defined!";
      Civi::log()->warning($msg);
      throw new CRM_Core_Exception($msg);
    }
    return $paymentProcessor['password'];
  }

  public static function createNewKeys($processorId) {
    /**
     * Start by creating a PrivateKey object
     */
    $privateKey = new \BTCPayServer\PrivateKey(self::getKeyPath($processorId));
    // Generate a random number
    $privateKey->generate();
    /**
     * Once we have a private key, a public key is created from it.
     */
    $publicKey = new \BTCPayServer\PublicKey(self::getKeyPath($processorId, FALSE));
    // Inject the private key into the public key
    $publicKey->setPrivateKey($privateKey);
    // Generate the public key
    $publicKey->generate();
    /**
     * Now that you have a private and public key generated, you will need to store
     * them somewhere. This option is up to you and how you store them is up to
     * you. Please be aware that you MUST store the private key with some type
     * of security. If the private key is compromised you will need to repeat this
     * process.
     */
    /**
     * It's recommended that you use the EncryptedFilesystemStorage engine to persist your
     * keys. You can, of course, create your own as long as it implements the StorageInterface
     */
    $storageEngine = new \BTCPayServer\Storage\EncryptedFilesystemStorage(self::getKeyPassword($processorId));
    $storageEngine->persist($privateKey);
    $storageEngine->persist($publicKey);
    return TRUE;
  }

  public static function pair($processorId, $pairingCode, $label) {
    /**
     * To load up keys that you have previously saved, you need to use the same
     * storage engine. You also need to tell it the location of the key you want
     * to load.
     */
    $storageEngine = new \BTCPayServer\Storage\EncryptedFilesystemStorage(self::getKeyPassword($processorId));
    $privateKey = $storageEngine->load(self::getKeyPath($processorId));
    $publicKey = $storageEngine->load(self::getKeyPath($processorId, FALSE));
    /**
     * Create the client, there's a lot to it and there are some easier ways, I am
     * showing the long form here to show how various things are injected into the
     * client.
     */
    $client = new CRM_BTCPayServer_Client();

    /**
     * The adapter is what will make the calls to the self-hosted BTCPay server and return the response
     * from BTCPay. This can be updated or changed as long as it implements the
     * AdapterInterface
     */
    $adapter = new CRM_Btcpay_Utils_GuzzleAdapter();

    /**
     * Now all the objects are created and we can inject them into the client
     */

    $btcpay = civicrm_api3('PaymentProcessor', 'get', [
      'sequential' => 1,
      'id' => $processorId,
    ]);

    /**
     * Visit the url for your self-hosted BTCPay server and create a new pairing code. Pairing
     * codes can only be used once and the generated code is valid for only 24 hours.
     */
    // $pairingCode = 'InsertPairingCodeHere';
    /**
     * Currently this part is required, however future versions of the PHP SDK will
     * be refactor and this part may become obsolete.
     */
    $sin = \BTCPayServer\SinKey::create()->setPublicKey($publicKey)->generate();

    /**** end ****/
    try {
      $client->setUri($btcpay["values"][0]["url_site"]);
      $client->setPrivateKey($privateKey);
      $client->setPublicKey($publicKey);
      $client->setAdapter($adapter);
      Civi::log()->debug(print_r($client, TRUE));

      $token = $client->createToken(
        [
          'id' => (string) $sin,
          'pairingCode' => $pairingCode,
          'label' => $label,
          'facade' => 'merchant'
        ]
      );


    } catch (\Exception $e) {
      /**
       * The code will throw an exception if anything goes wrong, if you did not
       * change the $pairingCode value or if you are trying to use a pairing
       * code that has already been used, you will get an exception. It was
       * decided that it makes more sense to allow your application to handle
       * this exception since each app is different and has different requirements.
       */
      $msg = "Exception occured: " . $e->getMessage() . PHP_EOL;
      $msg .= "Pairing failed. Please check whether you're trying to pair a production pairing code on test." . PHP_EOL;
      Civi::log()->warning($msg);

      $request = $client->getRequest();
      $response = $client->getResponse();


      /**
       * You can use the entire request/response to help figure out what went
       * wrong, but for right now, we will just var_dump them.
       */
      Civi::log()
        ->debug('BTCPay pairing request: ' . (string) $request->getUri() . PHP_EOL . PHP_EOL . PHP_EOL);
      Civi::log()
        ->debug('BTCPay pairing response: ' . (string) $response . PHP_EOL . PHP_EOL . PHP_EOL);
      throw new CRM_Core_Exception($msg);
      /**
       * NOTE: The `(string)` is include so that the objects are converted to a
       *       user friendly string.
       */
      exit(1); // We do not want to continue if something went wrong
    }
    /**
     * You will need to persist the token somewhere, by the time you get to this
     * point your application has implemented an ORM such as Doctrine or you have
     * your own way to persist data. Such as using a framework or some other code
     * base such as Drupal.
     */

    $persistThisValue = $token->getToken();

    civicrm_api3('PaymentProcessor', 'create', [
      'id' => $processorId,
      'signature' => $persistThisValue,

    ]);

    return $persistThisValue;
  }

}
