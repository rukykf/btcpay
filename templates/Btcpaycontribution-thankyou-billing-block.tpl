{crmScope key='btcpay'}
<div class="crm-group credit_card-group">
  <div class="header-dark">
    {ts}Payment Information{/ts}
  </div>
  <div class="display-block">
    <p>
      {ts}Payment Url{/ts}: <a href="{$btcpayPaymentUrl}" target="_blank"><strong>{$btcpayPaymentUrl}</strong></a><br/>
      {ts}Total Amount:{/ts} <strong>{$btcpayPrice} {$btcpayCurrency}</strong><br/>
    </p>
    <p><strong>{ts}We will send a receipt to your email after confirming your payment.{/ts}</strong></p>

    <p>{ts}You can click the button in the contribution section below (requires Javascript) to copy the Crypto Address you'll need to complete this transaction.{/ts}</p>
    <p>{ts}You can also send crypto payment (Bitcoin and Litecoin) into any of the following addresses{/ts}</p><br/>

    <hr/>
    {foreach from=$btcpayCryptoInfo item=crypto name=payment}
      <div>
        <p>{ts}Crypto Code{/ts}: <strong>{$crypto.cryptoCode}</strong></p>
        <p>{ts}Amount due{/ts}: <strong>{$crypto.due} {$crypto.cryptoCode}</strong></p>
        <p>{ts}Send payment to address{/ts}: <strong>{$crypto.address}</strong></p>
        <p>{ts}{$btcpayCurrency} to {$crypto.cryptoCode} conversion rate{/ts}: <strong>{$crypto.rate}</strong></p>
        <br/>
        {if not $smarty.foreach.payment.last}
          <strong>{ts}OR{/ts}</strong>
          <br/>
        {/if}
      </div>
    {/foreach}


  </div>
  <div class="crm-section crm-btcpay-block">
    <div class="crm-btcpay" id="btcpay-trxnid" style="display: none">{$btcpayTrxnId}</div>
    <a id="btcpay-payment-link" href="javascript:void(0)" onclick="btcpay.showInvoice('{$btcpayTrxnId}')">
      <img src="{$btcpayServerUrl}/img/paybutton/pay.svg" alt="{ts escape='js'}Pay with BTCPay{/ts}" style="padding: 30px"/>
    </a>
  </div>
</div>

<script src="{$btcpayServerUrl}/modal/btcpay.js">
  {literal}
  /**
   *
   * @source: https://github.com/btcpayserver/btcpayserver/blob/master/BTCPayServer/wwwroot/modal/btcpay.js
   *
   * @licstart  The following is the entire license notice for the
   *  JavaScript code in this page.
   *
   * MIT / Expat License

   * Copyright (c) 2017-2021 btcpayserver

   * Permission is hereby granted, free of charge, to any person obtaining a copy
   * of this software and associated documentation files (the "Software"), to deal
   * in the Software without restriction, including without limitation the rights
   * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
   * copies of the Software, and to permit persons to whom the Software is
   * furnished to do so, subject to the following conditions:

   * The above copyright notice and this permission notice shall be included in all
   * copies or substantial portions of the Software.

   * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
   * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
   * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
   * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
   * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
   * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
   * SOFTWARE.
   *
   * @licend  The above is the entire license notice
   * for the JavaScript code in this page.
   *
   */
  {/literal}
</script>

{literal}
  <script type="text/javascript">
    // @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-v3.0
    CRM.$(function ($) {
      CRM.$('.crm-btcpay-block').appendTo('div.crm-group.amount_display-group div.display-block');
      btcpay.showInvoice(CRM.$('#btcpay-trxnid').text());
    });
    // @license-end
  </script>
{/literal}
{/crmScope}
