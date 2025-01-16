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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentExample extends PaymentModule
{
    const CONFIG_OS_OFFLINE = 'PAYMENTEXAMPLE_OS_OFFLINE';
    const CONFIG_PO_OFFLINE_ENABLED = 'PAYMENTEXAMPLE_PO_OFFLINE_ENABLED';
    const CONFIG_PO_EXTERNAL_ENABLED = 'PAYMENTEXAMPLE_PO_EXTERNAL_ENABLED';
    const CONFIG_PO_EMBEDDED_ENABLED = 'PAYMENTEXAMPLE_PO_EMBEDDED_ENABLED';
    const CONFIG_PO_BINARY_ENABLED = 'PAYMENTEXAMPLE_PO_BINARY_ENABLED';
    const MODULE_ADMIN_CONTROLLER = 'AdminConfigurePaymentExample';
    const HOOKS = [
        'actionObjectShopAddAfter',
        'paymentOptions',
        'displayAdminOrderLeft',
        'displayAdminOrderMainBottom',
        'displayCustomerAccount',
        'displayOrderConfirmation',
        'displayOrderDetail',
        'displayPaymentReturn',
        'displayPDFInvoice',
    ];
    public function __construct()
    {
        $this->name = 'paymentexample';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = '9PSB';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_,
        ];
        $this->controllers = [
            'account',
            'cancel',
            'external',
            'validation',
        ];
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $config = Configuration::getMultiple([
            'TEST_SECRETKEY',
            'TEST_PUBLICKEY',
            'LIVE_SECRETKEY',
            'LIVE_PUBLICKEY',
            'ENV_MODE',
        ]);
        parent::__construct();
        $this->displayName = $this->l('9PSB Payment');
        $this->description = $this->l('Make payments with 9PSB');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
    }
    public function install()
    {
        return parent::install()
        && (bool) $this->registerHook(static::HOOKS)
        && $this->installOrderState()
        && $this->installConfiguration()
        && $this->installTabs(); // 1 for test mode as default
    }
    public function uninstall()
    {
        if (
            !Configuration::deleteByName('TEST_SECRETKEY') || !Configuration::deleteByName('TEST_PUBLICKEY') || !Configuration::deleteByName('LIVE_PUBLICKEY') || !Configuration::deleteByName('LIVE_SECRETKEY') || !parent::uninstall()
        ) {
            return false;
        }
        return true;
    }
    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            // Update configuration values based on the form input
            Configuration::updateValue('TEST_PUBLICKEY', Tools::getValue('TEST_PUBLICKEY'));
            Configuration::updateValue('TEST_SECRETKEY', Tools::getValue('TEST_SECRETKEY'));
            Configuration::updateValue('LIVE_PUBLICKEY', Tools::getValue('LIVE_PUBLICKEY'));
            Configuration::updateValue('LIVE_SECRETKEY', Tools::getValue('LIVE_SECRETKEY'));
            Configuration::updateValue('CALLBACK_URL', Tools::getValue('CALLBACK_URL'));
            Configuration::updateValue('ENV_MODE', Tools::getValue('ENV_MODE'));
        }
        // Return the form HTML
        return $this->renderForm();
    }
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('9PSB Merchant Details'),
                    'icon' => 'icon-user',
                    'class' => 'legend-control',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'name' => 'custom_html',
                        'html_content' => '<br>',
                    ],
                    [
                        'type' => 'html',
                        'name' => 'custom_html',
                        'html_content' => '<br>',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Test Mode'),
                        'name' => 'ENV_MODE',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'mode_test',
                                'value' => 0,
                                'label' => $this->l('Test'),
                            ],
                            [
                                'id' => 'mode_live',
                                'value' => 1,
                                'label' => $this->l('Live'),
                            ],
                        ],
                        'class' => 'form-control',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Test Secret Key'),
                        'name' => 'TEST_SECRETKEY',
                        'class' => 'form-control',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Test Public Key'),
                        'name' => 'TEST_PUBLICKEY',
                        'class' => 'form-control',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Live Secret Key'),
                        'name' => 'LIVE_SECRETKEY',
                        'class' => 'form-control',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Live Public Key'),
                        'name' => 'LIVE_PUBLICKEY',
                        'class' => 'form-control',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Callback URL'),
                        'name' => 'CALLBACK_URL',
                        'required' => true,
                        'placeholder' => 'https://yourstore.com/callback',
                        'class' => 'form-control',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
        // Add inline styles
        $output = '<style>
            .legend-control {
                margin-bottom: 30px !important;
            }
            i.process-icon-save {
                display: none;
            }
            .form-control {
                background-color: #f9f9f9 !important;
                border-radius: 5px !important;
                border: 1px solid #ddd !important;
                padding: 10px !important;
                width: 400px !important;
            }
            .form-control:focus {
                border-color: #80bdff; /* Highlighted border on focus */
                outline: none; /* Remove default outline */
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25); /* Shadow effect */
            }
            .toggle-switch {
                display: inline-block; /* Custom toggle switch styling */
                margin-bottom: 10px;
                margin-top: 40px;
            }
            button#configuration_form_submit_btn {
                background: black !important;
                color: white !important;
                font-size: 14px !important;
                height: 40px !important;
                width: 120px !important;
                border-radius: 8px !important;
            }
        </style>';

        $helper = new HelperForm();
        $helper->submit_action = 'submit' . $this->name;
        $helper->fields_value['TEST_SECRETKEY'] = Configuration::get('TEST_SECRETKEY');
        $helper->fields_value['TEST_PUBLICKEY'] = Configuration::get('TEST_PUBLICKEY');
        $helper->fields_value['LIVE_SECRETKEY'] = Configuration::get('LIVE_SECRETKEY');
        $helper->fields_value['LIVE_PUBLICKEY'] = Configuration::get('LIVE_PUBLICKEY');
        $helper->fields_value['CALLBACK_URL'] = Configuration::get('CALLBACK_URL');
        $helper->fields_value['ENV_MODE'] = Configuration::get('ENV_MODE');

        // Render the form with the styles applied
        $output .= $helper->generateForm([$fields_form]);

        return $output;
    }
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/admin_custom.css');
    }
    /**
     * This hook called after a new Shop is created
     *
     * @param array $params
     */
    public function hookActionObjectShopAddAfter(array $params)
    {
        if (empty($params['object'])) {
            return;
        }

        /** @var Shop $shop */
        $shop = $params['object'];

        if (false === Validate::isLoadedObject($shop)) {
            return;
        }

        $this->addCheckboxCarrierRestrictionsForModule([(int) $shop->id]);
        $this->addCheckboxCountryRestrictionsForModule([(int) $shop->id]);

        if ($this->currencies_mode === 'checkbox') {
            $this->addCheckboxCurrencyRestrictionsForModule([(int) $shop->id]);
        } elseif ($this->currencies_mode === 'radio') {
            $this->addRadioCurrencyRestrictionsForModule([(int) $shop->id]);
        }
    }
    /**
     * Factory of PaymentOption for External Payment
     *
     * @return PaymentOption
     */
    private function getExternalPaymentOption()
    {
        $externalOption = new PaymentOption();
        $externalOption->setModuleName($this->name);
        $externalOption->setCallToActionText($this->l('Pay with 9PSB'));
        $externalOption->setAction($this->context->link->getModuleLink($this->name, 'external', [], true));
        $externalOption->setInputs([
            'token' => [
                'name' => 'token',
                'type' => 'hidden',
                'value' => '[5cbfniD+(gEV<59lYbG/,3VmHiE<U46;#G9*#NP#X.FA§]sb%ZG?5Q{xQ4#VM|7',
            ],
        ]);
        $externalOption->setAdditionalInformation($this->context->smarty->fetch('module:paymentexample/views/templates/front/paymentOptionExternal.tpl'));
        $externalOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.png'));

        return $externalOption;
    }
    /**
     * This hook is used to display additional information on BO Order View, under Payment block
     *
     * @since PrestaShop 1.7.7 This hook is replaced by displayAdminOrderMainBottom on migrated BO Order View
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminOrderLeft(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int) $params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/hook/displayAdminOrderLeft.tpl');
    }
    /**
     * This hook is used to display additional information on BO Order View, under Payment block
     *
     * @since PrestaShop 1.7.7 This hook replace displayAdminOrderLeft on migrated BO Order View
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminOrderMainBottom(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int) $params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/hook/displayAdminOrderMainBottom.tpl');
    }

    /**
     * This hook is used to display information in customer account
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayCustomerAccount(array $params)
    {
        $this->context->smarty->assign([
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/hook/displayCustomerAccount.tpl');
    }

    /**
     * This hook is used to display additional information on order confirmation page
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/hook/displayOrderConfirmation.tpl');
    }

    /**
     * This hook is used to display additional information on FO (Guest Tracking and Account Orders)
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayOrderDetail(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/hook/displayOrderDetail.tpl');
    }

    /**
     * This hook is used to display additional information on bottom of order confirmation page
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayPaymentReturn(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/hook/displayPaymentReturn.tpl');
    }

    /**
     * This hook is used to display additional information on Invoice PDF
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayPDFInvoice(array $params)
    {
        if (empty($params['object'])) {
            return '';
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = $params['object'];

        if (false === Validate::isLoadedObject($orderInvoice)) {
            return '';
        }

        $order = $orderInvoice->getOrder();

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/hook/displayPDFInvoice.tpl');
    }

    /**
     * Check if currency is allowed in Payment Preferences
     *
     * @param Cart $cart
     *
     * @return bool
     */
    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        /** @var array $currencies_module */
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (empty($currencies_module)) {
            return false;
        }

        foreach ($currencies_module as $currency_module) {
            if ($currency_order->id == $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a form for Embedded Payment
     *
     * @return string
     */
    private function generateEmbeddedForm()
    {
        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation', ['option' => 'embedded'], true),
        ]);

        return $this->context->smarty->fetch('module:paymentexample/views/templates/front/paymentOptionEmbeddedForm.tpl');
    }

    /**
     * @return bool
     */
    private function installOrderState()
    {
        return $this->createOrderState(
            static::CONFIG_OS_OFFLINE,
            [
                'en' => 'Awaiting offline payment',
            ],
            '#00ffff',
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            true,
            'awaiting-offline-payment'
        );
    }

    /**
     * Create custom OrderState used for payment
     *
     * @param string $configurationKey Configuration key used to store OrderState identifier
     * @param array $nameByLangIsoCode An array of name for all languages, default is en
     * @param string $color Color of the label
     * @param bool $isLogable consider the associated order as validated
     * @param bool $isPaid set the order as paid
     * @param bool $isInvoice allow a customer to download and view PDF versions of his/her invoices
     * @param bool $isShipped set the order as shipped
     * @param bool $isDelivery show delivery PDF
     * @param bool $isPdfDelivery attach delivery slip PDF to email
     * @param bool $isPdfInvoice attach invoice PDF to email
     * @param bool $isSendEmail send an email to the customer when his/her order status has changed
     * @param string $template Only letters, numbers and underscores are allowed. Email template for both .html and .txt
     * @param bool $isHidden hide this status in all customer orders
     * @param bool $isUnremovable Disallow delete action for this OrderState
     * @param bool $isDeleted Set OrderState deleted
     *
     * @return bool
     */
    private function createOrderState(
        $configurationKey,
        array $nameByLangIsoCode,
        $color,
        $isLogable = false,
        $isPaid = false,
        $isInvoice = false,
        $isShipped = false,
        $isDelivery = false,
        $isPdfDelivery = false,
        $isPdfInvoice = false,
        $isSendEmail = false,
        $template = '',
        $isHidden = false,
        $isUnremovable = true,
        $isDeleted = false
    ) {
        $tabNameByLangId = [];

        foreach ($nameByLangIsoCode as $langIsoCode => $name) {
            foreach (Language::getLanguages(false) as $language) {
                if (Tools::strtolower($language['iso_code']) === $langIsoCode) {
                    $tabNameByLangId[(int) $language['id_lang']] = $name;
                } elseif (isset($nameByLangIsoCode['en'])) {
                    $tabNameByLangId[(int) $language['id_lang']] = $nameByLangIsoCode['en'];
                }
            }
        }

        $orderState = new OrderState();
        $orderState->module_name = $this->name;
        $orderState->name = $tabNameByLangId;
        $orderState->color = $color;
        $orderState->logable = $isLogable;
        $orderState->paid = $isPaid;
        $orderState->invoice = $isInvoice;
        $orderState->shipped = $isShipped;
        $orderState->delivery = $isDelivery;
        $orderState->pdf_delivery = $isPdfDelivery;
        $orderState->pdf_invoice = $isPdfInvoice;
        $orderState->send_email = $isSendEmail;
        $orderState->hidden = $isHidden;
        $orderState->unremovable = $isUnremovable;
        $orderState->template = $template;
        $orderState->deleted = $isDeleted;
        $result = (bool) $orderState->add();

        if (false === $result) {
            $this->_errors[] = sprintf(
                'Failed to create OrderState %s',
                $configurationKey
            );

            return false;
        }

        $result = (bool) Configuration::updateGlobalValue($configurationKey, (int) $orderState->id);

        if (false === $result) {
            $this->_errors[] = sprintf(
                'Failed to save OrderState %s to Configuration',
                $configurationKey
            );

            return false;
        }

        $orderStateImgPath = $this->getLocalPath() . 'views/img/orderstate/' . $configurationKey . '.png';

        if (false === (bool) Tools::file_exists_cache($orderStateImgPath)) {
            $this->_errors[] = sprintf(
                'Failed to find icon file of OrderState %s',
                $configurationKey
            );

            return false;
        }

        if (false === (bool) Tools::copy($orderStateImgPath, _PS_ORDER_STATE_IMG_DIR_ . $orderState->id . '.gif')) {
            $this->_errors[] = sprintf(
                'Failed to copy icon of OrderState %s',
                $configurationKey
            );

            return false;
        }

        return true;
    }

    /**
     * Delete custom OrderState used for payment
     * We mark them as deleted to not break passed Orders
     *
     * @return bool
     */
    private function deleteOrderState()
    {
        $result = true;

        $orderStateCollection = new PrestaShopCollection('OrderState');
        $orderStateCollection->where('module_name', '=', $this->name);
        /** @var OrderState[] $orderStates */
        $orderStates = $orderStateCollection->getAll();

        foreach ($orderStates as $orderState) {
            $orderState->deleted = true;
            $result = $result && (bool) $orderState->save();
        }

        return $result;
    }

    /**
     * Install default module configuration
     *
     * @return bool
     */
    private function installConfiguration()
    {
        return Configuration::updateGlobalValue('TEST_PUBLICKEY', '')
            && Configuration::updateGlobalValue('TEST_SECRETKEY', '')
            && Configuration::updateGlobalValue('LIVE_PUBLICKEY', '')
            && Configuration::updateGlobalValue('LIVE_SECRETKEY', '')
            && Configuration::updateGlobalValue('CALLBACK_URL', '')
            && Configuration::updateGlobalValue('ENV_MODE', 0);
    }

    /**
     * Uninstall module configuration
     *
     * @return bool
     */
    private function uninstallConfiguration()
    {
        return Configuration::deleteByName('TEST_PUBLICKEY')
            && Configuration::deleteByName('TEST_SECRETKEY')
            && Configuration::deleteByName('LIVE_PUBLICKEY')
            && Configuration::deleteByName('LIVE_SECRETKEY')
            && Configuration::deleteByName('CALLBACK_URL')
            && Configuration::deleteByName('ENV_MODE');
    }

    /**
     * Install Tabs
     *
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return (bool) $tab->add();
    }

    /**
     * Uninstall Tabs
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return array Should always return an array
     */
    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        $paymentOptions = [];

        if (Configuration::get(static::CONFIG_PO_EXTERNAL_ENABLED)) {
            $paymentOptions[] = $this->getExternalPaymentOption();
        }

        return $paymentOptions;
    }
}
