<?php

class Shopware_Plugins_Frontend_Vrpayecommerce_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    private static $repository;
    private $imgPath = 'engine/Shopware/Plugins/Community/Frontend/Vrpayecommerce/Views/frontend/_resources/images/';
    private $jsPath =
    '/engine/Shopware/Plugins/Community/Frontend/Vrpayecommerce/Views/frontend/easycredit/easycredit_notification.js';

    private function getRepository()
    {
        if (self::$repository === null) {
            self::$repository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        }
        return self::$repository;
    }

    public function getCapabilities()
    {
        return array(
            'install' => true,
            'update' => true,
            'enable' => true
        );
    }

    /**
     * Returns the label of the plugin as string
     *
     * @return string
     */
    public function getLabel()
    {
        return 'VR pay eCommerce';
    }

    /**
     * Returns the version of plugin as string.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.3.0';
    }

    /**
     * Informations about this plugin
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'autor'     => 'VR pay eCommerce',
            'copyright' => 'Copyright (c) 2017, VR pay eCommerce',
            'label' => $this->getLabel(),
            'supplier' => 'VR pay eCommerce',
            'description' => 'VR pay eCommerce plugin v'.$this->getVersion(),
            'support' => '',
            'link' => 'http://www.vr-epay.info/'
        );
    }

    /**
     * Installs the plugin
     *
     * @return bool
     */
    public function install()
    {
        $this->uninstall();
        $this->registerEventHandlers();
        $this->registerPaymentMethods();
        $this->createConfiguration();
        $this->addConfigTranslations();
        $this->insertTranslations();
        $this->createPaymentStatus();
        $this->createTableRecurring();
        $this->alterValidateRecurring();
        $this->alterOrderInformation();
        $languageId = $this->getLanguage($this->getLanguageId());
        return array('success' => true, 'message' => $this->getNotificationMessage($languageId));
    }

    /**
     * Uninstalls the plugin
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->deleteTranslations();
        $this->deactivePayment();
        $this->deletePaymentStatus();
        $this->deleteOrderInformation();
        return true;
    }

    /**
     * shopware default function for running plugin update process
     *
     * @return boolean
     */
    public function update($priorVersion)
    {
        if (version_compare($this->Info()['version'], '1.2.13', '<')) {
            $this->updateEasyCreditTranslations();
        }
        if (version_compare($this->Info()['version'], '1.2.14', '<')) {
            $this->updateKlarnaMethod();
        }
        if (version_compare($this->Info()['version'], '1.2.20', '<')) {
            $this->addEasyCreditConfirmationButtonTranslation();
        }
        if(version_compare($priorVersion, '1.3.0', '<'))
        {
            $attribute = [];
            $attribute['elementCH'] = 'BACKEND_CH_GENERAL';
            $attribute['pLabel'] = 'General';
            $this->createTextFieldConfig(
                $this->Form(),
                $attribute['elementCH'] . '_BEARER_TOKEN',
                $attribute['pLabel'].' Access Token',
                ''
            );
            Shopware()->Db()->query('UPDATE s_core_config_elements SET position = -1 WHERE name = "BACKEND_CH_GENERAL_BEARER_TOKEN"');
            Shopware()->Db()->query('UPDATE s_core_config_elements SET position = -2 WHERE name = "BACKEND_PM_GENERAL"');
        }

        return true;
    }

    /**
     * Information about payment method
     *
     * @var array
     */
    public $paymentMethods = array(
      'vrpay_cc' => array(
          'description' => 'Credit Cards',
          'identifier' => 'FRONTEND_PM_CC',
          'element' => 'BACKEND_CH_CC_ACTIVE',
          'logo' => '',
          'logo_de' => ''
      ),
      'vrpay_ccsaved' => array(
          'description' => 'Credit Cards (Recurring)',
          'identifier' => 'FRONTEND_PM_CCSAVED',
          'element' => 'BACKEND_CH_CCSAVED_ACTIVE',
          'logo' => '',
          'logo_de' => ''
      ),
      'vrpay_dd' => array(
          'description' => 'Direct Debit',
          'identifier' => 'FRONTEND_PM_DD',
          'element' => 'BACKEND_CH_DD_ACTIVE',
          'logo' => 'sepa.png',
          'logo_de' => 'sepa.png'
      ),
      'vrpay_ddsaved' => array(
          'description' => 'Direct Debit (Recurring)',
          'identifier' => 'FRONTEND_PM_DDSAVED',
          'element' => 'BACKEND_CH_DDSAVED_ACTIVE',
          'logo' => 'sepa.png',
          'logo_de' => 'sepa.png'
      ),
      'vrpay_giropay' => array(
          'description' => 'giropay',
          'identifier' => 'FRONTEND_PM_GIROPAY',
          'element' => 'BACKEND_CH_GIROPAY_ACTIVE',
          'logo' => 'giropay.png',
          'logo_de' => 'giropay.png'
      ),
      'vrpay_paypal' => array(
          'description' => 'PayPal',
          'identifier' => 'FRONTEND_PM_PAYPAL',
          'element' => 'BACKEND_CH_PAYPAL_ACTIVE',
          'logo' => 'paypal.png',
          'logo_de' => 'paypal.png'
      ),
      'vrpay_paypalsaved' => array(
          'description' => 'PayPal (Recurring)',
          'identifier' => 'FRONTEND_PM_PAYPALSAVED',
          'element' => 'BACKEND_CH_PAYPALSAVED_ACTIVE',
          'logo' => 'paypal.png',
          'logo_de' => 'paypal.png'
      ),
      'vrpay_paydirekt' => array(
          'description' => 'paydirekt',
          'identifier' => 'FRONTEND_PM_PAYDIREKT',
          'element' => 'BACKEND_CH_PAYDIREKT_ACTIVE',
          'logo' => 'paydirekt.png',
          'logo_de' => 'paydirekt.png'
      ),
      'vrpay_klarnaobt' => array(
          'description' => 'Online Bank Transfer.',
          'identifier' => 'FRONTEND_PM_KLARNAOBT',
          'element' => 'BACKEND_CH_KLARNAOBT_ACTIVE',
          'logo' => 'klarnaobt_en.png',
          'logo_de' => 'klarnaobt_de.png'
      ),
        'vrpay_easycredit' => array(
          'description' => 'ratenkauf by easyCredit',
          'identifier' => 'FRONTEND_PM_EASYCREDIT',
          'element' => 'BACKEND_CH_EASYCREDIT_ACTIVE',
          'logo' => 'easycredit.png',
          'logo_de' => 'easycredit.png'
      )
    );

    /**
     * Plugin Translation config
     */
    private function addConfigTranslations()
    {
        $form = $this->Form();
        $translations = array(
            'de_DE' => array(
                       'BACKEND_PM_GENERAL' => 'Allgemeine Einstellungen',
                       'BACKEND_CH_GENERAL_BEARER_TOKEN' => 'Access Token',
                       'BACKEND_CH_GENERAL_LOGIN' => 'User-ID',
                       'BACKEND_CH_GENERAL_PASSWORD' => 'Passwort',
                       'BACKEND_CH_GENERAL_RECURRING' => 'Recurring',
                       'BACKEND_CH_GENERAL_MERCHANTEMAIL' => 'Händler E-Mail-Adressee',
                       'BACKEND_CH_GENERAL_MERCHANTNO' => 'Händler Nr. (VR pay)',
                       'BACKEND_CH_GENERAL_SHOPURL' => 'Shop URL',
                       'BACKEND_CH_GENERAL_VERSION_TRACKER' => 'Version Tracker',
                       'BACKEND_CH_GENERAL_MERCHANT_LOCATION' => 'Firmensitz',

                       'BACKEND_PM_CC' => 'Kreditkarte',
                       'BACKEND_CH_CC_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_CC_SERVER' => 'Server',
                       'BACKEND_CH_CC_MODE' => 'Transaktions Modus',
                       'BACKEND_CH_CC_VISA' => 'Visa Aktiviert',
                       'BACKEND_CH_CC_MASTER' => 'MasterCard Aktiviert',
                       'BACKEND_CH_CC_AMEX' => 'American Express Aktiviert',
                       'BACKEND_CH_CC_DINERS' => 'Diners Aktiviert',
                       'BACKEND_CH_CC_JCB' => 'JCB Aktiviert',
                       'BACKEND_CH_CC_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_CCSAVED' => 'Kreditkarte (Recurring)',
                       'BACKEND_CH_CCSAVED_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_CCSAVED_SERVER' => 'Server',
                       'BACKEND_CH_CCSAVED_MODE' => 'Transaktions Modus',
                       'BACKEND_CH_CCSAVED_VISA' => 'Visa Aktiviert',
                       'BACKEND_CH_CCSAVED_MASTER' => 'MasterCard Aktiviert',
                       'BACKEND_CH_CCSAVED_AMEX' => 'American Express Aktiviert',
                       'BACKEND_CH_CCSAVED_DINERS' => 'Diners Aktiviert',
                       'BACKEND_CH_CCSAVED_JCB' => 'JCB Aktiviert',
                       'BACKEND_CH_CCSAVED_AMOUNT' => 'Betrag für Registrierung',

                       'BACKEND_CH_CCSAVED_MULTICHANNEL' => 'Multi-Kanal',
                       'BACKEND_CH_CCSAVED_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_CCSAVED_CHANNELMOTO' => 'Entity-ID MOTO',

                       'BACKEND_PM_DC' => 'Debit Karte',
                       'BACKEND_CH_DC_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_DC_SERVER' => 'Server',
                       'BACKEND_CH_DC_MODE' => 'Transaktions Modus',
                       'BACKEND_CH_DC_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_DD' => 'Lastschrift',
                       'BACKEND_CH_DD_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_DD_SERVER' => 'Server',
                       'BACKEND_CH_DD_MODE' => 'Transaktions Modus',
                       'BACKEND_CH_DD_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_DDSAVED' => 'Lastschrift (Recurring)',
                       'BACKEND_CH_DDSAVED_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_DDSAVED_SERVER' => 'Server',
                       'BACKEND_CH_DDSAVED_AMOUNT' => 'Betrag für Registrierung',
                       'BACKEND_CH_DDSAVED_MODE' => 'Transaktions Modus',
                       'BACKEND_CH_DDSAVED_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_EPS' => 'eps',
                       'BACKEND_CH_EPS_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_EPS_SERVER' => 'Server',
                       'BACKEND_CH_EPS_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_GIROPAY' => 'Giropay',
                       'BACKEND_CH_GIROPAY_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_GIROPAY_SERVER' => 'Server',
                       'BACKEND_CH_GIROPAY_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_IDEAL' => 'iDeal',
                       'BACKEND_CH_IDEAL_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_IDEAL_SERVER' => 'Server',
                       'BACKEND_CH_IDEAL_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_KLARNAPAYLATER' => 'Rechnung.',
                       'BACKEND_CH_KLARNAPAYLATER_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_KLARNAPAYLATER_SERVER' => 'Server',
                       'BACKEND_CH_KLARNAPAYLATER_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_KLARNASLICEIT' => 'Ratenkauf.',
                       'BACKEND_CH_KLARNASLICEIT_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_KLARNASLICEIT_SERVER' => 'Server',
                       'BACKEND_CH_KLARNASLICEIT_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_KLARNASLICEIT_PCLASS' => 'Ratenplan (PCLASS)',

                       'BACKEND_PM_MASTERPASS' => 'Masterpass',
                       'BACKEND_CH_MASTERPASS_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_MASTERPASS_SERVER' => 'Server',
                       'BACKEND_CH_MASTERPASS_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_PAYPAL' => 'PayPal',
                       'BACKEND_CH_PAYPAL_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_PAYPAL_SERVER' => 'Server',
                       'BACKEND_CH_PAYPAL_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_PAYPALSAVED' => 'PayPal (Recurring)',
                       'BACKEND_CH_PAYPALSAVED_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_PAYPALSAVED_SERVER' => 'Server',
                       'BACKEND_CH_PAYPALSAVED_AMOUNT' => 'Betrag für Registrierung',
                       'BACKEND_CH_PAYPALSAVED_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_PAYDIREKT' => 'paydirekt',
                       'BACKEND_CH_PAYDIREKT_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_PAYDIREKT_SERVER' => 'Server',
                       'BACKEND_CH_PAYDIREKT_MODE' => 'Transaktions Modus',
                       'BACKEND_CH_PAYDIREKT_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_PAYDIREKT_PAYMENT_IS_PARTIAL' => 'Anteiliger Capture oder anteiliger Refund',
                       'BACKEND_CH_PAYDIREKT_MINIMUM_AGE' => 'Mindestalter',

                       'BACKEND_PM_KLARNAOBT' => 'Sofort.',
                       'BACKEND_CH_KLARNAOBT_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_KLARNAOBT_SERVER' => 'Server',
                       'BACKEND_CH_KLARNAOBT_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_SWISSPOSTFINANCE' => 'Swiss Postfinance',
                       'BACKEND_CH_SWISSPOSTFINANCE_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_SWISSPOSTFINANCE_SERVER' => 'Server',
                       'BACKEND_CH_SWISSPOSTFINANCE_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_EASYCREDIT' => 'ratenkauf by easyCredit',
                       'BACKEND_CH_EASYCREDIT_ACTIVE' => 'Aktiviert',
                       'BACKEND_CH_EASYCREDIT_SERVER' => 'Server',
                       'BACKEND_CH_EASYCREDIT_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_EASYCREDIT_SHOPNAME' => 'Shopname',
            ),
            'en_GB' => array(
                       'BACKEND_PM_GENERAL' => 'General Setting',
                       'BACKEND_CH_GENERAL_BEARER_TOKEN' => 'Access Token',
                       'BACKEND_CH_GENERAL_LOGIN' => 'User-ID',
                       'BACKEND_CH_GENERAL_PASSWORD' => 'Password',
                       'BACKEND_CH_GENERAL_RECURRING' => 'Recurring',
                       'BACKEND_CH_GENERAL_MERCHANTEMAIL' => 'Merchant Email',
                       'BACKEND_CH_GENERAL_MERCHANTNO' => 'Merchant No. (VR pay)',
                       'BACKEND_CH_GENERAL_SHOPURL' => 'Shop URL',
                       'BACKEND_CH_GENERAL_VERSION_TRACKER' => 'Version Tracker',
                       'BACKEND_CH_GENERAL_MERCHANT_LOCATION' => 'Merchant Location',

                       'BACKEND_PM_CC' => 'Credit Cards',
                       'BACKEND_CH_CC_ACTIVE' => 'Enabled',
                       'BACKEND_CH_CC_SERVER' => 'Server',
                       'BACKEND_CH_CC_MODE' => 'Transaction Mode',
                       'BACKEND_CH_CC_VISA' => 'Visa Enabled',
                       'BACKEND_CH_CC_MASTER' => 'MasterCard Enabled',
                       'BACKEND_CH_CC_AMEX' => 'American Express Enabled',
                       'BACKEND_CH_CC_DINERS' => 'Diners Enabled',
                       'BACKEND_CH_CC_JCB' => 'JCB Enabled',
                       'BACKEND_CH_CC_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_CCSAVED' => 'Credit Cards (Recurring)',
                       'BACKEND_CH_CCSAVED_ACTIVE' => 'Enabled',
                       'BACKEND_CH_CCSAVED_SERVER' => 'Server',
                       'BACKEND_CH_CCSAVED_MODE' => 'Transaction Mode',
                       'BACKEND_CH_CCSAVED_VISA' => 'Visa Enabled',
                       'BACKEND_CH_CCSAVED_MASTER' => 'MasterCard Enabled',
                       'BACKEND_CH_CCSAVED_AMEX' => 'American Express Enabled',
                       'BACKEND_CH_CCSAVED_DINERS' => 'Diners Enabled',
                       'BACKEND_CH_CCSAVED_JCB' => 'JCB Enabled',
                       'BACKEND_CH_CCSAVED_AMOUNT' => 'Amount for Registration',
                       'BACKEND_CH_CCSAVED_MULTICHANNEL' => 'Multichannel',
                       'BACKEND_CH_CCSAVED_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_CCSAVED_CHANNELMOTO' => 'Entity-ID MOTO',

                       'BACKEND_PM_DC' => 'Debit Card',
                       'BACKEND_CH_DC_ACTIVE' => 'Enabled',
                       'BACKEND_CH_DC_SERVER' => 'Server',
                       'BACKEND_CH_DC_MODE' => 'Transaction Mode',
                       'BACKEND_CH_DC_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_DD' => 'Direct Debit',
                       'BACKEND_CH_DD_ACTIVE' => 'Enabled',
                       'BACKEND_CH_DD_SERVER' => 'Server',
                       'BACKEND_CH_DD_MODE' => 'Transaction Mode',
                       'BACKEND_CH_DD_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_DDSAVED' => 'Direct Debit (Recurring)',
                       'BACKEND_CH_DDSAVED_ACTIVE' => 'Enabled',
                       'BACKEND_CH_DDSAVED_SERVER' => 'Server',
                       'BACKEND_CH_DDSAVED_MODE' => 'Transaction Mode',
                       'BACKEND_CH_DDSAVED_AMOUNT' => 'Amount for Registration',
                       'BACKEND_CH_DDSAVED_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_EPS' => 'eps',
                       'BACKEND_CH_EPS_ACTIVE' => 'Enabled',
                       'BACKEND_CH_EPS_SERVER' => 'Server',
                       'BACKEND_CH_EPS_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_GIROPAY' => 'giropay',
                       'BACKEND_CH_GIROPAY_ACTIVE' => 'Enabled',
                       'BACKEND_CH_GIROPAY_SERVER' => 'Server',
                       'BACKEND_CH_GIROPAY_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_IDEAL' => 'iDeal',
                       'BACKEND_CH_IDEAL_ACTIVE' => 'Enabled',
                       'BACKEND_CH_IDEAL_SERVER' => 'Server',
                       'BACKEND_CH_IDEAL_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_KLARNAPAYLATER' => 'Pay later.',
                       'BACKEND_CH_KLARNAPAYLATER_ACTIVE' => 'Enabled',
                       'BACKEND_CH_KLARNAPAYLATER_SERVER' => 'Server',
                       'BACKEND_CH_KLARNAPAYLATER_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_KLARNASLICEIT_PCLASS' => 'Installment Plan (PCLASS)',

                       'BACKEND_PM_KLARNASLICEIT' => 'Slice it.',
                       'BACKEND_CH_KLARNASLICEIT_ACTIVE' => 'Enabled',
                       'BACKEND_CH_KLARNASLICEIT_SERVER' => 'Server',
                       'BACKEND_CH_KLARNASLICEIT_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_MASTERPASS' => 'Masterpass',
                       'BACKEND_CH_MASTERPASS_ACTIVE' => 'Enabled',
                       'BACKEND_CH_MASTERPASS_SERVER' => 'Server',
                       'BACKEND_CH_MASTERPASS_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_PAYPAL' => 'PayPal',
                       'BACKEND_CH_PAYPAL_ACTIVE' => 'Enabled',
                       'BACKEND_CH_PAYPAL_SERVER' => 'Server',
                       'BACKEND_CH_PAYPAL_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_PAYPALSAVED' => 'PayPal (Recurring)',
                       'BACKEND_CH_PAYPALSAVED_ACTIVE' => 'Enabled',
                       'BACKEND_CH_PAYPALSAVED_SERVER' => 'Server',
                       'BACKEND_CH_PAYPALSAVED_AMOUNT' => 'Amount for Registration',
                       'BACKEND_CH_PAYPALSAVED_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_PAYDIREKT' => 'paydirekt',
                       'BACKEND_CH_PAYDIREKT_ACTIVE' => 'Enabled',
                       'BACKEND_CH_PAYDIREKT_SERVER' => 'Server',
                       'BACKEND_CH_PAYDIREKT_MODE' => 'Transaction Mode',
                       'BACKEND_CH_PAYDIREKT_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_PAYDIREKT_PAYMENT_IS_PARTIAL' => 'Partial Capture or Refund',
                       'BACKEND_CH_PAYDIREKT_MINIMUM_AGE' => 'Minimum Age',

                       'BACKEND_PM_KLARNAOBT' => 'Online Bank Transfer.',
                       'BACKEND_CH_KLARNAOBT_ACTIVE' => 'Enabled',
                       'BACKEND_CH_KLARNAOBT_SERVER' => 'Server',
                       'BACKEND_CH_KLARNAOBT_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_SWISSPOSTFINANCE' => 'Swiss Postfinance',
                       'BACKEND_CH_SWISSPOSTFINANCE_ACTIVE' => 'Enabled',
                       'BACKEND_CH_SWISSPOSTFINANCE_SERVER' => 'Server',
                       'BACKEND_CH_SWISSPOSTFINANCE_CHANNEL' => 'Entity-ID',

                       'BACKEND_PM_EASYCREDIT' => 'ratenkauf by easyCredit',
                       'BACKEND_CH_EASYCREDIT_ACTIVE' => 'Enabled',
                       'BACKEND_CH_EASYCREDIT_SERVER' => 'Server',
                       'BACKEND_CH_EASYCREDIT_CHANNEL' => 'Entity-ID',
                       'BACKEND_CH_EASYCREDIT_SHOPNAME' => 'Shop name',
            )
        );

        $translationsTooltips = array(
            'de_DE' => array(
                       'BACKEND_CH_GENERAL_MERCHANTNO' => 'Ihre Kundennummer/Händlernummer bei VR pay',
                       'BACKEND_CH_CCSAVED_AMOUNT' => 'Betrag, der bei der Registrierung von Zahlungsarten '
                        . 'gebucht und gutgeschrieben wird (wenn die Registrierung ohne Checkout erfolgt)',
                       'BACKEND_CH_DDSAVED_AMOUNT' => 'Betrag, der bei der Registrierung von Zahlungsarten '
                        . 'gebucht und gutgeschrieben wird (wenn die Registrierung ohne Checkout erfolgt)',
                       'BACKEND_CH_PAYPALSAVED_AMOUNT' => 'Betrag, der bei der Registrierung von Zahlungsarten '
                        . 'gebucht und gutgeschrieben wird (wenn die Registrierung ohne Checkout erfolgt)',
                       'BACKEND_CH_CCSAVED_CHANNELMOTO' => 'Alternativer Kanal (z.B. ohne 3D Secure) für '
                        . 'wiederkehrende Zahlungen (nur bei aktiviertem Multichannel benötigt)',
                       'BACKEND_CH_CCSAVED_MULTICHANNEL' => 'Wenn aktiviert, werden wiederkehrende Zahlungen '
                        . 'über den alternativen Kanal abgewickelt',
                       'BACKEND_CH_KLARNASLICEIT_PCLASS' => 'Tragen Sie hier den Ihnen von Klarna zugewiesene '
                        . 'Ratenplan (PCLASS) ein.',
                       'BACKEND_CH_GENERAL_VERSION_TRACKER' => ' When enabled, you accept to share your IP, '
                        . 'email address, etc with Cardprocess',
                        'BACKEND_CH_GENERAL_MERCHANT_LOCATION' => 'Firmensitz lt. Handelsregister (Firmenname, '
                        .'Adresse inklusive Land)',
                  ),
            'en_GB' => array(
                       'BACKEND_CH_GENERAL_MERCHANTNO' => 'Your Customer ID from VR pay',
                       'BACKEND_CH_CCSAVED_AMOUNT' => 'Amount that is debited and refunded when a shopper '
                        . 'registers a payment method without purchase',
                       'BACKEND_CH_DDSAVED_AMOUNT' => 'Amount that is debited and refunded when a shopper '
                        . 'registers a payment method without purchase',
                       'BACKEND_CH_PAYPALSAVED_AMOUNT' => 'Amount that is debited and refunded when a shopper '
                        . 'registers a payment method without purchase',
                       'BACKEND_CH_CCSAVED_CHANNELMOTO' => 'Alternative channel for recurring payments if '
                        . 'Multichannel is activated (to bypass 3D Secure)',
                       'BACKEND_CH_CCSAVED_MULTICHANNEL' => 'If activated, repeated recurring payments are '
                        . 'handled by the alternative channel',
                       'BACKEND_CH_KLARNASLICEIT_PCLASS' => 'Please insert your Klarna installment plan (PCLASS) here.',
                       'BACKEND_CH_GENERAL_VERSION_TRACKER' => ' When enabled, you accept to share your IP, email '
                        . 'address, etc with Cardprocess',
                        'BACKEND_CH_GENERAL_MERCHANT_LOCATION' => 'Principal place of business (Company Name, Adress '
                        .'including the Country)',
            )
        );

        $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
        foreach ($translations as $locale => $snippets) {
            $localeModel = $shopRepository->findOneBy(array(
                'locale' => $locale
            ));
            foreach ($snippets as $element => $snippet) {
                if ($localeModel === null) {
                    continue;
                }
                $elementModel = $form->getElement($element);
                if ($elementModel === null) {
                    continue;
                }
                $translationModel = new \Shopware\Models\Config\ElementTranslation();
                $translationModel->setLabel($snippet);
                $translationModel->setLocale($localeModel);
                if (isset($translationsTooltips[$locale][$element])) {
                    $translationModel->setDescription($translationsTooltips[$locale][$element]);
                }
                $elementModel->addTranslation($translationModel);
            }
        }
    }

    /**
     * Insert plugin Translation to database
     */
    private function insertTranslations()
    {
        $sql = "INSERT INTO s_core_snippets (namespace, shopID, localeID, name, value) VALUES "
            ."('frontend/". $this->getPaymentController() ."/form/cp', 1,1, 'FRONTEND_BT_CANCEL', 'Abbrechen'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,2, 'FRONTEND_BT_CANCEL', 'Cancel'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,1, 'FRONTEND_TT_TESTMODE', 'TESTMODUS "
            .": ES FINDET KEINE REALE ZAHLUNG STATT'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,2, 'FRONTEND_TT_TESTMODE', 'THIS IS A "
            ."TEST. NO REAL MONEY WILL BE TRANSFERED') ,
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,1, 'FRONTEND_MC_PAYANDSAFE', 'Zahlung "
            ."abschlie&szlig;en und Daten hinterlegen'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,2, 'FRONTEND_MC_PAYANDSAFE', 'Pay and "
            ."Save Payment Information'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,1, 'FRONTEND_RECURRING_WIDGET_HEADER1', "
            ."'Hinterlegte Zahlungsdaten verwenden'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,2, 'FRONTEND_RECURRING_WIDGET_HEADER1', "
            ."'Use stored payment data'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,1, 'FRONTEND_RECURRING_WIDGET_HEADER2', "
            ."'Alternative Zahlungsdaten verwenden'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,2, 'FRONTEND_RECURRING_WIDGET_HEADER2', "
            ."'Use alternative payment data'),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,1, 'FRONTEND_MERCHANT_LOCATION_DESC', "
            ."'Zahlungsempfänger: '),
            ('frontend/". $this->getPaymentController() ."/form/cp', 1,2, 'FRONTEND_MERCHANT_LOCATION_DESC', "
            ."'Payee: '),


            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,1, 'FRONTEND_BT_CANCEL', 'Abbrechen'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,2, 'FRONTEND_BT_CANCEL', 'Cancel'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,1, 'FRONTEND_TT_TESTMODE', 'TESTMODUS "
            .": ES FINDET KEINE REALE ZAHLUNG STATT'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,2, 'FRONTEND_TT_TESTMODE', 'THIS IS A "
            ."TEST. NO REAL MONEY WILL BE TRANSFERED') ,
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,1, 'FRONTEND_MC_PAYANDSAFE', 'Zahlung "
            ."abschlie&szlig;en und Daten hinterlegen'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,2, 'FRONTEND_MC_PAYANDSAFE', 'Pay and "
            ."Save Payment Information') ,
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,1, 'FRONTEND_BT_PAYNOW', 'Jetzt bezahlen'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,2, 'FRONTEND_BT_PAYNOW', 'Pay now'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,1, 'FRONTEND_RECURRING_WIDGET_HEADER1', "
            ."'Hinterlegte Zahlungsdaten verwenden'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,2, 'FRONTEND_RECURRING_WIDGET_HEADER1', "
            ."'Use stored payment data'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,1, 'FRONTEND_RECURRING_WIDGET_HEADER2', "
            ."'Alternative Zahlungsdaten verwenden'),
            ('frontend/". $this->getPaymentController() ."/form/paypalsaved', 1,2, 'FRONTEND_RECURRING_WIDGET_HEADER2', "
            ."'Use alternative payment data'),

            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'SHOPWARE_BILLING_ADDRESS', 'Rechnungsadresse'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'SHOPWARE_BILLING_ADDRESS', 'Billing address'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'SHOPWARE_SHIPPING_ADDRESS', 'Lieferadresse'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'SHOPWARE_SHIPPING_ADDRESS', 'Shipping address'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'FRONTEND_EASYCREDIT_LINK', 'Vorvertragliche "
            ."Informationen zum Ratenkauf hier abrufen'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'FRONTEND_EASYCREDIT_LINK', 'Read pre-contractual "
            ."information on Installments'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'SHOPWARE_PAYMENT_BUTTON', 'Zahlungspflichtig bestellen'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'SHOPWARE_PAYMENT_BUTTON', 'Complete payment'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'SHOPWARE_PAYMENT_DISPATCH', 'Zahlung und Versand'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'SHOPWARE_PAYMENT_DISPATCH', 'Payment and dispatch'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'FRONTEND_EASYCREDIT_INTEREST', 'Zinsbetrag'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'FRONTEND_EASYCREDIT_INTEREST', 'Sum of Interest'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'FRONTEND_EASYCREDIT_INTEREST_OF_INSTALLMENT', 'Zinsen für Ratenzahlung'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'FRONTEND_EASYCREDIT_INTEREST_OF_INSTALLMENT', 'Interest on installment'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,1, 'FRONTEND_EASYCREDIT_TOTAL', 'Endbetrag'),
            ('frontend/". $this->getPaymentController() ."/confirmation', 1,2, 'FRONTEND_EASYCREDIT_TOTAL', 'Order Total'),

            ('frontend/account/payment', 1,1, 'MODULE_PAYMENT_VRPAYECOMMERCE_EASYCREDIT_TEXT_ERROR_CREDENTIALS', "
            ."'VR pay eCommerce General Setting Müssen ausgefüllt werden'),
            ('frontend/account/payment', 1,2, 'MODULE_PAYMENT_VRPAYECOMMERCE_EASYCREDIT_TEXT_ERROR_CREDENTIALS', "
            ."'VR pay eCommerce General Setting must be filled'),
            ('frontend/account/payment', 1,1, 'ERROR_MESSAGE_EASYCREDIT_AMOUNT_NOTALLOWED', 'Der Finanzierungsbetrag "
            ."liegt außerhalb der zulässigen Beträge (200 - 5.000 EUR).'),
            ('frontend/account/payment', 1,2, 'ERROR_MESSAGE_EASYCREDIT_AMOUNT_NOTALLOWED', 'The financing amount is "
            ."outside the permitted amounts (200 - 5,000 EUR).'),
            ('frontend/account/payment', 1,1, 'ERROR_EASYCREDIT_BILLING_NOTEQUAL_SHIPPING', 'Um mit easyCredit "
            ."bezahlen zu können, muss die Lieferadresse mit der Rechnungsadresse übereinstimmen.'),
            ('frontend/account/payment', 1,2, 'ERROR_EASYCREDIT_BILLING_NOTEQUAL_SHIPPING', 'In order to be able "
            ."to pay with easyCredit, the delivery address must match the invoice address.'),
            ('frontend/account/payment', 1,1, 'ERROR_MESSAGE_EASYCREDIT_PARAMETER_GENDER', 'Bitte geben Sie Ihr "
            ."Geschlecht an um die Zahlung mit easyCredit durchzuführen.'),
            ('frontend/account/payment', 1,2, 'ERROR_MESSAGE_EASYCREDIT_PARAMETER_GENDER', 'Please enter your gender to"
            ." make payment with easyCredit.'),"
            ."('frontend/account/payment', 1,1, 'FRONTEND_EASYCREDIT_TERMS', 'Ja, ich möchte per Ratenkauf"
            ." zahlen und willge ein, dass %x Ratenkauf der TeamBank AG (Partner der genossenschaftlichen FiinanzGrupper "
            ."Volksbanken Raiffeisenbanken), %y zur Identitäts- und Bonitätsprüfung sowie Betrugsprävention Anrede und "
            ."Name, Geburtsdatum und -ort, Kontaktdaten (Adresse, Telefon, E-mail) sowie Angaben zur aktuallen und zu "
            ."früheren Bestellungen übermittlet und das Prüfungsergebnis zu diesem Zweck erhält.'),"
            ."('frontend/account/payment', 1,2, 'FRONTEND_EASYCREDIT_TERMS', 'Yes, I would like to pay by installment "
            ."and I agree that %x installment purchase of TeamBank AG (Partner of the Cooperative FiinanzGrupper Volksbanken "
            ."Raiffeisenbanken), %y gets for identity and credit checks and fraud prevention title and "
            ."surname, date and place of birth, contact details ( Address, telephone, e-mail) as well as details"
            ." of current and past orders and receives the test result for this purpose.'),
            ('frontend/". $this->getPaymentController() ."/form/response_redirect', 1,1, 'ERROR_MESSAGE_EASYCREDIT_BEFORE_PAYMENT', "
            ."'Please make sure your payment method is correct!'),
            ('frontend/". $this->getPaymentController() ."/form/response_redirect', 1,2, 'ERROR_MESSAGE_EASYCREDIT_BEFORE_PAYMENT', "
            ."'Please make sure your payment method is correct!'),

            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'SHOPWARE_FAILPAYMENTTITLE', 'Die Zahlung kann "
            ."nicht abgeschlossen werden'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'SHOPWARE_FAILPAYMENTTITLE', 'Payment cannot "
            ."be completed'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'SHOPWARE_CLICKTITLE', 'Klicken'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'SHOPWARE_CLICKTITLE', 'Click'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'SHOPWARE_HERETITLE', 'hierher'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'SHOPWARE_HERETITLE', 'here'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'SHOPWARE_TOCONTINUETITLE', 'Weiter'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'SHOPWARE_TOCONTINUETITLE', 'Continue'),

            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_CC', 'Kreditkarte'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_CC', 'Credit Cards'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_CCSAVED', 'Kreditkarte'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_CCSAVED', 'Credit Card'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_DC', 'Debit Card'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_DC', 'Debit Card'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_DD', 'Lastschrift'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_DD', 'Direct Debit'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_DDSAVED', 'Lastschrift'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_DDSAVED', 'Direct Debit'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_EPS', 'eps'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_EPS', 'eps'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_GIROPAY', 'Giropay'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_GIROPAY', 'Giropay'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_IDEAL', 'iDeal'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_IDEAL', 'iDeal'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_KLARNAPAYLATER', 'Rechnung.'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_KLARNAPAYLATER', 'Pay later.'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_KLARNASLICEIT', 'Ratenkauf.'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_KLARNASLICEIT', 'Slice it.'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_MASTERPASS', 'Masterpass'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_MASTERPASS', 'Masterpass'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_PAYPAL', 'PayPal'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_PAYPAL', 'PayPal'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_PAYPALSAVED', 'PayPal'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_PAYPALSAVED', 'PayPal'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_PAYDIREKT', 'paydirekt'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_PAYDIREKT', 'paydirekt'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_KLARNAOBT', 'Sofort.'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_KLARNAOBT', 'Online Bank Transfer.'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_SWISSPOSTFINANCE', 'Swiss Postfinance'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_SWISSPOSTFINANCE', 'Swiss Postfinance'),
            ('frontend/checkout/shipping_payment', 1,1, 'FRONTEND_PM_EASYCREDIT', 'ratenkauf by easyCredit'),
            ('frontend/checkout/shipping_payment', 1,2, 'FRONTEND_PM_EASYCREDIT', 'ratenkauf by easyCredit'),

            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_ACCOUNT', 'The account holder entered "
            ."does not match your name. Please use an account that is registered on your name.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_INVALIDDATA', 'Unfortunately, the "
            ."card/account data you entered was not correct. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_BLACKLIST', 'Unfortunately, the credit "
            ."card you entered can not be accepted. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_DECLINED_CARD', 'Unfortunately, the credit "
            ."card you entered can not be accepted. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_EXPIRED', 'Unfortunately, the credit card "
            ."you entered is expired. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_INVALIDCVV', 'Unfortunately, the CVV/CVC you "
            ."entered is not correct. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_EXPIRY', 'Unfortunately, the expiration date "
            ."you entered is not correct. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_LIMIT_EXCEED', 'Unfortunately, the limit of "
            ."your credit card is exceeded. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_3DAUTH', 'Unfortunately, the password you "
            ."entered was not correct. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_3DERROR', 'Unfortunately, there has been an "
            ."error while processing your request. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_NOBRAND', 'Unfortunately, there has been an "
            ."error while processing your request. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_LIMIT_AMOUNT', 'Unfortunately, your "
            ."credit limit is exceeded. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_LIMIT_TRANSACTIONS', 'Unfortunately, "
            ."your limit of transaction is exceeded. Please try again later. '),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_DECLINED_AUTH', 'Unfortunately, your "
            ."transaction has failed. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_DECLINED_RISK', 'Unfortunately, "
            ."your transaction has failed. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_ADDRESS', 'We are sorry. We could no "
            ."accept your card as its origin does not match your address.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_CANCEL', 'You cancelled the payment "
            ."prior to its execution. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_RECURRING', 'Recurring transactions have "
            ."been deactivated for this credit card. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CC_REPEATED', 'Unfortunately, your transaction "
            ."has been declined due to invalid data. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_ADDRESS', 'Unfortunately, your transaction "
            ."has failed. Please check the personal data you entered.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_BLACKLIST', 'The chosen payment method "
            ."is not available at the moment. Please choose a different card or payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_GENERAL', 'Unfortunately, your transaction "
            ."has failed. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_TIMEOUT', 'Unfortunately, your transaction "
            ."has failed. Please try again. '),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GIRO_NOSUPPORT', 'Giropay is not supported for "
            ."this transaction. Please choose a different payment method.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_ADDRESS_PHONE', 'Unfortunately, your transaction has "
            ."failed. Please enter a valid telephone number.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_REDIRECT', 'Error before redirect.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_CAPTURE_BACKEND', 'Transaction can not be captured.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_MERCHANT_SSL_CERTIFICATE',
            'SSL certificate problem, please contact the merchant.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_UNKNOWN', 'Unfortunately, your transaction has "
            ."failed. Please try again.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_GENERAL_NORESPONSE', 'Unfortunately, the confirmation "
            ."of your payment failed. Please contact your merchant for clarification.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_MESSAGE_SESSION_TIMEOUT', 'your payment is refunded "
            ."due to the session timeout, please login to finish the payment.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_MESSAGE_SESSION_TIMEOUT_WHITOUT_REFUND', 'Your payment "
            ."is failed due to the session timeout'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_EASYCREDIT_IBAN', 'The IBAN "
            ."does not correspond to the IBAN country format.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_EASYCREDIT_DOB', 'The date entered must be in the past'),
            ('frontend/". $this->getPaymentController() ."/result', 1,2, 'ERROR_EASYCREDIT_ADDRESS', 'The address could not be found. "
            ."Please select a different delivery and billing address or choose another payment method.'),

            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_ACCOUNT', 'Sie sind nicht der Inhaber des "
            ."eingegebenen Kontos. Bitte w&auml;hlen Sie ein Konto das auf Ihren Namen l&auml;uft.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_INVALIDDATA', 'Ihre Karten-/Kontodaten sind leider "
            ."nicht korrekt. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_BLACKLIST', 'Leider kann die eingegebene Kreditkarte "
            ."nicht akzeptiert werden. Bitte w&auml;hlen Sie eine andere Karte oder Bezahlungsmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_DECLINED_CARD', 'Leider kann die eingegebene "
            ."Kreditkarte nicht akzeptiert werden. Bitte w&auml;hlen Sie eine andere Karte oder Bezahlungsmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_EXPIRED', 'Leider ist die eingegebene Kreditkarte "
            ."abgelaufen. Bitte w&auml;hlen Sie eine andere Karte oder Bezahlungsmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_INVALIDCVV', 'Leider ist die eingegebene "
            ."Kartenpr&uuml;fnummer nicht korrekt. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_EXPIRY', 'Leider ist das eingegebene Ablaufdatum "
            ."nicht korrekt. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_LIMIT_EXCEED', 'Leider &uuml;bersteigt der zu "
            ."zahlende Betrag das Limit Ihrer Kreditkarte. Bitte w&auml;hlen Sie eine andere Karte oder "
            ."Bezahlsmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_3DAUTH', 'Ihr Passwort wurde leider nicht korrekt "
            ."eingegeben. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_3DERROR', 'Leider gab es einen Fehler bei der "
            ."Durchf&uuml;hrung Ihrer Zahlung. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_NOBRAND', 'Leider gab es einen Fehler bei der "
            ."Durchf&uuml;hrung Ihrer Zahlung. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_LIMIT_AMOUNT', 'Leider &uuml;bersteigt der "
            ."zu zahlende Betrag Ihr Limit. Bitte w&auml;hlen Sie eine andere Bezahlsmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_LIMIT_TRANSACTIONS', 'Leider &uuml;bersteigt "
            ."der zu zahlende Betrag Ihr Transaktionslimit. Bitte w&auml;hlen Sie eine andere Bezahlsmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_DECLINED_AUTH', 'Leider ist Ihre Zahlung "
            ."fehlgeschlagen. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_DECLINED_RISK', 'Leider ist Ihre Zahlung "
            ."fehlgeschlagen. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_ADDRESS', 'Leider konnten wir Ihre Kartendaten "
            ."nicht akzeptieren. Ihre Adresse stimmt nicht mit der Herkunft Ihrer Karte &uuml;berein.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_CANCEL', 'Der Vorgang wurde auf Ihren Wunsch "
            ."abgebrochen. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_RECURRING', 'F&uuml;r die gewählte Karte wurden "
            ."wiederkehrende Zahlungen deaktiviert. Bitte w&auml;len Sie eine andere Bezahlmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CC_REPEATED', 'Leider ist Ihre Zahlung fehlgeschlagen, "
            ."da Sie mehrfach fehlerhafte Angaben gemacht haben. Bitte w&auml;len Sie eine andere Bezahlmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_ADDRESS', 'Leider ist Ihre Zahlung "
            ."fehlgeschlagen. Bitte kontrollieren Sie Ihre pers&ouml;nlichen Angaben.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_BLACKLIST', 'Die gew&auml;hlte Bezahlmethode "
            ."steht leider nicht zur Verfügung. Bitte w&auml;len Sie eine andere Bezahlmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_GENERAL', 'Leider konnten wir Ihre Transaktion "
            ."nicht durchf&uuml;hren. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_TIMEOUT', 'Leider konnten wir Ihre Transaktion "
            ."nicht durchf&uuml;hren. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GIRO_NOSUPPORT', 'Giropay wird leider f&uuml;r diese "
            ."Transaktion nicht unterstützt. Bitte w&auml;len Sie eine andere Bezahlmethode.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_ADDRESS_PHONE', 'Leider ist Ihre Zahlung "
            ."fehlgeschlagen. Bitte geben Sie eine korrekte Telefonnummer an.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_REDIRECT', 'Fehler vor Weiterleitung.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_CAPTURE_BACKEND', 'Die Transaktion kann nicht gecaptured werden.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_MERCHANT_SSL_CERTIFICATE',
            'SSL-Zertifikat Problem, wenden Sie sich bitte an den Händler.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_UNKNOWN', 'Leider konnten wir Ihre Transaktion "
            ."nicht durchf&uuml;hren. Bitte versuchen Sie es erneut.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_GENERAL_NORESPONSE', 'Leider konnte ihre Zahlung "
            ."nicht best&auml;tigt werden. Bitte setzen Sie sich mit dem Händler in Verbindung.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_MESSAGE_SESSION_TIMEOUT', 'Ihre Zahlung wird "
            ."zurückerstattet wegen der Session Timeout, bitte anmelden, um die Zahlung zu beenden.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_MESSAGE_SESSION_TIMEOUT_WHITOUT_REFUND', 'Ihre Zahlung "
            ."ist aufgrund des Session-Timeouts fehlgeschlagen'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_EASYCREDIT_IBAN', 'Die IBAN "
            ."entspricht nicht dem IBAN-Länder-Format.'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_EASYCREDIT_DOB', 'Das eingegebene Datum muss in "
            ."der Vergangenheit liegen'),
            ('frontend/". $this->getPaymentController() ."/result', 1,1, 'ERROR_EASYCREDIT_ADDRESS', 'Die Adresse konnte nicht gefunden "
            ."werden. Bitte wählen Sie eine andere Liefer- und Rechnungsadresse oder wählen Sie eine andere Zahlungsart.'),

            ('sidebar', 1,2, 'FRONTEND_MC_INFO', 'My Payment Information'),
            ('sidebar', 1,1, 'FRONTEND_MC_INFO', 'Meine Zahlungsarten'),

            ('frontend/account/sidebar', 1,2, 'FRONTEND_MC_INFO', 'My Payment Information'),
            ('frontend/account/sidebar', 1,1, 'FRONTEND_MC_INFO', 'Meine Zahlungsarten'),

            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_INFO', 'My Payment Information'),
            ('frontend/payment_information/information', 1,2, 'SUCCESS_MC_ADD', 'Congratulations, your "
            ."payment information were successfully saved.'),
            ('frontend/payment_information/information', 1,2, 'SUCCESS_MC_UPDATE', 'Congratulations, your "
            ."payment information were successfully updated.'),
            ('frontend/payment_information/information', 1,2, 'SUCCESS_MC_DELETE', 'Congratulations, your "
            ."payment information were successfully deleted.'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_CC', 'Credit Cards'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_ENDING', 'ending in:'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_VALIDITY', 'expires on:'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_BT_DEFAULT', 'Default'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_BT_SETDEFAULT', 'Set as Default'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_BT_CHANGE', 'Change'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_BT_DELETE', 'Delete'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_BT_ADD', 'Add'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_DD', 'Direct Debit'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_ACCOUNT', 'Account: ****'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_HOLDER', 'Holder:'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_PAYPAL', 'PayPal'),
            ('frontend/payment_information/information', 1,2, 'FRONTEND_MC_EMAIL', 'Email:'),
            ('frontend/payment_information/information', 1,2, 'ERROR_MC_ADD', 'We are sorry. Your attempt "
            ."to save your payment information was not successful, please try again.'),
            ('frontend/payment_information/information', 1,2, 'ERROR_MC_UPDATE', 'We are sorry. Your "
            ."attempt to update your payment information was not successful, please try again.'),
            ('frontend/payment_information/information', 1,2, 'ERROR_MC_DELETE', 'We are sorry. Your "
            ."attempt to delete your payment information was not successful, please try again.'),

            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_INFO', 'Meine Zahlungsarten'),
            ('frontend/payment_information/information', 1,1, 'SUCCESS_MC_ADD', 'Ihre Zahlungsart wurde "
            ."erfolgreich angelegt'),
            ('frontend/payment_information/information', 1,1, 'SUCCESS_MC_UPDATE', 'Ihre Zahlungsdaten "
            ."wurden erfolgreich ge&auml;ndert'),
            ('frontend/payment_information/information', 1,1, 'SUCCESS_MC_DELETE', 'Ihre Zahlungsart "
            ."wurde erfolgreich gel&ouml;scht'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_CC', 'Kreditkarten'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_ENDING', 'endet auf:'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_VALIDITY', 'g&uuml;ltig bis:'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_BT_DEFAULT', 'Standard'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_BT_SETDEFAULT', 'Als Standard "
            ."setzen'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_BT_CHANGE', '&Auml;ndern'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_BT_DELETE', 'Entfernen'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_BT_ADD', 'Hinzuf&uuml;gen'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_DD', 'Lastschrift'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_ACCOUNT', 'Konto: ****'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_HOLDER', 'Inhaber:'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_PAYPAL', 'PayPal'),
            ('frontend/payment_information/information', 1,1, 'FRONTEND_MC_EMAIL', 'Email:'),
            ('frontend/payment_information/information', 1,1, 'ERROR_MC_ADD', 'Leider ist das Anlegen Ihrer "
            ."Zahlungsart fehlgeschlagen. Bitte versuchen Sie es erneut'),
            ('frontend/payment_information/information', 1,1, 'ERROR_MC_UPDATE', 'Leider ist die &Auml;nderung "
            ."Ihrer Zahlungsdaten fehlgeschlagen. Bitte versuchen Sie es erneut'),
            ('frontend/payment_information/information', 1,1, 'ERROR_MC_DELETE', 'Leider ist das l&ouml;schen "
            ."Ihrer Zahlungsart fehlgeschlagen. Bitte versuchen Sie es erneut'),

            ('frontend/payment_information/form/register_cp', 1,2, 'FRONTEND_MC_SAVE', 'Save Payment Information'),
            ('frontend/payment_information/form/register_cp', 1,2, 'ERROR_MC_ADD', 'We are sorry. Your attempt "
            ."to save your payment information was not successful, please try again.'),
            ('frontend/payment_information/form/register_cp', 1,2, 'FRONTEND_BT_CANCEL', 'Cancel'),
            ('frontend/payment_information/form/register_cp', 1,2, 'FRONTEND_BT_REGISTER', 'Register'),
            ('frontend/payment_information/form/register_cp', 1,2, 'FRONTEND_TT_TESTMODE', 'THIS IS A TEST. "
            ."NO REAL MONEY WILL BE TRANSFERED'),
            ('frontend/payment_information/form/register_cp', 1,2, 'FRONTEND_TT_REGISTRATION', 'A small amount "
            ."(<1 &euro;) will be charged and instantly refunded to verify your account/card details. '),

            ('frontend/payment_information/form/register_cp', 1,1, 'FRONTEND_MC_SAVE', 'Zahlungsarten anlegen'),
            ('frontend/payment_information/form/register_cp', 1,1, 'ERROR_MC_ADD', 'Leider ist das Anlegen "
            ."Ihrer Zahlungsart fehlgeschlagen. Bitte versuchen Sie es erneut'),
            ('frontend/payment_information/form/register_cp', 1,1, 'FRONTEND_BT_CANCEL', 'Abbrechen'),
            ('frontend/payment_information/form/register_cp', 1,1, 'FRONTEND_BT_REGISTER', 'Registrieren'),
            ('frontend/payment_information/form/register_cp', 1,1, 'FRONTEND_TT_TESTMODE', 'TESTMODUS : ES "
            ."FINDET KEINE REALE ZAHLUNG STATT'),
            ('frontend/payment_information/form/register_cp', 1,1, 'FRONTEND_TT_REGISTRATION', 'Hinweis: Bei "
            ."der Registrierung wird zur Verifizierung Ihrer Konten/Kartendaten ein kleiner Betrag <1 &euro; "
            ."belastet und sofort wieder gut geschrieben. '),

            ('frontend/payment_information/form/change_cp', 1,2, 'FRONTEND_MC_CHANGE', 'Change Payment "
            ."Information'),
            ('frontend/payment_information/form/change_cp', 1,2, 'ERROR_MC_UPDATE', 'We are sorry. Your "
            ."attempt to update your payment information was not successful, please try again.'),
            ('frontend/payment_information/form/change_cp', 1,2, 'FRONTEND_BT_CANCEL', 'Cancel'),
            ('frontend/payment_information/form/change_cp', 1,2, 'FRONTEND_MC_BT_CHANGE', 'Change'),
            ('frontend/payment_information/form/change_cp', 1,2, 'FRONTEND_TT_TESTMODE', 'THIS IS A TEST. "
            ."NO REAL MONEY WILL BE TRANSFERED'),
            ('frontend/payment_information/form/change_cp', 1,2, 'FRONTEND_TT_REGISTRATION', 'A small "
            ."amount (<1 &euro;) will be charged and instantly refunded to verify your account/card details. '),

            ('frontend/payment_information/form/change_cp', 1,1, 'FRONTEND_MC_CHANGE', 'Zahlungsarten "
            ."&Auml;ndern'),
            ('frontend/payment_information/form/change_cp', 1,1, 'ERROR_MC_UPDATE', 'Leider ist die &Auml;nderung "
            ."Ihrer Zahlungsdaten fehlgeschlagen. Bitte versuchen Sie es erneut'),
            ('frontend/payment_information/form/change_cp', 1,1, 'FRONTEND_BT_CANCEL', 'Abbrechen'),
            ('frontend/payment_information/form/change_cp', 1,1, 'FRONTEND_MC_BT_CHANGE', '&Auml;ndern'),
            ('frontend/payment_information/form/change_cp', 1,1, 'FRONTEND_TT_TESTMODE', 'TESTMODUS : ES FINDET "
            ."KEINE REALE ZAHLUNG STATT'),
            ('frontend/payment_information/form/change_cp', 1,1, 'FRONTEND_TT_REGISTRATION', 'Hinweis: Bei der "
            ."Registrierung wird zur Verifizierung Ihrer Konten/Kartendaten ein kleiner Betrag <1 &euro; belastet "
            ."und sofort wieder gut geschrieben. '),

            ('frontend/payment_information/delete', 1,2, 'FRONTEND_MC_DELETE', 'Delete Payment Information'),
            ('frontend/payment_information/delete', 1,2, 'ERROR_MC_DELETE', 'We are sorry. Your attempt to delete "
            ."your payment information was not successful, please try again.'),
            ('frontend/payment_information/delete', 1,2, 'FRONTEND_MC_DELETESURE', 'Are you sure to delete this "
            ."payment information?'),
            ('frontend/payment_information/delete', 1,2, 'FRONTEND_BT_CANCEL', 'Cancel'),
            ('frontend/payment_information/delete', 1,2, 'FRONTEND_BT_CONFIRM', 'Confirm'),

            ('frontend/payment_information/delete', 1,1, 'FRONTEND_MC_DELETE', 'Zahlungsarten l&ouml;schen'),
            ('frontend/payment_information/delete', 1,1, 'ERROR_MC_DELETE', 'Leider ist das l&ouml;schen Ihrer "
            ."Zahlungsart fehlgeschlagen. Bitte versuchen Sie es erneut'),
            ('frontend/payment_information/delete', 1,1, 'FRONTEND_MC_DELETESURE', 'Sind Sie sicher, dass Sie "
            ."diese Zahlungsart l&ouml;schen m&ouml;chten?'),
            ('frontend/payment_information/delete', 1,1, 'FRONTEND_BT_CANCEL', 'Abbrechen'),
            ('frontend/payment_information/delete', 1,1, 'FRONTEND_BT_CONFIRM', 'Best&auml;tigen'),

            ('backend/order/main', 1,2, 'SUCCESS_IN_REVIEW', 'Transaction succeeded but has been suspended for "
            ."manual review. Please update transaction status in 24 hours.'),
            ('backend/order/main', 1,1, 'SUCCESS_IN_REVIEW', 'Die Transaktion war erfolgreich, sollte aber "
            ."manuell überprüft werden. Bitte aktualisieren Sie den Status der Transaktion manuell nach 24 Stunden'),

            ('backend/error/index', 1,1, 'ERROR_CAPTURE_BACKEND', 'Transaction can not be captured.'),
            ('backend/error/index', 1,2, 'ERROR_CAPTURE_BACKEND', 'Die Transaktion kann nicht gecaptured werden.'),
            ('backend/error/index', 1,2, 'ERROR_REFUND_BACKEND', 'Transaction can not be refunded or reversed.'),
            ('backend/error/index', 1,1, 'ERROR_REFUND_BACKEND', 'Die Transaktion kann nichrt refunded oder "
            ."reversed werden.'),
            ('backend/error/index', 1,1, 'ERROR_REORDER_BACKEND', 'Card holder has advised his bank to stop "
            ."this recurring payment.'),
            ('backend/error/index', 1,2, 'ERROR_REORDER_BACKEND', 'Der Kunde hat seine Bank angewiesen, keine "
            ."wiederkehrenden Zahlungen mehr zuzulassen. .'),
            ('backend/error/index', 1,1, 'ERROR_RECEIPT_BACKEND', 'Receipt can not be performed.'),
            ('backend/error/index', 1,2, 'ERROR_RECEIPT_BACKEND', 'Das Receipt kann nicht prozessiert werden.'),"
            ."('confirm', 1,2, 'FRONTEND_EASYCREDIT_CONFIRM_BUTTON', 'Continue to Ratenkauf by easyCredit'),
            ('confirm', 1,1, 'FRONTEND_EASYCREDIT_CONFIRM_BUTTON', 'Weiter zu Ratenkauf by easyCredit')
            "
        ;

        Shopware()->Db()->query($sql);
    }

    /**
     * Delete all Plugin Translation config
     */
    private function deleteTranslations()
    {
        $sql =  "DELETE FROM s_core_snippets WHERE namespace in ('frontend/". $this->getPaymentController() ."/form/cp',"
            . "'frontend/". $this->getPaymentController() ."/form/paypalsaved', 'frontend/". $this->getPaymentController() ."/result', "
            . "'frontend/". $this->getPaymentController() ."/confirmation', "
            . "'frontend/payment_information/information', 'frontend/payment_information/form/register_cp',"
            . "'frontend/payment_information/form/change_cp', 'frontend/payment_information/delete',
            'frontend/". $this->getPaymentController() ."/form/response_redirect')";
        Shopware()->Db()->query($sql);
        $sql =  "DELETE FROM s_core_snippets WHERE namespace in ('frontend/checkout/shipping_payment',"
            . "'sidebar', 'frontend/account/sidebar', 'backend/error/index', "
            . "'frontend/account/payment', 'backend/order/main') "
            . "AND name IN ('FRONTEND_PM_CC','FRONTEND_PM_CCSAVED','FRONTEND_PM_DC','FRONTEND_PM_DD',"
            . "'FRONTEND_PM_DDSAVED','FRONTEND_PM_EPS','FRONTEND_PM_GIROPAY','FRONTEND_PM_IDEAL',"
            . "'FRONTEND_PM_KLARNAPAYLATER','FRONTEND_PM_EASYCREDIT',"
            . "'FRONTEND_PM_KLARNASLICEIT','FRONTEND_PM_MASTERPASS',"
            . "'FRONTEND_PM_PAYPAL','FRONTEND_PM_PAYPALSAVED','FRONTEND_PM_PAYDIREKT','FRONTEND_PM_KLARNAOBT',"
            . "'FRONTEND_PM_SWISSPOSTFINANCE','FRONTEND_MC_INFO',"
            . "'MODULE_PAYMENT_VRPAYECOMMERCE_EASYCREDIT_TEXT_ERROR_CREDENTIALS', 'ERROR_EASYCREDIT_FUTURE_DOB',"
            . "'ERROR_EASYCREDIT_PARAMETER_DOB','ERROR_MESSAGE_EASYCREDIT_PARAMETER_GENDER','ERROR_CAPTURE_BACKEND',"
            . "'ERROR_MESSAGE_EASYCREDIT_AMOUNT_NOTALLOWED','ERROR_EASYCREDIT_BILLING_NOTEQUAL_SHIPPING',"
            . "'ERROR_REFUND_BACKEND','ERROR_REORDER_BACKEND', 'ERROR_RECEIPT_BACKEND', 'SUCCESS_IN_REVIEW',"
            ."'FRONTEND_EASYCREDIT_TERMS')";
        Shopware()->Db()->query($sql);
        $sql =  "DELETE FROM s_core_snippets WHERE namespace in ('confirm') "
            . "AND name IN ('FRONTEND_EASYCREDIT_CONFIRM_BUTTON')";
        Shopware()->Db()->query($sql);
    }

    /**
     * update payment method easyCredit title to ratenkauf by easyCredit
     */
    private function updateEasyCreditTranslations()
    {
        $sql =  "UPDATE `s_core_snippets` SET `value` = 'ratenkauf by easyCredit' "
            . "WHERE `namespace` = 'frontend/checkout/shipping_payment' AND `name` = 'FRONTEND_PM_EASYCREDIT'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `label` = 'ratenkauf by easyCredit' "
            . "WHERE `name` = 'BACKEND_PM_EASYCREDIT'";
        Shopware()->Db()->query($sql);

        $elementId = $this->getElementId('BACKEND_PM_EASYCREDIT');
        $sql =  "UPDATE `s_core_config_element_translations` SET `label` = 'ratenkauf by easyCredit' "
            . "WHERE `element_id` = '" . $elementId . "'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_paymentmeans` SET `description` = 'ratenkauf by easyCredit' "
            . "WHERE `name` = 'vrpay_easycredit'";
        Shopware()->Db()->query($sql);
    }


    /**
     * update payment method Klarna and Sofort title to Slice it, Pay later and Online Bank Transfer
     * @return void
     */
    private function updateKlarnaMethod()
    {
        $this->updateDbKlarnapaylaterConfiguration();
        $this->updateDbKlarnasliceitConfiguration();
        $this->updateDbKlarnaobtConfiguration();
    }

    /**
     * add easyCredit confirmation button translation
     */
    private function addEasyCreditConfirmationButtonTranslation()
    {
        $sql = "INSERT INTO s_core_snippets (namespace, shopID, localeID, name, value) VALUES "
            ."('confirm', 1,2, 'FRONTEND_EASYCREDIT_CONFIRM_BUTTON', 'Continue to Ratenkauf by easyCredit'),
            ('confirm', 1,1, 'FRONTEND_EASYCREDIT_CONFIRM_BUTTON', 'Weiter zu Ratenkauf by easyCredit')";

        Shopware()->Db()->query($sql);
    }

    /**
     * update database value klarna invoice to Pay later.
     * @return void
     */
    public function updateDbKlarnapaylaterConfiguration()
    {
        $sql =  "UPDATE `s_core_paymentmeans` SET `name` = 'vrpay_klarnapaylater', "
          ." `description` = 'Pay later.' WHERE `name` = 'vrpay_klarnainv'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_snippets` SET `name` = 'FRONTEND_PM_KLARNAPAYLATER', `value` = 'Rechnung.' " .
        "WHERE localeID = (SELECT id FROM s_core_locales WHERE locale = 'de_DE') AND `name` = 'FRONTEND_PM_KLARNAINV'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_snippets` SET `name` = 'FRONTEND_PM_KLARNAPAYLATER', `value` = 'Pay later.' " .
        "WHERE localeID = (SELECT id FROM s_core_locales WHERE locale = 'en_GB') AND `name` = 'FRONTEND_PM_KLARNAINV'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_element_translations` SET `label` = 'Rechnung.' "
          . "WHERE `label` = 'Klarna Rechnung'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_element_translations` SET `label` = 'Pay later.' "
          . "WHERE `label` = 'Klarna Invoice'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_PM_KLARNAPAYLATER', `label` = 'Pay later.' "
          . "WHERE `name` = 'BACKEND_PM_KLARNAINV'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNAPAYLATER_ACTIVE', "
        . "`label` = 'Pay later. Enabled' WHERE `name` = 'BACKEND_CH_KLARNAINV_ACTIVE'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNAPAYLATER_SERVER', "
          ."`label` = 'Pay later. Server' WHERE `name` = 'BACKEND_CH_KLARNAINV_SERVER'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNAPAYLATER_CHANNEL', "
          . " `label` = 'Pay later. Entity-ID' WHERE `name` = 'BACKEND_CH_KLARNAINV_CHANNEL'";
        Shopware()->Db()->query($sql);
    }

    /**
     * update database value klarna installment to Slice it.
     * @return void
     */
    public function updateDbKlarnasliceitConfiguration()
    {
        $sql =  "UPDATE `s_core_paymentmeans` SET `name` = 'vrpay_klarnasliceit', `description` = 'Slice it.' "
          . "WHERE `name` = 'vrpay_klarnains'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_snippets` SET `name` = 'FRONTEND_PM_KLARNASLICEIT', `value` = 'Ratenkauf.' " .
        "WHERE localeID = (SELECT id FROM s_core_locales WHERE locale = 'de_DE') AND `name` = 'FRONTEND_PM_KLARNAINS'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_snippets` SET `name` = 'FRONTEND_PM_KLARNASLICEIT', `value` = 'Slice it.' " .
        "WHERE localeID = (SELECT id FROM s_core_locales WHERE locale = 'en_GB') AND `name` = 'FRONTEND_PM_KLARNAINS'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_element_translations` SET `label` = 'Ratenkauf.' "
          . "WHERE `label` = 'Klarna Ratenkauf'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_element_translations` SET `label` = 'Slice it.' "
          . "WHERE `label` = 'Klarna Installments'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_PM_KLARNASLICEIT', `label` = 'Slice it.' "
          . "WHERE `name` = 'BACKEND_PM_KLARNAINS'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNASLICEIT_ACTIVE', "
          . " `label` = 'Slice it. Enabled' WHERE `name` = 'BACKEND_CH_KLARNAINS_ACTIVE'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNASLICEIT_SERVER', "
          . " `label` = 'Slice it. Server' WHERE `name` = 'BACKEND_CH_KLARNAINS_SERVER'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNASLICEIT_CHANNEL', "
          . " `label` = 'Slice it. Entity-ID' WHERE `name` = 'BACKEND_CH_KLARNAINS_CHANNEL'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNASLICEIT_PCLASS', "
          . " `label` = 'Slice it. Plan (PCLASS)' WHERE `name` = 'BACKEND_CH_KLARNAINS_PCLASS'";
        Shopware()->Db()->query($sql);
    }

    /**
     * update database value sofort to Online Bank Transfer.
     * @return void
     */
    public function updateDbKlarnaobtConfiguration()
    {
        $sql =  "UPDATE `s_core_paymentmeans` SET `name` = 'vrpay_klarnaobt', `description` = 'Online Bank Transfer.' "
          . "WHERE `name` = 'vrpay_sofort'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_snippets` SET `name` = 'FRONTEND_PM_KLARNAOBT', `value` = 'Sofort.' "
          . "WHERE localeID = (SELECT id FROM s_core_locales WHERE locale = 'de_DE') AND `name` = 'FRONTEND_PM_SOFORT'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_snippets` SET `name` = 'FRONTEND_PM_KLARNAOBT', `value` = 'Online Bank Transfer.' "
          . "WHERE localeID = (SELECT id FROM s_core_locales WHERE locale = 'en_GB') AND `name` = 'FRONTEND_PM_SOFORT'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_element_translations` SET `label` = 'Sofot.' "
          . "WHERE `label` = 'SOFORT &Uuml;berweisung'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_element_translations` SET `label` = 'Online Bank Transfer.' "
          . "WHERE `label` = 'SOFORT Banking'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_PM_KLARNAOBT', "
          . " `label` = 'Online Bank Transfer.' WHERE `name` = 'BACKEND_PM_SOFORT'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNAOBT_ACTIVE', "
          . " `label` = 'Online Bank Transfer. Enabled' WHERE `name` = 'BACKEND_CH_SOFORT_ACTIVE'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNAOBT_SERVER', "
          . " `label` = 'Online Bank Transfer. Server' WHERE `name` = 'BACKEND_CH_SOFORT_SERVER'";
        Shopware()->Db()->query($sql);

        $sql =  "UPDATE `s_core_config_elements` SET `name` = 'BACKEND_CH_KLARNAOBT_CHANNEL', "
          . " `label` = 'Online Bank Transfer. Entity-ID' WHERE `name` = 'BACKEND_CH_SOFORT_CHANNEL'";
        Shopware()->Db()->query($sql);
    }

    /**
     * Add custom payment status
     */
    private function createPaymentStatus()
    {
         $this->deletePaymentStatus();
         $sql = "INSERT INTO s_core_states (id, name, description, position, mail,`group`) VALUES
                (1750,'in_review','In Review',1750,1,'payment'),
                (1751,'pre_authorization_of_payment','Pre-Authorization of Payment',1751,1,'payment'),
                (1752,'payment_accepted','Payment Accepted',1752,1,'payment'),
                (1753,'refund','Refund',1753,1,'payment')";
        Shopware()->Db()->query($sql);
        $sql = "INSERT INTO s_core_snippets (namespace, shopID, localeID, name, value) VALUES
            ('backend/static/payment_status', 1, 1, 'in_review', 'In Review'),
            ('backend/static/payment_status', 1, 2, 'in_review', 'In Review'),
            ('backend/static/payment_status', 1, 1, 'pre_authorization_of_payment', 'Pre-Authorization of Payment'),
            ('backend/static/payment_status', 1, 2, 'pre_authorization_of_payment', 'Pre-Authorization of Payment'),
            ('backend/static/payment_status', 1, 1, 'payment_accepted', 'Payment Accepted'),
            ('backend/static/payment_status', 1, 2, 'payment_accepted', 'Payment Accepted'),
            ('backend/static/payment_status', 1, 1, 'refund', 'Refund'),
            ('backend/static/payment_status', 1, 2, 'refund', 'Refund')";
        Shopware()->Db()->query($sql);
    }

    /**
     * Crete table for record all recurring payment method
     */
    private function createTableRecurring()
    {
         $sql = "CREATE TABLE IF NOT EXISTS `payment_vrpayecommerce_recurring` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `cust_id` INT(11) NOT NULL,
                `payment_group` VARCHAR(32),
                `brand` VARCHAR(100),
                `holder` VARCHAR(100) NULL default NULL,
                `email` VARCHAR(100) NULL default NULL,
                `last4digits` VARCHAR(4),
                `expiry_month` VARCHAR(2),
                `expiry_year` VARCHAR(4),
                `ref_id` VARCHAR(32),
                `payment_default` boolean NOT NULL default '0',
                PRIMARY KEY (id) );";

        Shopware()->Db()->query($sql);
    }

    /**
     * Alter recurring table
     */
    private function alterValidateRecurring()
    {
        $showSql       = "SHOW columns FROM `payment_vrpayecommerce_recurring` LIKE 'server_mode'";
        $showSqlResult = Shopware()->Db()->query($showSql)->fetchAll();

        if (empty($showSqlResult)) {
            $sql = "ALTER TABLE `payment_vrpayecommerce_recurring`
            ADD `server_mode` VARCHAR(4) NOT NULL AFTER `expiry_year`,
            ADD `channel_id` VARCHAR(32) NOT NULL AFTER `server_mode`";
            Shopware()->Db()->query($sql);
        }
    }

    /**
     * Alter order information table
     */
    private function alterOrderInformation()
    {
        if (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '<')) {
            $sql = "UPDATE `s_core_snippets`
                SET `value` = CASE
                WHEN `value` = 'Free text 4' THEN 'Transaction ID'
                WHEN `value` = 'Freitextfeld 4' THEN 'Transaktions-ID'
                ELSE `value`
                END
                WHERE `namespace` = 'backend/order/main'
                AND `value` IN ('Freitextfeld 4', 'Free text 4')";
        } elseif (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '>=')) {
            $sql = "INSERT INTO `s_attribute_configuration` (table_name, column_name, "
                . "column_type, position, translatable, display_in_backend, custom, "
                . "help_text, support_text, label, entity, array_store) "
                . "VALUES ('s_order_attributes','attribute4','string',1,0,1,0,'','','Transaction ID','NULL',NULL)";
        }
        Shopware()->Db()->query($sql);
    }

    /**
     * Alter order information table
     */
    private function deleteOrderInformation()
    {
        if (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '<')) {
            $sql = "UPDATE `s_core_snippets`
                SET `value` = CASE
                WHEN `value` = 'Transaction ID' THEN 'Free text 4'
                WHEN `value` = 'Transaktions-ID' THEN 'Freitextfeld 4'
                ELSE `value`
                END
                WHERE `namespace` = 'backend/order/main'
                AND `value` IN ('Transaction ID','Transaktions-ID')";
        } elseif (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '>=')) {
            $sql =  "DELETE FROM s_attribute_configuration WHERE `table_name` = 's_order_attributes' AND
                 `column_name` IN ('attribute4') AND
                 `label` IN ('Transaction ID')";
        }
        Shopware()->Db()->query($sql);
    }

    /**
     * Delete all custom payment status
     */
    private function deletePaymentStatus()
    {
        $sql =  "DELETE FROM s_core_states WHERE `group` = 'payment' AND
                 id IN (1750, 1751, 1752, 1753)";
        Shopware()->Db()->query($sql);
        $sql =  "DELETE FROM s_core_snippets WHERE name in ('in_review',"
            . "'pre_authorization_of_payment', 'payment_accepted', "
            . "'refund')";
        Shopware()->Db()->query($sql);
    }

    /**
     * deactive payment method
     */
    private function deactivePayment()
    {
        $sql =  "DELETE FROM s_core_paymentmeans WHERE name
                IN ('vrpay_cc', 'vrpay_ccsaved', 'vrpay_dc', 'vrpay_dd',
                'vrpay_ddsaved', 'vrpay_eps', 'vrpay_giropay', 'vrpay_ideal',
                'vrpay_klarnapaylater', 'vrpay_klarnasliceit', 'vrpay_masterpass', 'vrpay_paypal',
                'vrpay_paypalsaved','vrpay_paydirekt', 'vrpay_klarnaobt',
                'vrpay_swisspostfinance', 'vrpay_easycredit')";

        Shopware()->Db()->query($sql);
    }

    /**
     * create and render configuration form
     */
    private function createConfiguration()
    {
        $this->createVrpayecommercePluginConfig();
        $this->Form();
    }

    /**
     * create form text field
     * @param object $form
     * @param string $id
     * @param string $label
     * @param string $defaultValue
     */
    private function createTextFieldConfig($form, $id, $label, $defaultValue)
    {
        $form->setElement('text', $id, array(
            'label' => $label,
            'required' => false,
            'value' => $defaultValue,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
    }

    /**
     * create form yes or no option field
     * @param object $form
     * @param array $attribute
     * @param string $configName
     * @param boolean $defaultValue
     */
    private function createEnabledField($form, $attribute, $configName, $defaultValue = false)
    {
        if (!$defaultValue) {
            $defaultValue = 'Yes';
        }
        $form->setElement(
            'select',
            $attribute['elementCH'].$configName,
            array(
                'label' => $attribute['pLabel'].' Enabled',
                'value' => $defaultValue,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'store' => array(
                    array('Yes', 'Yes'),
                    array('No', 'No')
                    )
            )
        );
    }

    /**
     * create form true or false option field
     * @param object $form
     * @param array $attribute
     * @param string $configName
     * @param boolean $defaultValue
     */
    private function createFieldActiveOption($form, $attribute, $configName, $defaultValue = false)
    {
        if (!$defaultValue) {
            $defaultValue = 'True';
        }
        $form->setElement(
            'select',
            $attribute['elementCH'].$configName,
            array(
                'label' => $attribute['pLabel'].' Enabled',
                'value' => $defaultValue,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'store' => array(
                    array('True', 'True'),
                    array('False', 'False')
                    )
                )
        );
    }

    /**
     * create form selection field for all card type
     * @param object $form
     * @param array $attribute
     */
    private function createCardTypeSelection($form, $attribute)
    {
        $this->createEnabledField($form, $attribute, '_VISA');
        $this->createEnabledField($form, $attribute, '_MASTER');
        $this->createEnabledField($form, $attribute, '_AMEX');
        $this->createEnabledField($form, $attribute, '_DINERS');
        $this->createEnabledField($form, $attribute, '_JCB');
    }

    /**
     * create form label
     * @param object $form
     * @param array $attribute
     */
    private function createLabel($form, $attribute)
    {
        $form->setElement(
            'button',
            $attribute['elementPM'],
            array(
                'label' => $attribute['pLabel'],
                )
        );
    }

    /**
     * create form selection field for all card type
     * @param object $form
     * @param array $attribute
     */
    private function createServerField($form, $attribute)
    {
        $form->setElement(
            'select',
            $attribute['elementCH'].'_SERVER',
            array('label' => $attribute['pLabel'].' Server',
                'value' => 'TEST',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'store' => array(
                    array('TEST', 'TEST'),
                    array('LIVE', 'LIVE')
                )
            )
        );
    }

    /**
     * create form selection field for transaction mode
     * @param object $form
     * @param array $attribute
     */
    private function createTransactionModeField($form, $attribute)
    {
        $form->setElement(
            'select',
            $attribute['elementCH'].'_MODE',
            array('label' => $attribute['pLabel'].' Transaction Mode',
                'value' => 'DB',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'store' => array(
                    array('DB', 'Debit'),
                    array('PA', 'Pre-Authorization')
                )
            )
        );
    }

    /**
     * create form selection field for enable partial Payment
     * @param object $form
     * @param array $attribute
     */
    private function createPaymentIsPartialField($form, $attribute)
    {
        $form->setElement(
            'select',
            $attribute['elementCH'].'_PAYMENT_IS_PARTIAL',
            array('label' => $attribute['pLabel'].' Partial Capture or Refund',
                'value' => 'false',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'store' => array(
                    array('true', 'Yes'),
                    array('false', 'No')
                )
            )
        );
    }

    /**
     * create configuration form
     */
    private function createVrpayecommercePluginConfig()
    {
        $form = $this->Form();
        $attribute['elementCH'] = 'BACKEND_CH_GENERAL';
        $attribute['elementPM'] = 'BACKEND_PM_GENERAL';
        $attribute['pLabel'] = 'General';
        $this->createLabel($form, $attribute);
        $this->createTextFieldConfig(
            $form,
            $attribute['elementCH'] . '_BEARER_TOKEN',
            $attribute['pLabel'].' Access Token',
            ''
        );
        $this->createTextFieldConfig(
            $form,
            $attribute['elementCH'].'_LOGIN',
            $attribute['pLabel'].' User-ID',
            ''
        );
        $this->createTextFieldConfig(
            $form,
            $attribute['elementCH'].'_PASSWORD',
            $attribute['pLabel'].' Password',
            ''
        );
        $this->createEnabledField(
            $form,
            $attribute,
            '_RECURRING'
        );
        $this->createTextFieldConfig(
            $form,
            $attribute['elementCH'].'_MERCHANTEMAIL',
            $attribute['pLabel'].' Merchant Email',
            ''
        );
        $this->createTextFieldConfig(
            $form,
            $attribute['elementCH'].'_MERCHANTNO',
            $attribute['pLabel'].' Merchant No. (VR pay)',
            ''
        );
        $this->createTextFieldConfig(
            $form,
            $attribute['elementCH'].'_SHOPURL',
            $attribute['pLabel'].' Shop URL',
            ''
        );
        $this->createFieldActiveOption(
            $form,
            $attribute,
            '_VERSION_TRACKER'
        );

        $this->createTextFieldConfig(
            $form,
            $attribute['elementCH'].'_MERCHANT_LOCATION',
            $attribute['pLabel'].' Merchant Location',
            ''
        );

        foreach ($this->paymentMethods as $pValue => $pSub) {
            $pMethod = strtoupper(substr($pValue, 6));
            $attribute['elementCH'] = 'BACKEND_CH_'.$pMethod;
            $attribute['elementPM'] = 'BACKEND_PM_'.$pMethod;
            $attribute['pLabel'] = $pSub['description'];
            $this->createLabel($form, $attribute);
            $this->createEnabledField($form, $attribute, '_ACTIVE');
            $this->createServerField($form, $attribute);
            if ($pValue == 'vrpay_cc' || $pValue == 'vrpay_ccsaved' ||
                $pValue == 'vrpay_dc' || $pValue == 'vrpay_dd' ||
                $pValue == 'vrpay_ddsaved' || $pValue == 'vrpay_paydirekt') {
                $this->createTransactionModeField($form, $attribute);
            }
            if ($pValue == 'vrpay_cc' || $pValue == 'vrpay_ccsaved') {
                $this->createCardTypeSelection($form, $attribute);
            }
            if ($pValue == 'vrpay_ccsaved' || $pValue == 'vrpay_ddsaved' || $pValue == 'vrpay_paypalsaved') {
                $this->createTextFieldConfig(
                    $form,
                    $attribute['elementCH'].'_AMOUNT',
                    $attribute['pLabel'].' Amount for registration',
                    ''
                );
            }
            if ($pValue == 'vrpay_ccsaved') {
                $this->createEnabledField(
                    $form,
                    $attribute,
                    '_MULTICHANNEL',
                    'No'
                );
            }
                $this->createTextFieldConfig(
                    $form,
                    $attribute['elementCH'].'_CHANNEL',
                    $attribute['pLabel'].' Entity-ID',
                    ''
                );

            if ($pValue == 'vrpay_ccsaved') {
                $this->createTextFieldConfig(
                    $form,
                    $attribute['elementCH'].'_CHANNELMOTO',
                    $attribute['pLabel'].' Entity-ID MOTO',
                    ''
                );
            }
            if ($pValue == 'vrpay_klarnasliceit') {
                $this->createTextFieldConfig(
                    $form,
                    $attribute['elementCH'].'_PCLASS',
                    $attribute['pLabel'].' Installment Plan (PCLASS)',
                    ''
                );
            }
            if ($pValue == 'vrpay_easycredit') {
                $this->createTextFieldConfig(
                    $form,
                    $attribute['elementCH'].'_SHOPNAME',
                    $attribute['pLabel'].' Shop name',
                    ''
                );
            }
            if ($pValue == 'vrpay_paydirekt') {
                $this->createPaymentIsPartialField($form, $attribute);
                $this->createTextFieldConfig(
                    $form,
                    $attribute['elementCH'].'_MINIMUM_AGE',
                    $attribute['pLabel'].' Minimum Age',
                    ''
                );
            }
        }
    }

    /**
     * Create and save payment methods
     */
    private function registerPaymentMethods()
    {
        $pPosition = 0;
        foreach ($this->paymentMethods as $pValue => $pSub) {
            if ($this->isAboveShopwareVersion52()) {
                $action = 'payment_processor_csrf/process';
            } else {
                $action = 'payment_processor/process';
            }
            $this->createPayment(array(
                'name' => $pValue,
                'description' => $pSub['description'],
                'action' => $action,
                'active' => 0,
                'position' => $pPosition,
                'additionalDescription' => ''
            ));
            $pPosition++;
        }
    }

    /**
     * Creates and subscribe the events and hooks.
     */
    private function registerEventHandlers()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentProcessorCsrf',
            'onGetControllerPathFrontend'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentProcessor',
            'onGetControllerPathFrontend'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentInformation',
            'onGetControllerPathFrontendInformation'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_Backend_Order_Save',
            'onOrderSaveAction'
        );
        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_VrpayecommerceClient',
            'onInitResourceVrpayecommerceClient'
        );
        $this->subscribeEvent(
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter',
            'onGetPaymentMeans'
        );
        $this->subscribeEvent(
            'Shopware_Modules_Admin_GetPaymentMeanById_DataFilter',
            'onGetPaymentMeanById'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch',
            'onPostDispatch'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Account',
            'onPostDispatchTemplate'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
            'onPostDispatchFinish'
        );
         $this->subscribeEvent(
             'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
             'onFrontendCheckoutPostDispatch'
         );
    }

    /**
     * Backend - Autofill Plugin Configuration
     * @param string $pMethod
     * @param string $defaultValue
     * @param array $elementValues
     */
    private function setActivatedPayment($pMethod, $defaultValue, $elementValues)
    {
        $sql = "SELECT id from s_core_paymentmeans where name = '".$pMethod."'";
        $query = Shopware()->Db()->query($sql);
        $data = $query->fetchAll();
        $paymentID = $data[0]['id'];

        $sql =  "DELETE FROM s_core_paymentmeans_subshops WHERE paymentID = '".$paymentID."'";
        Shopware()->Db()->query($sql);

        $active = '0';

        foreach ($elementValues as $key => $value) {
            if (empty($value['value'])) {
                $value['value'] = $defaultValue;
            }

            if ($value['value'] == 'Yes') {
                $sql =  "INSERT INTO s_core_paymentmeans_subshops (paymentID, subshopID)
                    VALUES ('".$paymentID."', '".$value['shopId']."')";
                Shopware()->Db()->query($sql);
                $active = '1';
            }
        }

        $sql =  "UPDATE s_core_paymentmeans SET active = '".$active."' WHERE name = '".$pMethod."'";
        Shopware()->Db()->query($sql);
    }

    /**
     * Active plugin configuration
     * @param string $active
     * @param string $paymentName
     * @param string $shopId
     */
    private function setActivatedConfig($active, $paymentName, $shopId)
    {
        if (empty($shops)) {
            $shops = $this->getActiveShops();
        }

        $elementName = $this->paymentMethods[$paymentName]['element'];
        $elementId   = $this->getElementId($elementName);
        $this->updateSubConfig($active, $elementId, $shopId);
    }

    /**
     * get id from configuration element
     * @param string $elementName
     * @return string
     */
    private function getElementId($elementName)
    {
        $sql = "SELECT id from s_core_config_elements where name = '".$elementName."'";
        $query = Shopware()->Db()->query($sql);
        $data = $query->fetchAll();
        return $data[0]['id'];
    }

    /**
     * check configs availability
     * @param string $elementId
     * @param string $shopId
     * @return boolean
     */
    private function isHaveConfig($elementId, $shopId)
    {
        $sql = "SELECT id from s_core_config_values
            where element_id = '".$elementId."' and shop_id = '".$shopId."'";
        $query = Shopware()->Db()->query($sql);
        $data = $query->fetchAll();
        return !empty($data[0]['id']);
    }

    /**
     * change and insert configs value
     * @param string $active
     * @param string $elementId
     * @param string $shopId
     */
    private function updateSubConfig($active, $elementId, $shopId)
    {
        $value = $active ? serialize('Yes') : serialize('No');
        $isHaveConfig = $this->isHaveConfig($elementId, $shopId);
        if ($isHaveConfig) {
            $sql = "UPDATE s_core_config_values set value = '".$value."'
                where element_id = '".$elementId."' and shop_id = '".$shopId."'";
        } else {
            $sql = "INSERT INTO s_core_config_values (element_id, shop_id, value)
                VALUES ('".$elementId."', '".$shopId."', '".$value."')";
        }
        Shopware()->Db()->query($sql);
    }

    /**
     * Provide active shop data
     *
     * @return array
     */
    private function getActiveShops()
    {
        $sql = "SELECT `id` from s_core_shops where `active` = 1";
        $query = Shopware()->Db()->query($sql);
        return $query->fetchAll();
    }

    /**
     * Backend Event
     * @param Enlight_Event_EventArgs $args
     * @return null
     */
    public function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        if ($request->getModuleName() == 'backend') {
            if (strtolower($request->getControllerName()) == 'config') {
                if ($request->getActionName() == 'saveForm') {
                    $this->onSaveForm($args);
                    return;
                }
            } elseif ($request->getControllerName() == 'payment') {
                if ($request->getActionName() == 'updatePayments') {
                    $this->OnUpdatePayments($args);
                    return;
                }
            }
        }
    }

    /**
     * Save Form on Plugin Configuration
     * @param Enlight_Event_EventArgs $args
     * @return boolean
     */
    private function onSaveForm(Enlight_Event_EventArgs $args)
    {
        $request  = $args->getSubject()->Request();
        $elements = $request->getPost('elements');

        foreach ($elements as $key => $element) {
            foreach ($this->paymentMethods as $pValue => $pSub) {
                if ($element['name'] == $pSub['element']) {
                    $this->setActivatedPayment($pValue, $element['value'], $element['values']);
                }
            }
        }
        return true;
    }

    /**
     * Update payment activated from Payment methods
     * @param Enlight_Event_EventArgs $args
     * @return boolean
     */
    private function OnUpdatePayments(Enlight_Event_EventArgs $args)
    {
        $request     = $args->getSubject()->Request();
        $paymentName = $request->name;
        $active      = $request->active;
        $shops       = $request->shops;
        $activeShops = $this->getActiveShops();

        $selectedShops = array();
        foreach ($shops as $key => $shop) {
            $selectedShops[] = $shop['id'];
        }

        if ($active and ! empty($selectedShops)) {
            foreach ($activeShops as $key => $shop) {
                if (in_array($shop['id'], $selectedShops)) {
                    $newActive = true;
                } else {
                    $newActive = false;
                }
                $this->setActivatedConfig($newActive, $paymentName, $shop['id']);
            }
        }

        if (!$active || empty($selectedShops)) {
            foreach ($activeShops as $key => $shop) {
                $this->setActivatedConfig($active, $paymentName, $shop['id']);
            }
        }
        return true;
    }

    /**
     * end Backend
     *
     */

    /**
     * Provide Locale data from the shop
     * @param string $sLanguage
     * @return string
     */
    private function getLocale($sLanguage)
    {
        $sql = "SELECT l.locale from s_core_shops s join s_core_locales l on s.locale_id = l.id where s.id = ?";
        $query = Shopware()->Db()->query($sql, array($sLanguage));
        $locales = $query->fetchAll();
        return $locales[0]['locale'];
    }

     /**
     * Provide default language from shop
     * @param string $languageId
     * @return string
     */
    private function getLanguage($languageId)
    {
        $sql = "SELECT locale from s_core_locales where id = ?";
        $query = Shopware()->Db()->query($sql, array($languageId));
        $locales = $query->fetchAll();
        return $locales[0]['locale'];
    }

    /**
     * Provide default language ID from shop
     *
     * @return string
     */
    private function getLanguageId()
    {
        $languageId = "";
        if (isset($_SESSION['Shopware']["Auth"]->localeID) && $_SESSION['Shopware']["Auth"]->localeID != "") {
            $languageId = $_SESSION['Shopware']["Auth"]->localeID;
        }
        return $languageId;
    }

    /**
     * Provide notification message base on language id
     * @param string $isoCode
     * @return string
     */
    private function getNotificationMessage($isoCode)
    {
        switch ($isoCode) {
            case 'de_DE':
                $notificationMessage = 'WARNUNG Für die Bereitstellung der besten Dienste für Sie, '
                                       . 'um Sie über neuere Versionen des Plugins und auch '
                                       . 'über Sicherheitsprobleme zu informieren, sammelt VR pay einige '
                                       . 'grundlegende und technische Informationen aus dem Shop-System '
                                       . '(für Details siehe Handbuch). Die Informationen werden unter keinen '
                                       . 'Umständen für Marketing- und / oder Werbezwecke verwendet. '
                                       . 'Bitte beachten Sie, dass das Deaktivieren des Versions-Trackers '
                                       . 'die Servicequalität sowie wichtige Sicherheits- und '
                                       . 'Aktualisierungsinformationen beeinträchtigen kann.';
                break;
            default:
                $notificationMessage = 'WARNING For providing the best service to you, '
                                       . 'to inform you about newer versions of the '
                                       . 'plugin and also about security issues, VR pay is '
                                       . 'gathering some basic and technical information '
                                       . 'from the shop system (for details please see the manual). '
                                       . 'The information will under no circumstances be used '
                                       . 'for marketing and/or advertising purposes.Please be '
                                       . 'aware that deactivating the version tracker may affect '
                                       . 'the service quality and also important security and '
                                       . 'update information.';
        }
        return $notificationMessage;
    }

    /**
     * Provide snippet value
     * @param string $identifier
     * @param string $localeId
     * @return string
     */
    private function getSnippet($identifier, $localeId)
    {
        $sql = "SELECT value from s_core_snippets where name = ? "
               . "and namespace = 'frontend/checkout/shipping_payment' and localeId = ? ";
        $query = Shopware()->Db()->query($sql, array($identifier, $localeId));
        $data = $query->fetchAll();
        return $data[0]['value'];
    }

    /**
     * Provide snippet value
     * @param string $identifier
     * @param string $localeId
     * @return string
     */
    private function getEasycreditValidationSnippet($identifier, $localeId)
    {
        $sql = "SELECT value from s_core_snippets where name = ? "
               . "and namespace = 'frontend/account/payment' and localeId = ? ";
        $query = Shopware()->Db()->query($sql, array($identifier, $localeId));
        $data = $query->fetchAll();
        return $data[0]['value'];
    }

    /**
     * Provide local payment
     * @param array $userInfo
     * @return string
     */
    private function getPaymentLocale($userInfo)
    {
        $identifier = $this->paymentMethods[$userInfo['paymentMean']['name']]['identifier'];
        $localeId   = $userInfo['locale'] == 'de_DE' ? '1' : '2';
        if ($userInfo['paymentMean']['name'] == 'klarnaobt') {
            $userCountryIso = $userInfo['userData']['additional']['country']['countryiso'];
            if ($userCountryIso == 'DE' || $userCountryIso == 'AT' || $userCountryIso == 'CH') {
                $userInfo['paymentMean']['description'] = $this->getSnippet($identifier, '1');
            } else {
                $userInfo['paymentMean']['description'] = $this->getSnippet($identifier, '2');
            }
        } else {
            $userInfo['paymentMean']['description'] = $this->getSnippet($identifier, $localeId);
        }
        return $userInfo['paymentMean'];
    }

    /**
     * Event for custom code
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchTemplate(Enlight_Event_EventArgs $args)
    {
        $view   = $args->getSubject()->View();
        $client = $this->Client();
        if ($client->isRecurringActive()) {
            $view->addTemplateDir(dirname(__FILE__).'/Views/frontend/account');
            $view->extendsTemplate('sidebar.tpl');
        }
    }

    /**
     * Event for custom code
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchFinish(Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        $view    = $args->getSubject()->View();

        $sMessage             = $request->getParam('sMessage');
        $view->successMessage = $sMessage;
        $view->addTemplateDir(dirname(__FILE__).'/Views/frontend/checkout');
        $view->extendsTemplate('finish.tpl');
    }

    /**
     * Checkout related modifications
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFrontendCheckoutPostDispatch(Enlight_Event_EventArgs $args)
    {
        $action = $args->getSubject();
        $request = $action->Request();
        $view = $action->View();
        $userData = $view->getAssign('sUserData');

        if ($request->getActionName() == 'confirm' &&
            $userData['additional']['payment']['name'] == 'vrpay_easycredit'
        ) {
            $view->addTemplateDir(dirname(__FILE__).'/Views/frontend/checkout');
            $view->extendsTemplate('confirm.tpl');
        }
    }

    /**
     * Provide local payment
     * @param Enlight_Event_EventArgs $arguments
     * @return string
     */
    public function onGetPaymentMeanById(Enlight_Event_EventArgs $arguments)
    {
        $userInfo['userData'] = $arguments->getUser();

        $sLanguage = Shopware()->Shop()->getId();
        $userInfo['locale'] = $this->getLocale($sLanguage);

        $userInfo['paymentMean'] = $arguments->getReturn();

        if (strpos($userInfo['paymentMean']['name'], 'vrpay_') !== false) {
              $paymentMean = $this->getPaymentLocale($userInfo);
        }
        return $paymentMean;
    }

    /**
     * Provide payment method for client
     * @param Enlight_Event_EventArgs $arguments
     * @return array
     */
    public function onGetPaymentMeans(Enlight_Event_EventArgs $arguments)
    {
        $adminClass           = $arguments->getSubject();
        $userInfo['userData'] = $adminClass->sGetUserData();

        $sLanguage          = Shopware()->Shop()->getId();
        $userInfo['locale'] = $this->getLocale($sLanguage);

        $paymentMeans = $arguments->getReturn();

        //payment method list if recurring enabled or disabled.
        $paymentNonRecurrings = array("vrpay_cc", "vrpay_dd", "vrpay_paypal");
        $paymentRecurrings    = array("vrpay_ccsaved", "vrpay_ddsaved", "vrpay_paypalsaved");
        $client               = $this->Client();

        foreach ($paymentMeans as $key => &$userInfo['paymentMean']) {
            if (strpos($userInfo['paymentMean']['name'], 'vrpay_') !== false) {
                if ($userInfo['paymentMean']['active']) {
                    $paymentLocale                                    = $this->getPaymentLocale($userInfo);
                    $userInfo['paymentMean']['description']           = $paymentLocale['description'];
                    $userInfo['paymentMean']['additionaldescription'] = $this->getPaymentLogo(
                        $userInfo['paymentMean']['name']
                    );
                    if ($userInfo['paymentMean']['name'] == 'vrpay_cc' ||
                        $userInfo['paymentMean']['name'] == 'vrpay_ccsaved') {
                        $client->setPaymentMethod($userInfo['paymentMean']['name']);
                        $brand = $client->getBrandCard();
                        if (empty($brand) || $brand == '') {
                            unset($paymentMeans[$key]);
                        }
                    }

                    if ($client->isRecurringActive()) {
                        if ($userInfo['userData']['additional']['user']['accountmode'] == "1") {
                            if (in_array($userInfo['paymentMean']['name'], $paymentRecurrings)) {
                                unset($paymentMeans[$key]);
                            }
                        } else {
                            if (in_array($userInfo['paymentMean']['name'], $paymentNonRecurrings)) {
                                unset($paymentMeans[$key]);
                            }
                        }
                    } else {
                        if (in_array($userInfo['paymentMean']['name'], $paymentRecurrings)) {
                            unset($paymentMeans[$key]);
                        }
                    }
                }
            }
        }
        return $paymentMeans;
    }

    /**
     * Provide payment logo
     * @param array $paymentMethod
     * @return string
     */
    private function getPaymentLogo($paymentMethod)
    {
        $sLanguage = Shopware()->Shop()->getId();
        $locale    = $this->getLocale($sLanguage);
        $logoTitle = $this->paymentMethods[$paymentMethod]['description'];

        if ($locale == 'de_DE') {
            $logo = $this->paymentMethods[$paymentMethod]['logo_de'];
        } else {
            $logo = $this->paymentMethods[$paymentMethod]['logo'];
        }

        $logoHtml = '';
        switch ($paymentMethod) {
            case 'vrpay_cc':
            case 'vrpay_ccsaved':
            case 'vrpay_dc':
                $client = $this->Client();
                $client->setPaymentMethod($paymentMethod);
                $brand  = $client->getBrandCard();
                $brands = explode(' ', $brand);
                if ($brands) {
                    foreach ($brands as $value) {
                        $logoHtml .= '<img src="'.$this->imgPath.strtolower($value).
                                     '.png" alt="'.$value.'" title="'.$value.
                                     '" style="height:40px !important; float:left; margin-right:5px;" />';
                    }
                }
                break;
            case 'vrpay_easycredit':
                $logoHtml = '<img src="'.$this->imgPath.$logo.'" alt="'.$logoTitle.
                            '" title="'.$logoTitle.'" class ="'.$logoTitle.'" style="height:40px !important" />'.
                            $this->getEasycreditNotification();
                break;
            default:
                $logoHtml = '<img src="'.$this->imgPath.$logo.'" alt="'.$logoTitle.
                            '" title="'.$logoTitle.'" class ="'.$logoTitle.'" style="height:40px !important" />';
                break;
        }

        return $logoHtml;
    }

    public function getEasycreditNotification()
    {
        $shopId = Shopware()->Shop()->getId();
        $locale = $this->getLocale($shopId);
        $localeId   = $locale == 'de_DE' ? '1' : '2';

        $easycreditNotify = '';
        $easycreditValidation = $this->getEasycreditValidation();
        
        if ($easycreditValidation) {
            for ($i=0; $i<count($easycreditValidation); $i++) {
                $easycreditNotify .= "<div id= 'easycredit_error_message'>"
                .$this->getEasycreditValidationSnippet($easycreditValidation[$i], $localeId).
                "</div>";
            }

            $easycreditNotify .= $this->getEasycreditErrorMessage();
        } else {
            $easycreditTocTransalation = $this->getEasycreditValidationSnippet('FRONTEND_EASYCREDIT_TERMS', $localeId);
            $customerData = Shopware()->Modules()->Admin()->sGetUserData();
            $customerAddress = $customerData["billingaddress"]['street'].", ".$customerData["billingaddress"]['zipcode']
                    ." ".$customerData["billingaddress"]['city'];
           
            
            $easycreditTocTransalation = str_replace("%y", $customerAddress, $easycreditTocTransalation);
            $client = $this->Client();
            $shopName = $client->getConfig("BACKEND_CH_EASYCREDIT_SHOPNAME");
            $easycreditTocTransalation = str_replace("%x", $shopName, $easycreditTocTransalation);
            $easycreditNotify .= '<div style="width:100%"><dl style="margin-top: 0; margin-bottom: 20px;">'
                    .'<dt style="float:left; width: 2%;"><input id="toc_easycredit" type="checkbox"'
                    .'name="toc_easycredit"></dt><dd style="width:90%;"><span>'
                    .$easycreditTocTransalation
                    .'</span></dd></dl></div>';
        }
        $easycreditNotify .= $this->getEasycreditDisabled();
        return $easycreditNotify;
    }
    
    /**
     * get error message for easycredit on html format
     *
     * @return string
     */
    public function getEasycreditErrorMessage()
    {
        if (isset($_GET['error'])) {
            $error = true;
        }

        $message = '';
        if ($error) {
            $message = '<script language="javascript">
            var d = document.createElement("div");
            var htmlMessage = \'<div class="alert is--error is--rounded">
            <div class="alert--icon"><i class="icon--element icon--cross"></i></div>
            <div class="alert--content">
                Unfortunately, your transaction has failed. Please select other payment method
            </div>
            </div>\';
            d.innerHTML = htmlMessage;
            var container = document.getElementsByClassName("confirm--outer-container")[0];
            var messageNode = d.firstChild;container.parentNode.insertBefore(messageNode, container);
            </script>';
        }

        return $message;
    }

    /**
     * Gets the easycredit validation.
     *
     * @return     array  The easycredit validation.
     */
    public function getEasycreditValidation()
    {
        if (!$this->isLoginDataFilled()) {
            $easycreditValidation[] = 'MODULE_PAYMENT_VRPAYECOMMERCE_EASYCREDIT_TEXT_ERROR_CREDENTIALS';
        }
        if (!$this->isGenderNotEmpty()) {
            $easycreditValidation[] = 'ERROR_MESSAGE_EASYCREDIT_PARAMETER_GENDER';
        }

        if (!$this->isAmountAllowed()) {
            $easycreditValidation[] = 'ERROR_MESSAGE_EASYCREDIT_AMOUNT_NOTALLOWED';
        }

        if (!$this->isBillingEqualShipping()) {
            $easycreditValidation[] = 'ERROR_EASYCREDIT_BILLING_NOTEQUAL_SHIPPING';
        }

        return $easycreditValidation;
    }

    /**
     * Determines if login data filled.
     *
     * @return     boolean  True if login data filled, False otherwise.
     */
    public function isLoginDataFilled()
    {
        $client = $this->Client();
        $credentials = $client->getCredentials();

        if (!empty($credentials['bearerToken']) || !empty($credentials['login']) || !empty($credentials['password'])) {
            return true;
        }

        return false;
    }

    /**
     * Gets the customer gender.
     *
     * @return     String  The customer gender.
     */
    public function getCustomerGender()
    {
        $user = Shopware()->Modules()->Admin()->sGetUserData();

        if (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '<')) {
            $customerGender = $user["billingaddress"]["salutation"];
        } elseif (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '>=')) {
            $customerGender = $user["additional"]["user"]["salutation"];
        }

        return $customerGender;
    }

    /**
     * Determines if gender not empty.
     *
     * @return     boolean  True if gender not empty, False otherwise.
     */
    public function isGenderNotEmpty()
    {
         $customerGender = $this->getCustomerGender();

        if (!empty($customerGender)) {
            return true;
        }
        return false;
    }

    /**
     * Determines if currency euro.
     *
     * @return boolean
     */
    public function isCurrencyEuro()
    {
        $currency = Shopware()->Shop()->getCurrency()->getCurrency();

        if ($currency != 'EUR') {
            return false;
        }
        return true;
    }

    /**
     * Gets the total amount.
     *
     * @return string
     */
    public function getTotalAmount()
    {
        $amount = Shopware()->Modules()->Basket()->sGetAmount();
        $totalAmount = $amount['totalAmount'];

        if (!empty($totalAmount)) {
            return $totalAmount;
        }

        return '0';
    }

    /**
     * Determines if amount allowed.
     *
     * @return     boolean  True if amount allowed, False otherwise.
     */
    public function isAmountAllowed()
    {
        $totalAmount = $this->getTotalAmount();
        $isCurrencyEuro = $this->isCurrencyEuro();

        if ($isCurrencyEuro && $totalAmount >=200 && $totalAmount <=5000) {
            return true;
        }

        return false;
    }

    /**
     * is billing equal shipping
     *
     * @return boolean
     */
    public function isBillingEqualShipping()
    {
        $user = Shopware()->Modules()->Admin()->sGetUserData();

        $billingAddress = $user["billingaddress"]["street"].' '.$user["billingaddress"]["city"].
                          ' '.$user["billingaddress"]["zipcode"].' '.$user["billingaddress"]["countryID"];

        $shippingAddress = $user["shippingaddress"]["street"].' '.$user["shippingaddress"]["city"].
                          ' '.$user["shippingaddress"]["zipcode"].' '.$user["shippingaddress"]["countryID"];

        if ($billingAddress == $shippingAddress) {
            return true;
        }
        return false;
    }

    /**
     * Gets the easycredit disabled.
     *
     * @return     string  The easycredit disabled.
     */
    public function getEasycreditDisabled()
    {
        $easycreditNotify = '<script src="'.$this->getUrl().$this->jsPath.'" type="text/javascript"></script>';
        return $easycreditNotify;
    }

    /**
     * Gets the url.
     *
     * @return     String  The url.
     */
    public function getUrl()
    {
        $request = Shopware()->Front()->Request();
        $basePath = $request->getHttpHost() . $request->getBasePath() . '/';

        return $request->getScheme() . '://' . $basePath;
    }

    /**
     * Creates and returns the Vrpayecommerce client for an event.
     *
     * @param Enlight_Event_EventArgs $args
     * @return \Shopware_Components_Vrpayecommerce_Client
     */
    public function onInitResourceVrpayecommerceClient()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware_Components_Vrpayecommerce',
            $this->Path() . 'Components/Vrpayecommerce/'
        );
        $client = new Shopware_Components_Vrpayecommerce_Client($this->Config());
        return $client;
    }

    /**
     * @return \Shopware_Components_Vrpayecommerce_Client
     */
    public function Client()
    {
        return $this->Application()->VrpayecommerceClient();
    }

    /* Handles payment status change event in the edit order page.
       For every wait to paid state change for orders with cc method, we need to send CC.CP transaction to the gateway.
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onOrderSaveAction(Enlight_Event_EventArgs $arguments)
    {
        $client = $this->Client();
        $backendOrderController = $arguments->getSubject();
        $request = $backendOrderController->Request();

        $params = $request->getParams();
        $payment = $params['payment'][0]['name'];
        $paymentStatusNow = $params['cleared'];
        $orderId = $request->getParam('id', null);

        $locale = $request->getParam('locale');
        $localeId = $locale[0]['id'];

        if (!empty($orderId)) {
            $order = $this->getRepository()->find($orderId);
            $paymentStatusBefore = $order->getPaymentStatus()->getId();
            $data = array(
                    'refId' => $order->getTransactionId(),
                    'amount' => $params['invoiceAmountEuro'],
                    'currency' => $params['currency'],
                    'payment' => $payment
                );

            if ($paymentStatusBefore == 1750 && $paymentStatusNow == 1750) {
                $transactionResult = $client->updateStatus($data, $returnCode);
                if ($transactionResult != 'ACK') {
                    $errorIdentifier = $client->getErrorIdentifierBackend($returnCode);
                    $errorMessage = $this->getBackendSnippet($errorIdentifier, $localeId);
                    throw new Exception($errorMessage);
                }
            } elseif ($paymentStatusBefore == 1751 && $paymentStatusNow == 1752) {
                $transactionResult = $client->executePayment($data, 'CP', $returnCode);
                if ($transactionResult != 'ACK') {
                    $errorIdentifier = $client->getErrorIdentifierBackend($returnCode);
                    $errorMessage = $this->getBackendSnippet($errorIdentifier, $localeId);
                    throw new Exception($errorMessage);
                }
            } elseif ($paymentStatusBefore == 1752 && $paymentStatusNow == 1753) {// payment accepted => refund
                $transactionResult = $client->executePayment($data, 'RF', $returnCode);
                if ($transactionResult != 'ACK') {
                    $errorIdentifier = $client->getErrorIdentifierBackend($returnCode);
                    $errorMessage = $this->getBackendSnippet($errorIdentifier, $localeId);
                    throw new Exception($errorMessage);
                }
            }
        }
    }

    /**
     * Provide snippet data for backend
     * @param string $name
     * @param string $localeId
     * @param string $namespace
     * @param int $shopId
     * @return string
     */
    public function getBackendSnippet($name, $localeId, $namespace = 'backend/error/index', $shopId = 1)
    {
        $sql = 'SELECT `value` FROM `s_core_snippets` WHERE `namespace` = ? '.
               'AND `shopID` = ? AND `localeID` = ? AND `name` = ?';
        $data = current(Shopware()->Db()->fetchAll($sql, array($namespace, $shopId, $localeId, $name)));
        return $data['value'];
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathFrontend()
    {
        $this->registerMyTemplateDir();
        if ($this->isAboveShopwareVersion52()) {
            return $this->Path() . 'Controllers/Frontend/PaymentProcessorCsrf.php';
        } else {
            return $this->Path() . 'Controllers/Frontend/PaymentProcessor.php';
        }
    }

    /**
     * Provide template directoy path
     */
    protected function registerMyTemplateDir()
    {
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/', 'payment_processor');
    }

    /**
     * Provide payment information controller and teplate directory path
     */
    public function onGetControllerPathFrontendInformation()
    {
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/', 'payment_information');
        return $this->Path() . 'Controllers/Frontend/PaymentInformation.php';
    }

    /**
     * This function is running when plugin is deactivated
     * Disable payment methods from database
     *
     * @return bool
     */
    public function disable()
    {
        try {
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_cc'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_ccsaved'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_dd'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_ddsaved'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_giropay'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_klarnapaylater'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_klarnasliceit'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_paypal'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_paypalsaved'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_paydirekt'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_klarnaobt'";
            Shopware()->Db()->query($sql);
            $sql =  "UPDATE s_core_paymentmeans SET active = '0' WHERE name = 'vrpay_easycredit'";
            Shopware()->Db()->query($sql);
            return $result['success'] = true;
        } catch (Exception $e) {
            return $result['success'] = false;
        }
    }

    /**
     * This function is running when plugin is activated
     * Enable payment method on frontend if payment method is enabled on configuration
     *
     * @return bool
     */
    public function enable()
    {
        try {
            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_CC_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_cc'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_CCSAVED_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_ccsaved'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_DD_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_dd'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_DDSAVED_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_ddsaved'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_GIROPAY_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_giropay'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_KLARNAPAYLATER_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_klarnapaylater'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_KLARNASLICEIT_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_klarnasliceit'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_PAYPAL_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_paypal'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_PAYPALSAVED_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_paypalsaved'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_PAYDIREKT_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_paydirekt'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_KLARNAOBT_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_klarnaobt'";
                Shopware()->Db()->query($sql);
            }

            $sql =  "SELECT value FROM s_core_config_values WHERE element_id = '" .
              $this->getElementId('BACKEND_CH_EASYCREDIT_ACTIVE')."'";
            Shopware()->Db()->query($sql);
            $query = Shopware()->Db()->query($sql);
            $data = $query->fetchAll();
            if (!isset($data[0]['value'])) {
                $sql =  "UPDATE s_core_paymentmeans SET active = '1' WHERE name = 'vrpay_easycredit'";
                Shopware()->Db()->query($sql);
            }
            return $result['success'] = true;
        } catch (Exception $e) {
            return $result['success'] = false;
        }
    }

    /**
     * Compare versions.
     *
     * @param string   $version   Like: 5.2.0
     * @param string   $operator  Like: <=
     *
     * @return mixed
     */
 
    public function versionCompare( $version, $operator )
    {
        // return by default version compare
        return version_compare( Shopware()->Config()->get( 'Version' ), $version, $operator );
    }
 
    /**
     * Check if current environment is shopware 5.
     *
     * @return bool
     */
 
    public function isAboveShopwareVersion52()
    {
        // return if this is shopware 5.2
        return $this->versionCompare( '5.2.0', '>=' );
    }

    /**
     * Get Payment Controller.
     *
     * @return string
     */
 
    private function getPaymentController()
    {
        if ($this->isAboveShopwareVersion52()) {
            return 'payment_processor_csrf';
        } else {
            return 'payment_processor';
        }
    }
}
