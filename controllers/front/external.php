<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
/**
 * This Controller simulate an external payment gateway
 */
class PaymentExampleExternalModuleFrontController extends ModuleFrontController
{
    protected $generalErrorMessage;
    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $testSecretKey = Configuration::get('TEST_SECRETKEY');
        $testPublicKey = Configuration::get('TEST_PUBLICKEY');
        $liveSecretKey = Configuration::get('LIVE_SECRETKEY');
        $livePublicKey = Configuration::get('LIVE_PUBLICKEY');
        $mode = Configuration::get('ENV_MODE');

        $privateKey = $mode === '0' ? $testSecretKey : $liveSecretKey;
        $publicKey = $mode === '0' ? $testPublicKey : $livePublicKey;

        $baseUrl = $mode === '0'
            ? 'https://9psb-sonar-test.9psb.com.ng/gateway-api/v1/authenticate'
            : 'https://bank9jacollectapi.9psb.com.ng/gateway-api/v1/authenticate';

        $data = [
            'privateKey' => $privateKey,
            'publicKey' => $publicKey,
        ];

        $contextOptions = [
            'http' => [
                'method' => 'POST',
                'header' => "\r\nContent-Type: application/json\r\n",
                'content' => json_encode($data),
            ],
        ];

        $context = stream_context_create($contextOptions);
        $response = Tools::file_get_contents($baseUrl, false, $context);

        if ($response === false) {
            PrestaShopLogger::addLog('Failed to get a response from the authenticate endpoint.');
            return null;
        }

        PrestaShopLogger::addLog('Response received: ' . $response);
        $result = json_decode($response, true);

        if ($result && $result['code'] === '00' && isset($result['data']['accessToken'])) {
            return $result['data']['accessToken'];
        }

        $errorMessage = $result['message'] ?? 'Unknown error occurred';
        PrestaShopLogger::addLog('Failed to retrieve access token. Response: ' . json_encode($result));
        return null;
    }

    /**
     * Initializes content.
     */
    public function initContent()
    {
        parent::initContent();

        $customer = $this->context->customer;
        $cart = $this->context->cart;

        if ($customer->isLogged()) {
            // Extracting data from the order
            $orderAmount = $cart->getOrderTotal(true, Cart::BOTH);
            $customerName = $customer->firstname . ' ' . $customer->lastname;
            $customerEmail = $customer->email;

            // Generate random transaction ID
            $transactionId = $this->generateTransactionId($customerName, $customerEmail);

            // Attempt to authenticate and get the access token
            $accessToken = $this->authenticate();
            if ($accessToken) {
                // Attempt to initiate the payment and get the payment URL
                $paymentUrl = $this->initiatePayment($transactionId, $accessToken, $orderAmount, $customerEmail, $customerName);
                if ($paymentUrl) {
                    // Redirect to the payment URL
                    Tools::redirect($paymentUrl);
                } else {
                    PrestaShopLogger::addLog('Failed to get the payment URL.');
                    $this->context->smarty->assign('errorMessage', $this->generalErrorMessage);
                    $this->setTemplate('module:paymentexample/views/templates/front/error.tpl');
                }
            } else {
                PrestaShopLogger::addLog('Authentication failed.');
                $this->context->smarty->assign('errorMessage', 'Authentication failed. Please try again later.');
                $this->setTemplate('module:paymentexample/views/templates/front/error.tpl');
            }
        } else {
            $this->context->smarty->assign('errorMessage', 'You need to be logged in to view transaction details.');
            $this->setTemplate('module:randomtransactionid/views/templates/front/error.tpl');
        }
    }

    private function generateTransactionId($customerName, $customerEmail)
    {
        $timestamp = time();  // Current Unix timestamp
        $randomString = bin2hex(random_bytes(5));  // Generate a random 5-byte string
        return strtoupper(substr($customerName, 0, 3) . '-' . substr($customerEmail, 0, 3) . '-' . $timestamp . '-' . $randomString);
    }

    private function getLatestOrderByCustomerId($customerId)
    {
        $order = Order::getCustomerOrders($customerId, true); // Get the latest order (status = 'paid' or 'confirmed')
        return $order ? reset($order) : null;
    }

    /**
     * Initiates a payment request.
     *
     * @param string $txn_code Transaction code
     * @param string $accessToken Access token for authentication
     * @param float $amount Payment amount
     * @param string $email Customer email
     * @param string $name Customer name
     * @return string|null
     */
    public function initiatePayment($txn_code, $accessToken, $amount, $email, $name)
    {
        $callback_url = Configuration::get('CALLBACK_URL');

        $data = [
            'amount' => $amount,
            'callbackUrl' => $callback_url,
            'customer' => [
                'name' => $name,
                'email' => $email,
                'phoneNo' => '',
            ],
            'merchantReference' => $txn_code,
            'narration' => '9PSB Payment',
            'amountType' => '',
        ];

        $contextOptions = [
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer $accessToken\r\nContent-Type: application/json\r\n",
                'content' => json_encode($data),
            ],
        ];

        $mode = Configuration::get('ENV_MODE');

        $baseUrl = ($mode == '0') ? 'https://9psb-sonar-test.9psb.com.ng/gateway-api/v1/initiate-payment' : 'https://9psb-sonar-test.9psb.com.ng/gateway-api/v1/initiate-payment';

        $context = stream_context_create($contextOptions);
        $url = $baseUrl;
        $response = Tools::file_get_contents($url, false, $context);

        if ($response === false) {
            PrestaShopLogger::addLog('Failed to get a response from the initiatePayment endpoint.');
            return null;
        }

        PrestaShopLogger::addLog('Initiate Payment Response received: ' . $response);
        $result = json_decode($response, true);

        if ($result && $result['code'] === '00' && isset($result['data']['link'])) {
            return $result['data']['link'];
        } else {
            $errorMessage = $result['message'] ?? 'Unknown error occurred';
            $this->generalErrorMessage = $errorMessage;
            PrestaShopLogger::addLog('Failed to process. Response: ' . json_encode($result));
            return null;
        }
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        if (!Configuration::get(PaymentExample::CONFIG_PO_EXTERNAL_ENABLED)) {
            return false;
        }

        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }
}
