<?php

namespace Concrete\Package\CommunityStoreAuthorizeNet\Src\CommunityStore\Payment\Methods\CommunityStoreAuthorizeNet;

use Concrete\Package\CommunityStore\Controller\SinglePage\Dashboard\Store;
use Core;
use Config;
use Exception;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Shipping\Method\ShippingMethod as StoreShippingMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart as StoreCart;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductOption\ProductOption as StoreProductOption;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductOption\ProductOptionItem as StoreProductOptionItem;


class CommunityStoreAuthorizeNetPaymentMethod extends StorePaymentMethod
{

    public function dashboardForm()
    {
        $this->set('authorizeNetMode', Config::get('community_store_authorize_net.mode'));
        $this->set('authorizeNetCurrency', Config::get('community_store_authorize_net.currency'));
        $this->set('authorizeNetTestLoginID', Config::get('community_store_authorize_net.testLoginID'));
        $this->set('authorizeNetLiveLoginID', Config::get('community_store_authorize_net.liveLoginID'));
        $this->set('authorizeNetTestClientKey', Config::get('community_store_authorize_net.testClientKey'));
        $this->set('authorizeNetLiveClientKey', Config::get('community_store_authorize_net.liveClientKey'));
        $this->set('authorizeNetTestTransactionKey', Config::get('community_store_authorize_net.testTransactionKey'));
        $this->set('authorizeNetLiveTransactionKey', Config::get('community_store_authorize_net.liveTransactionKey'));
        $this->set('form', Core::make("helper/form"));

        $currencies = [
            'USD' => t('United States Dollar'),
            'CAD' => t('Canadian Dollar'),
            'CHF' => t('Swiss Franc'),
            'DKK' => t('Danish Krone'),
            'EUR' => t('Euro'),
            'GBP' => t('Pound Sterling'),
            'NOK' => t('Norwegian Krone'),
            'PLN' => t('Polish Zloty'),
            'SEK' => t('Swedish Krona'),
            'AUD' => t('Australian Dollar'),
            'NZD' => t('New Zealand Dollar')
        ];

        $this->set('authorizeNetCurrencies', $currencies);
    }

    public function save(array $data = [])
    {
        Config::save('community_store_authorize_net.mode', $data['authorizeNetMode']);
        Config::save('community_store_authorize_net.currency', $data['authorizeNetCurrency']);
        Config::save('community_store_authorize_net.testLoginID', $data['authorizeNetTestLoginID']);
        Config::save('community_store_authorize_net.liveLoginID', $data['authorizeNetLiveLoginID']);
        Config::save('community_store_authorize_net.testClientKey', $data['authorizeNetTestClientKey']);
        Config::save('community_store_authorize_net.liveClientKey', $data['authorizeNetLiveClientKey']);
        Config::save('community_store_authorize_net.testTransactionKey', $data['authorizeNetTestTransactionKey']);
        Config::save('community_store_authorize_net.liveTransactionKey', $data['authorizeNetLiveTransactionKey']);
    }

    public function validate($args, $e)
    {
        return $e;
    }

    public function checkoutForm()
    {
        $mode = Config::get('community_store_authorize_net.mode');
        $this->set('mode', $mode);
        $this->set('currency', Config::get('community_store_authorize_net.currency'));

        if ($mode == 'live') {
            $this->set('loginID', Config::get('community_store_authorize_net.liveLoginID'));
            $this->set('clientKey', Config::get('community_store_authorize_net.liveClientKey'));
        } else {
            $this->set('loginID', Config::get('community_store_authorize_net.testLoginID'));
            $this->set('clientKey', Config::get('community_store_authorize_net.testClientKey'));
        }

        $customer = new StoreCustomer();

        $this->set('email', $customer->getEmail());
        $this->set('form', Core::make("helper/form"));
        $this->set('amount', number_format(StoreCalculator::getGrandTotal() * 100, 0, '', ''));

        $pmID = StorePaymentMethod::getByHandle('community_store_authorize_net')->getID();
        $this->set('pmID', $pmID);
        $years = [];
        $year = date("Y");
        for ($i = 0; $i < 15; $i++) {
            $years[$year + $i] = $year + $i;
        }
        $this->set("years", $years);
    }

    public function submitPayment()
    {
        $customer = new StoreCustomer();

        $th = Core::make('helper/text');

        $currency = Config::get('community_store_authorize_net.currency');
        $mode = Config::get('community_store_authorize_net.mode');
        $total = number_format(StoreCalculator::getGrandTotal(), 2, '.', '');

        if ($mode == 'test') {
            $loginID = Config::get('community_store_authorize_net.testLoginID');
            $transactionKey = Config::get('community_store_authorize_net.testTransactionKey');
        } else {
            $loginID = Config::get('community_store_authorize_net.liveLoginID');
            $transactionKey = Config::get('community_store_authorize_net.liveTransactionKey');
        }

        $cart = StoreCart::getCart();

        $transRequestXmlStr =
            '<?xml version="1.0" encoding="UTF-8"?>
<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
      <merchantAuthentication>
        <name>' . $loginID . '</name>
        <transactionKey>' . $transactionKey . '</transactionKey>
        </merchantAuthentication>
      <transactionRequest>
         <transactionType>authCaptureTransaction</transactionType>
         <amount>' . $total . '</amount>
         <currencyCode>' . $currency . '</currencyCode>
         <payment>
            <opaqueData>
               <dataDescriptor>' . $_POST['dataDesc'] . '</dataDescriptor>
               <dataValue>' . $_POST['dataValue'] . '</dataValue>
            </opaqueData>
         </payment>';


        $transRequestXmlStr .= '<lineItems>';
        $itemid = 1;
        foreach ($cart as $k => $cartItem) {
            $transRequestXmlStr .= '<lineItem>';
            $transRequestXmlStr .= '<itemId>'. $itemid++  .'</itemId>';

            $qty = $cartItem['product']['qty'];
            $product = $cartItem['product']['object'];

            $transRequestXmlStr .= '<name>' . $th->shortText(trim(h($product->getName())),31, '') . '</name>';

            $description = '';
            $descriptions = [];

            // this code should be refactored into the core at some point
            foreach ($cartItem['productAttributes'] as $groupID => $valID) {
                $optionvalue = '';

                if (substr($groupID, 0, 2) == 'po') {
                    $groupID = str_replace("po", "", $groupID);
                    $optionvalue = StoreProductOptionItem::getByID($valID);

                    if ($optionvalue) {
                        $optionvalue = $optionvalue->getName();
                    }
                } elseif (substr($groupID, 0, 2) == 'pt') {
                    $groupID = str_replace("pt", "", $groupID);
                    $optionvalue = $valID;
                } elseif (substr($groupID, 0, 2) == 'pa') {
                    $groupID = str_replace("pa", "", $groupID);
                    $optionvalue = $valID;
                } elseif (substr($groupID, 0, 2) == 'ph') {
                    $groupID = str_replace("ph", "", $groupID);
                    $optionvalue = $valID;
                } elseif (substr($groupID, 0, 2) == 'pc') {
                    $groupID = str_replace("pc", "", $groupID);
                    $optionvalue = $valID;
                }

                $optiongroup = StoreProductOption::getByID($groupID);

                if ($optionvalue) {
                    $descriptions[] = ($optiongroup ? h($optiongroup->getName()) : '') . ': ' . ($optionvalue ? h($optionvalue) : '');
                }
            }

            $description = implode(', ', $descriptions);

            $transRequestXmlStr .= '<description>' .  $th->shortText(trim(h($description)), 255, '') . '</description>';
            $transRequestXmlStr .= '<quantity>' . $qty . '</quantity>';

            $productprice = $product->getActivePrice();

            if (!$productprice) {
                $productprice = (float)$cartItem['product']['customerPrice'];
            }

            $transRequestXmlStr .= '<unitPrice>' . $productprice . '</unitPrice>';

            $transRequestXmlStr .= '</lineItem>';
        }
        $transRequestXmlStr .= '</lineItems>';

        $transRequestXmlStr .= '<customer>
            <email>' . h($customer->getEmail()) . '</email>
         </customer>
         <billTo>
            <firstName>' . trim(h($customer->getValue('billing_first_name'))) . '</firstName>
            <lastName>' . trim(h($customer->getValue('billing_last_name'))) . '</lastName>
            <address>' . trim(h($customer->getAddressValue('billing_address', 'address1')) . ' ' . h($customer->getAddressValue('billing_address', 'address2'))) . '</address>
            <city>' . trim(h($customer->getAddressValue('billing_address', 'city'))) . '</city>
            <state>' . trim(h($customer->getAddressValue('billing_address', 'state_province'))) . '</state>
            <zip>' . h($customer->getAddressValue('billing_address', 'postal_code')) . '</zip>
            <country>' . h($customer->getAddressValue('billing_address', 'country')) . '</country>
            <phoneNumber>' . h($customer->getValue("billing_phone")) . '</phoneNumber>
         </billTo>';

        $shipping = StoreShippingMethod::getActiveShippingMethod();

        if ($shipping) {
            $transRequestXmlStr .= '<shipTo>
            <firstName>' . trim(h($customer->getValue('shipping_first_name'))) . '</firstName>
            <lastName>' . trim(h($customer->getValue('shipping_last_name'))) . '</lastName>
            <address>' . trim(h($customer->getAddressValue('shipping_address', 'address1')) . ' ' . h($customer->getAddressValue('billing_address', 'address2'))) . '</address>
            <city>' . trim(h($customer->getAddressValue('shipping_address', 'city'))) . '</city>
            <state>' . trim(h($customer->getAddressValue('shipping_address', 'state_province'))) . '</state>
            <zip>' . h($customer->getAddressValue('shipping_address', 'postal_code')) . '</zip>
            <country>' . h($customer->getAddressValue('shipping_address', 'country')) . '</country>
            </shipTo>';
        }


        $transRequestXmlStr .= '</transactionRequest>
</createTransactionRequest>';


        $url = 'https://api' . ($mode == 'test' ? 'test' : '') . '.authorize.net/xml/v1/request.api';

        try {    //setting the curl parameters.
            $ch = curl_init();
            if (FALSE === $ch)
                throw new Exception('failed to initialize');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $transRequestXmlStr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
            $content = curl_exec($ch);
            if (FALSE === $content)
                throw new Exception(curl_error($ch), curl_errno($ch));
            curl_close($ch);

            $xmlResult = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOWARNING);

            $resultcode = $xmlResult->transactionResponse->responseCode[0];

            if ($resultcode == '1' || $resultcode == '4') {
                return ['error' => 0, 'transactionReference' => $xmlResult->transactionResponse->transId[0]];
            } else {
                return ['error' => 1, 'errorMessage' => (string)$xmlResult->transactionResponse->errors[0]->error->errorText];
            }

        } catch (Exception $e) {
            return ['error' => 1, 'errorMessage' => t('An error occurred, the transaction did not succeed')];
        }
    }

    public function getPaymentMethodName()
    {
        return 'Authorize.Net';
    }

    public function getPaymentMethodDisplayName()
    {
        return $this->getPaymentMethodName();
    }

}

return __NAMESPACE__;