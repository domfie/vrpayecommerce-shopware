<?php

require_once dirname(__FILE__).'/Core.php';

if (version_compare( Shopware()->Config()->get( 'Version' ), '5.2.0', '>=' )) {
    require_once dirname(__FILE__).'/../../Controllers/Frontend/PaymentProcessorCsrf.php';
} else {
    require_once dirname(__FILE__).'/../../Controllers/Frontend/PaymentProcessor.php';
}

require_once dirname(__FILE__).'/Versiontracker.php';

/**
 * Shopware Vrpayecommerce Client
 */

class Shopware_Components_Vrpayecommerce_Client
{
    protected $client = 'CardProcess';
    protected $shop_system = 'Shopware';

    protected $paymentMethod;
    protected $paymentMethodUpperCase;
    protected $testMode = 'EXTERNAL';

    /**
    * set default payment method
    * @param string $paymentMethod
    */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->paymentMethodUpperCase = strtoupper(substr($paymentMethod, 6));
    }

    /**
    * provide credit cards brand
    * @return string
    */
    public function getBrandCard()
    {
        $brand = '';
        if ($this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_VISA') == 'Yes') {
            $brand .= 'VISA ';
        }
        if ($this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_MASTER') == 'Yes') {
            $brand .= 'MASTER ';
        }
        if ($this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_AMEX') == 'Yes') {
            $brand .= 'AMEX ';
        }
        if ($this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_DINERS') == 'Yes') {
            $brand .= 'DINERS ';
        }
        if ($this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_JCB') == 'Yes') {
            $brand .= 'JCB ';
        }
        return trim($brand);
    }

    /**
    * provide payment method brand
    * @return string
    */
    public function getBrand()
    {
        switch ($this->paymentMethod) {
            case 'vrpay_cc':
            case 'vrpay_ccsaved':
                return $this->getBrandCard();
            case 'vrpay_dc':
                return 'VPAY MAESTRO DANKORT VISAELECTRON POSTEPAY';
            case 'vrpay_dd':
            case 'vrpay_ddsaved':
                return 'DIRECTDEBIT_SEPA';
            case 'vrpay_klarnapaylater':
                return 'KLARNA_INVOICE';
            case 'vrpay_klarnasliceit':
                return 'KLARNA_INSTALLMENTS';
            case 'vrpay_paypalsaved':
                return 'PAYPAL';
            case 'vrpay_klarnaobt':
                return 'SOFORTUEBERWEISUNG';
            case 'vrpay_easycredit':
                return 'RATENKAUF';
            default:
                return $this->paymentMethodUpperCase;
        }
    }

    /**
    * provide Vrpayecommerce credentials data
    * @return array
    */
    public function getCredentials()
    {
        $credentials = array(
            'server_mode' => $this->getServerMode(),
            'channel_id'  => $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_CHANNEL'),
            'bearerToken' => $this->getConfig('BACKEND_CH_GENERAL_BEARER_TOKEN'),
            'login'       => $this->getConfig('BACKEND_CH_GENERAL_LOGIN'),
            'password'    => $this->getConfig('BACKEND_CH_GENERAL_PASSWORD')
        );

        if ($this->isMultiChannel()) {
            $credentials['channel_id_moto'] = $this->getConfig(
                'BACKEND_CH_'.$this->paymentMethodUpperCase.'_CHANNELMOTO'
            );
        }
        return $credentials;
    }

    /**
    * provide server type configs value
    * @return string
    */
    public function getServerMode()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_SERVER');
    }

    /**
    * provide multichanel configs value
    * @return string
    */
    public function isMultiChannel()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_MULTICHANNEL') == 'Yes';
    }

    public function getRegisterAmount()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_AMOUNT');
    }

    /**
    * provide Payment Is Partial configs value
    * @return string
    */
    public function getPaymentIsPartial()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_PAYMENT_IS_PARTIAL');
    }

    /**
    * provide minimum age configs value
    * @return string
    */
    public function getMinimumAge()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_MINIMUM_AGE');
    }
    
    /**
    * provide shop name from plugin configs
    * @return string
    */
    public function getShopName()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_SHOPNAME');
    }

    /**
    * provide cards Payment type configs value
    * @return string
    */
    public function getPaymentTypeCard()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_MODE');
    }

    /**
    * provide klarna pclass configs value
    * @return string
    */
    public function getKlarnaPclass()
    {
        return $this->getConfig('BACKEND_CH_'.$this->paymentMethodUpperCase.'_PCLASS');
    }

    /**
    * provide Payment type for all paymet mehtod configs value
    * @return string
    */
    public function getPaymentType()
    {
        switch ($this->paymentMethod) {
            case 'vrpay_cc':
            case 'vrpay_ccsaved':
            case 'vrpay_dc':
            case 'vrpay_dd':
            case 'vrpay_ddsaved':
            case 'vrpay_paydirekt':
                return $this->getPaymentTypeCard();
            case 'vrpay_easycredit':
            case 'vrpay_klarnapaylater':
            case 'vrpay_klarnasliceit':
                return 'PA';
            default:
                return 'DB';
        }
    }

    /**
    * provide payment transaction type
    * @return string
    */
    public function getTestMode()
    {
        if ($this->getServerMode() == "LIVE") {
            $testMode = false;
        } else {
            if ($this->paymentMethod == 'vrpay_giropay') {
                $testMode = 'INTERNAL';
            } else {
                $testMode = 'EXTERNAL';
            }
        }

        return $testMode;
    }

    /**
    * Returns if recurring payment method enabled
    *
    * @return boolean
    */
    public function isRecurringActive()
    {
        return $this->getConfig('BACKEND_CH_GENERAL_RECURRING') == 'Yes';
    }

    /**
    * Returns if payment method is recurring
    *
    * @return boolean
    */
    public function isRecurring()
    {
        switch ($this->paymentMethod) {
            case 'vrpay_ccsaved':
            case 'vrpay_ddsaved':
            case 'vrpay_paypalsaved':
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
    * provide recurring payment type
    *
    * @return string|boolean
    */
    public function getGroupRecurring()
    {
        switch ($this->paymentMethod) {
            case 'vrpay_ccsaved':
                return 'CC';
                break;
            case 'vrpay_ddsaved':
                return 'DD';
                break;
            case 'vrpay_paypalsaved':
                return 'VA';
                break;
            default:
                return false;
                break;
        }
    }

    /**
    * Returns if payment method use redirect page
    *
    * @return boolean
    */
    public function isRedirect()
    {
        switch ($this->paymentMethod) {
            case 'vrpay_paypal':
            case 'vrpay_paypalsaved':
            case 'vrpay_paydirekt':
            case 'vrpay_easycredit':
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Determines if server to server.
     *
     * @return     boolean  True if server to server, False otherwise.
     */
    public function isServerToServer()
    {
        switch ($this->paymentMethod) {
            case 'vrpay_easycredit':
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * validate payment risk score
     *
     * @param array $paymentResponse
     * @return boolean
     */
    public function isRiskPayment($paymentResponse)
    {
        if (isset($paymentResponse['risk']['score'])) {
            if ((int)$paymentResponse['risk']['score'] < 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * get error details from easycredit
     *
     * @param array $paymentResponse
     * @return array
     */
    public function getEasyCreditErrorDetail($paymentResponse)
    {
        $errorResults = $this->explodeByMultiDelimiter(
            array("{", "}"),
            $paymentResponse['resultDetails']['Error']
        );
        $errorResults = explode(", ", $errorResults[1]);
        foreach ($errorResults as $errorResult) {
            $errorResultValue = explode("=", $errorResult);
            $easyCreditErrorDetail[$errorResultValue[0]] = trim($errorResultValue[1], "'");
        }

        return $easyCreditErrorDetail;
    }

    /**
     * explode string with multi delimiter
     *
     * @param array $delimiters
     * @param string $string
     * @return array
     */
    public function explodeByMultiDelimiter($delimiters, $string)
    {
        $string = str_replace($delimiters, $delimiters[0], $string);
        $explodedString = explode($delimiters[0], $string);
        return $explodedString;
    }

    /**
    * Provide customer IP address
    *
    * @return string
    */
    public function getCustomerIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
    * Provide merchant email address
    *
    * @return string
    */
    public function getMerchantEmail()
    {
        if ($this->getConfig('BACKEND_CH_GENERAL_MERCHANTEMAIL')) {
            return $this->getConfig('BACKEND_CH_GENERAL_MERCHANTEMAIL');
        }
        $sql = "SELECT email from s_core_auth where roleID = '1' and active ='1'";
        $query = Shopware()->Db()->query($sql);
        $email = $query->fetchAll();
        return $email[0]['email'];
    }

    /**
    * Provide merchant email address
    *
    * @return array
    */
    public function getMerchantData()
    {
        return array(
            'merchant_email' => $this->getMerchantEmail(),
            'merchant_no' => $this->getConfig('BACKEND_CH_GENERAL_MERCHANTNO'),
            'shop_url' => $this->getConfig('BACKEND_CH_GENERAL_SHOPURL'),
            'merchant_location' => $this->getConfig('BACKEND_CH_GENERAL_MERCHANT_LOCATION')
        );
    }

    /**
    * Get version tracker parameters
    *
    * @return array
    */
    public function getVersionData()
    {
        return array_merge(
            $this->getGeneralVersionData(),
            $this->getCreditCardVersionData()
        );
    }

    /**
    * Get general version tracker parameters
    *
    * @return array
    */
    protected function getGeneralVersionData()
    {
        $merchant = $this->getMerchantData();

        $versionData['transaction_mode'] = $this->getServerMode();
        $versionData['ip_address'] = $_SERVER['SERVER_ADDR'];
        $versionData['shop_version'] = Shopware()->Config()->get( 'Version' );
        $versionData['plugin_version'] = $this->Plugin()->getVersion();
        $versionData['client'] = $this->client;
        $versionData['email'] = $merchant['merchant_email'];
        $versionData['merchant_id'] = $merchant['merchant_no'];
        $versionData['shop_system'] = $this->shop_system;
        $versionData['shop_url'] = $merchant['shop_url'];

        return $versionData;
    }

    /**
    * Get credit card version tracker parameters
    *
    * @return array
    */
    protected function getCreditCardVersionData()
    {
        $versionData = array();

        if ($this->paymentMethod == 'vrpay_cc' || $this->paymentMethod == 'vrpay_ccsaved') {
            $versionData['merchant_location'] = $this->getConfig('BACKEND_CH_GENERAL_MERCHANT_LOCATION');
        }

        return $versionData;
    }

    /**
    * Provide account type of payment type
    *
    * @return string|boolean
    */
    public function getAccountType()
    {
        switch ($this->paymentMethod) {
            case 'vrpay_ccsaved':
                return 'card';
            case 'vrpay_ddsaved':
                return 'bankAccount';
            case 'vrpay_paypalsaved':
                return 'virtualAccount';
            default:
                return false;
        }
    }

    /**
    * provide cards account data
    *
    * @param array $resultJson
    * @return array
    */
    public function getAccount($resultJson)
    {
        $account = $resultJson[$this->getAccountType()];
        switch ($this->paymentMethod) {
            case 'vrpay_ddsaved':
                $account['last4Digits'] = substr($account['iban'], -4);
                break;
            case 'vrpay_paypalsaved':
                $account['email']       = $account['accountId'];
                break;
        }
        $account['holder'] = isset($account['holder']) ? $account['holder'] : '';
        $account['email'] = isset($account['email']) ? $account['email'] : '';
        $account['last4Digits'] = isset($account['last4Digits']) ? $account['last4Digits'] : '';
        $account['expiryMonth'] = isset($account['expiryMonth']) ? $account['expiryMonth'] : '';
        $account['expiryYear'] = isset($account['expiryYear']) ? $account['expiryYear'] : '';

        return $account;
    }

    /**
    * Returns if cards use as a default payment
    *
    * @param string $userId
    * @return boolean
    */
    public function checkDefault($userId)
    {
        $credentials = $this->getcredentials();
        $sql = "SELECT * from payment_vrpayecommerce_recurring where cust_id='".(int)$userId."' and payment_group='".
               $this->getGroupRecurring()."' and server_mode = '".$credentials['server_mode']."' and channel_id = '".
               $credentials['channel_id']."'";

        $query = Shopware()->Db()->query($sql);
        $data = $query->fetchAll();
        if (count($data) > 0) {
            return 0;
        }
        return 1;
    }

    /**
    * Returns if cards never registered before
    *
    * @param string $userId
    * @param string $registrationId
    * @return boolean
    */
    public function isRegistered($userId, $registrationId)
    {
        $sql = "SELECT ref_id from payment_vrpayecommerce_recurring where ref_id='".$registrationId."' and cust_id='".
               $userId."' and payment_group='".$this->getGroupRecurring()."'";

        $query = Shopware()->Db()->query($sql);
        $data = $query->fetchAll();
        if ($data) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Returns if cards never registered before based on the registration Id
    *
    * @param string $registrationId
    * @return boolean
    */
    public function isRegisteredByRegistrationId($registrationId)
    {
        $sql = "SELECT ref_id from payment_vrpayecommerce_recurring where ref_id='".$registrationId."'
               and payment_group='".$this->getGroupRecurring()."'";

        $query = Shopware()->Db()->query($sql);
        $data = $query->fetchAll();
        if ($data) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the refund parameters.
     *
     * @param      array $paymentResponse  The payment response
     *
     * @return     array   The refund parameters.
     */
    public function getRefundParameters($paymentResponse)
    {
        $refundParameters = array();
        $refundParameters = $this->getCredentials();

        if ($this->isMultiChannel()) {
            $refundParameters['channel_id'] = $refundParameters['channel_id_moto'];
        }

        $refundParameters['amount'] = $paymentResponse['amount'];
        $refundParameters['currency']= $paymentResponse['currency'];
        $refundParameters['test_mode'] = $this->getTestMode();

        return $refundParameters;
    }

    /**
     * Do refund by the payment response
     *
     * @param      array  $paymentResponse  The payment response
     */
    public function refundByPaymentResponse($paymentResponse)
    {
        $referenceId = $paymentResponse['id'];
        $refundParameters = $this->getRefundParameters($paymentResponse);

        if ($paymentResponse['paymentType'] == 'PA') {
            $refundParameters['payment_type'] = "RV";
        } else {
            $refundParameters['payment_type'] = "RF";
        }

        $response = $this->backOfficeOperation($referenceId, $refundParameters);
    }

    /**
     * deregistration a payment account
     *
     * @param      string  $referenceId
     */
    public function deRegistrationPaymentAccount($referenceId)
    {
        $deRegistrationParameters = $this->getCredentials();
        $deRegistrationParameters['test_mode'] = $this->getTestMode();

        $this->deleteRegistration($referenceId, $deRegistrationParameters);
    }

    /**
    * provide recurring reference id
    *
    * @param string $id
    * @param string $userId
    * @return string
    */
    public function getReferenceId($id, $userId)
    {
        $sql = "SELECT ref_id from payment_vrpayecommerce_recurring where id = ? and cust_id = ? and payment_group = ?";
        $query = Shopware()->Db()->query($sql, array($id, $userId, $this->getGroupRecurring()));
        $data = $query->fetchAll();
        return $data[0]['ref_id'];
    }

    /**
    * Get total order from database
    *
    * @return string
    */
    public function getOrderCount($user)
    {
        $userId = $user['additional']['user']['id'];
        $sql = "SELECT COUNT(ordernumber) as order_count from s_order where userID = ? AND ordernumber != '0'";
        $query = Shopware()->Db()->query($sql, $userId);
        $data = $query->fetchAll();

        if ($data) {
            return $data[0]['order_count'];
        }
        return 0;
    }


    public function isCustomerLogin($user)
    {
        if ($user['additional']['user']['accountmode'] == "0") {
            return true;
        }
        return false;
    }

    public function getRiskKundenStatus($user)
    {
        if ($this->getOrderCount($user) > 0) {
            return 'BESTANDSKUNDE';
        }
        return 'NEUKUNDE';
    }

    public function getCustomerCreatedDate($user)
    {
        if (!$this->isCustomerLogin($user)) {
            return date('Y-m-d');
        }

        if (isset($user['additional']['user']['firstlogin'])) {
            return date('Y-m-d', strtotime($user['additional']['user']['firstlogin']));
        }
    }

    /**
    * provide all cards data that already registered by recurring method
    *
    * @param string $userId
    * @return array
    */
    public function getRegistrations($userId)
    {
        $credentials = $this->getcredentials();
        $sql = "SELECT * from payment_vrpayecommerce_recurring where cust_id = ? ".
               "and payment_group = ? and server_mode = ? and channel_id = ?";

        $query = Shopware()->Db()->query($sql, array(
            $userId,
            $this->getGroupRecurring(),
            $credentials['server_mode'],
            $credentials['channel_id']));
        $data = $query->fetchAll();
        return $data;
    }

    /**
    * change default recurring payment method
    *
    * @param string $id
    */
    public function updateDefaultRegistration($id)
    {
        $sql = "UPDATE payment_vrpayecommerce_recurring SET payment_default = '0'".
               " WHERE payment_default = '1' AND payment_group = ?";

        Shopware()->Db()->query($sql, array($this->getGroupRecurring()));
        $sql = "UPDATE payment_vrpayecommerce_recurring SET payment_default = '1' WHERE id = ? AND payment_group = ?";
        Shopware()->Db()->query($sql, array($id, $this->getGroupRecurring()));
    }

    /**
    * registering new payment account
    *
    * @param string $userId
    * @param string $registrationId
    * @param array $resultJson
    */
    public function insertRegistration($userId, $registrationId, $resultJson)
    {
        $credentials = $this->getcredentials();
        $isRegistered = $this->isRegistered($userId, $registrationId);
        if (!$isRegistered) {
            $default = $this->checkDefault($userId);
            $account = $this->getAccount($resultJson);
            $sql = "INSERT INTO payment_vrpayecommerce_recurring (cust_id, payment_group, brand, holder, email,".
                   " last4digits, expiry_month, expiry_year, server_mode, channel_id, ref_id, payment_default)".
                   " VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            Shopware()->Db()->query($sql, array(
                $userId,
                $this->getGroupRecurring(),
                $resultJson['paymentBrand'],
                $account['holder'],
                $account['email'],
                $account['last4Digits'],
                $account['expiryMonth'],
                $account['expiryYear'],
                $credentials['server_mode'],
                $credentials['channel_id'],
                $registrationId,
                $default
                ));
        }
    }

    /**
    * change payment account that already registered
    *
    * @param string $id
    * @param string $userId
    * @param string $registrationId
    * @param array $resultJson
    */
    public function updateRegistration($id, $userId, $registrationId, $resultJson)
    {
        $credentials = $this->getcredentials();
        $account = $this->getAccount($resultJson);
        $sql = "UPDATE payment_vrpayecommerce_recurring SET cust_id = ?, payment_group = ?, brand = ?, ".
               "holder = ?, email = ?, last4digits = ?, expiry_month = ?, expiry_year = ?, server_mode = ?,".
               " channel_id = ?, ref_id = ? WHERE id = ? ";

        Shopware()->Db()->query($sql, array(
            $userId,
            $this->getGroupRecurring(),
            $resultJson['paymentBrand'],
            $account['holder'],
            $account['email'],
            $account['last4Digits'],
            $account['expiryMonth'],
            $account['expiryYear'],
            $credentials['server_mode'],
            $credentials['channel_id'],
            $registrationId,
            $id
            ));
    }

    /**
    * delete payment account that already registered on database
    *
    * @param string $id
    * @param string $userId
    */
    public function deleteRecurring($id, $userId)
    {
        $sql = "DELETE FROM payment_vrpayecommerce_recurring where id = ? and cust_id = ? and payment_group = ?";
        Shopware()->Db()->query($sql, array($id, $userId, $this->getGroupRecurring()));
    }

    /**
    * get customer sex
    *
    * @param string $salutation
    * @param string $resultJson
    */
    public function getCustomerSex($salutation)
    {
        if ($salutation == 'mr') {
            return "M";
        } else {
            return "F";
        }
    }

    /**
    * provide checkout result from payment gateway for recurring payment
    *
    * @param array $user (id, email, first name, last name, billing street,
    *              billing city, billing zipcode, billing countryiso)
    * @param string $id
    * @return string
    */
    public function getRecurringChekoutResult($user, $id = false)
    {
        $userId = $user['additional']['user']['id'];
        $transactionData = $this->getCredentials();
        $transactionData['customer']['email'] = $user['additional']['user']['email'];
        $transactionData['customer']['first_name'] = $user['billingaddress']['firstname'];
        $transactionData['customer']['last_name'] = $user['billingaddress']['lastname'];
        $transactionData['billing']['street'] = $user['billingaddress']['street'];
        $transactionData['billing']['city'] = $user['billingaddress']['city'];
        $transactionData['billing']['zip'] = $user['billingaddress']['zipcode'];
        $transactionData['billing']['country_code'] = $user['additional']['country']['countryiso'];

        $transactionData['amount'] = $this->getRegisterAmount();
        $transactionData['currency'] = Shopware()->Shop()->getCurrency()->getCurrency();
        $transactionData['customer_ip'] = $this->getCustomerIp();
        $transactionData = array_merge_recursive(
            $transactionData,
            $this->getCCSavedParameters($transactionData['amount'], $transactionData['currency'])
        );

        if ($this->getGroupRecurring() != 'VA') {
            $transactionData['payment_type'] = $this->getPaymentType();
        }

        $transactionData['payment_recurring'] = 'INITIAL';
        $transactionData['payment_registration'] = 'true';
        $transactionData['test_mode'] = $this->getTestMode();
        // change payment
        if ($id) {
            $transactionData['transaction_id'] = $this->getReferenceId($id, $userId);
        } else {
            $transactionData['transaction_id'] = $userId;
        }

        $ChekoutResult = VrpayecommercePaymentCore::getChekoutResult($transactionData);

        return $ChekoutResult;
    }

    /**
    * get amount data of discount
    *
    * @param string $customerGroup
    * @return string|boolean
    */
    public function getDiscountByCustomerGroup($customerGroup)
    {
        $sql = "SELECT discount from s_core_customergroups where groupkey = '".$customerGroup."' and mode = '1'";
        $query = Shopware()->Db()->query($sql);
        $row = $query->fetch();
        if ($row) {
            return $row['discount'];
        }
        return false;
    }

    /**
    * provide items data that already insert to cart
    *
    * @param array $content
    * @param array $user
    * @return array
    */
    public function getCartItems($content, $user)
    {
        $cartItems = array();
        foreach ($content as $key => $item) {
            $cartItems[$key]['merchant_item_id'] = $item['articleID'];
            $customerGroup = $user['additional']['user']['customergroup'];
            $discount = $this->getDiscountByCustomerGroup($customerGroup);
            if ($discount) {
                $cartItems[$key]['discount'] = $discount;
            }
            $cartItems[$key]['quantity'] = $item['quantity'];
            $cartItems[$key]['name'] = $item['articlename'];
            if (isset($item['amountWithTax'])) {
                $cartItems[$key]['price'] = $item['amountWithTax'];
            } else {
                $cartItems[$key]['price'] = $item['amount'];
            }
            $cartItems[$key]['tax'] = $item['tax_rate'];
        }

        return $cartItems;
    }

    /**
    * provide params that needed for klarna payment
    *
    * @param array $content
    * @param array $user
    * @return array
    */
    public function getKlarnaParameters($content, $user)
    {
        $transactionData = array();
        if ($this->paymentMethod == 'vrpay_klarnapaylater' || $this->paymentMethod == 'vrpay_klarnasliceit') {
            $transactionData['customer']['sex'] = $this->getCustomerSex($user['billingaddress']['salutation']);
            if (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '<')) {
                $transactionData['customer']['birthdate'] = $user['billingaddress']['birthday'];
            } elseif (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '>=')) {
                $transactionData['customer']['birthdate'] = $user['additional']['user']['birthday'];
            }
            $transactionData['customer']['phone'] = $user['billingaddress']['phone'];
            $transactionData['cartItems'] = $this->getCartItems($content, $user);
            $transactionData['customParameters']['KLARNA_CART_ITEM1_FLAGS'] = 32;

            if ($this->paymentMethod == 'vrpay_klarnasliceit') {
                $transactionData['customParameters']['KLARNA_PCLASS_FLAG'] = $this->getKlarnaPclass();
            }
        }

        return $transactionData;
    }

    /**
    * provide params that needed for easycredit payment
    *
    * @return array
    */
    public function getEasycreditParameters($content, $shopperResultUrl, $user)
    {
        $transactionData = array();
        if ($this->paymentMethod == 'vrpay_easycredit') {
            $transactionData['customer']['sex'] = $this->getCustomerSex($user['billingaddress']['salutation']);
            $transactionData['customer']['phone'] = $user['billingaddress']['phone'];
            $transactionData['cartItems'] = $this->getCartItems($content, $user);
            $transactionData['customParameters']['RISK_ANZAHLBESTELLUNGEN'] = $this->getOrderCount($user);
            $transactionData['customParameters']['RISK_BESTELLUNGERFOLGTUEBERLOGIN'] =
                                                                    $this->isCustomerLogin($user) ? 'true' : 'false';
            $transactionData['customParameters']['RISK_KUNDENSTATUS'] = $this->getRiskKundenStatus($user);
            $transactionData['customParameters']['RISK_KUNDESEIT'] = $this->getCustomerCreatedDate($user);
            $transactionData['paymentBrand'] = $this->getBrand();
            $transactionData['shipping']['city'] = $user['shippingaddress']['city'];
            $transactionData['shipping']['country'] = $user['additional']['countryShipping']['countryiso'];
            $transactionData['shipping']['postcode'] = $user['shippingaddress']['zipcode'];
            $transactionData['shipping']['street1'] = $user['shippingaddress']['street'];
            $transactionData['shopperResultUrl'] = $shopperResultUrl;
        }
        return $transactionData;
    }

    /**
    * provide params that needed for credit card recurring payment
    *
    * @param array $amount
    * @param array $currency
    * @return array
    */
    public function getCCSavedParameters($amount, $currency)
    {
        $transactionData = array();
        if ($this->paymentMethod == 'vrpay_ccsaved') {
            $transactionData['3D']['amount'] = $amount;
            $transactionData['3D']['currency'] = $currency;
        }
        return $transactionData;
    }

    /**
    * provide params that needed for paydirect payment
    *
    * @return array
    */
    public function getPaydirektParameters()
    {
        $transactionData = array();
        if ($this->paymentMethod == 'vrpay_paydirekt') {
            $transactionData['customParameters']['PAYDIREKT_minimumAge'] = $this->getMinimumAge();
            $transactionData['customParameters']['PAYDIREKT_payment.isPartial'] = $this->getPaymentIsPartial();
        }
        return $transactionData;
    }

    /**
    * provide params that needed for recurring payment type
    *
    * @param array $user
    * @return array
    */
    public function getRegistrationParameters($user)
    {
        $transactionData = array();
        if ($this->isRecurring()) {
            $transactionData['payment_registration'] = 'true';

            if ($this->paymentMethod != 'vrpay_paypalsaved') {
                $userId = $user['additional']['user']['id'];
                $registrations = $this->getRegistrations($userId);
                if (!empty($registrations)) {
                    foreach ($registrations as $key => $value) {
                        $transactionData['registrations'][$key] = $value['ref_id'];
                    }
                }
            }
        }
        return $transactionData;
    }

    /**
    * provide checkout result from gateway for non recurring payment
    *
    * @param object $payment(getuser(email, first name, last name, billing street,
    *               billing city, billing zipcode, billing countryiso), getBasket(content))
    * @return string
    */
    public function getChekoutResult($payment, $shopperResultUrl)
    {
        $user = $payment->getUser();
        $basket = $payment->getBasket();
        $transactionData = $this->getCredentials();
        $transactionData['customer']['email'] = $user['additional']['user']['email'];
        $transactionData['customer']['first_name'] = $user['billingaddress']['firstname'];
        $transactionData['customer']['last_name'] = $user['billingaddress']['lastname'];
        $transactionData['billing']['street'] = $user['billingaddress']['street'];
        $transactionData['billing']['city'] = $user['billingaddress']['city'];
        $transactionData['billing']['zip'] = $user['billingaddress']['zipcode'];
        $transactionData['billing']['country_code'] = $user['additional']['country']['countryiso'];

        $transactionData['amount'] = $payment->getAmount();
        $transactionData['currency'] = $payment->getCurrencyShortName();
        $transactionData['customer_ip'] = $this->getCustomerIp();
        $transactionData['test_mode'] = $this->getTestMode();
        $transactionData['payment_type'] = $this->getPaymentType();
        Shopware()->Session()->vrpayecommerce['transactionid'] = date('dmy') . time();
        Shopware()->Session()->vrpayecommerce['userid'] = $user['additional']['user']['id'];
        $transactionData['transaction_id'] = Shopware()->Session()->vrpayecommerce['transactionid'];

        $transactionData = array_merge_recursive(
            $transactionData,
            $this->getCCSavedParameters($transactionData['amount'], $transactionData['currency']),
            $this->getEasycreditParameters($basket['content'], $shopperResultUrl, $user),
            $this->getKlarnaParameters($basket['content'], $user),
            $this->getPaydirektParameters(),
            $this->getRegistrationParameters($user)
        );

        if ($this->paymentMethod == 'vrpay_paypalsaved') {
            unset($transactionData['payment_type']);
        }

        Shopware()->Session()->vrpayecommerce['transaction_data'] = $transactionData;
        Shopware()->Session()->vrpayecommerce['payment_method'] = $this->paymentMethod;

        if ($this->paymentMethod == 'vrpay_easycredit') {
            $response = VrpayecommercePaymentCore::initializeServerToServerPayment($transactionData);
        } else {
            $response = VrpayecommercePaymentCore::getChekoutResult($transactionData);
        }

        return $response;
    }

    /**
    * return true if doesn't find error in payment widget content
    *
    * @param $paymentWidgetUrl string
    *
    * @return array
    */
    public function isPaymentWidgetValid($paymentWidgetUrl)
    {
        $paymentWidgetContent = VrpayecommercePaymentCore::getPaymentWidgetContent(
            $paymentWidgetUrl,
            $this->getServerMode()
        );

        if ($paymentWidgetContent['isValid']) {
            if (strpos($paymentWidgetContent['response'], 'errorDetail') !== false) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }


    /**
    * send data to version tracker API
    *
    * @return array
    */
    public function sendVersionTracker()
    {
        return VersionTracker::sendVersionTracker($this->getVersionData());
    }

    /**
    * provide status of payment
    *
    * @param string $checkoutId
    * @return array
    */
    public function getPaymentStatus($checkoutId, $isServerToServer = false)
    {
        $transactionData = $this->getCredentials();
        $resultJson = VrpayecommercePaymentCore::getPaymentStatus($checkoutId, $transactionData, $isServerToServer);
        return $resultJson;
    }

    /**
    * paying the items use payment account that already registered
    *
    * @param string $referenceId
    * @param string $transactionData
    * @return array
    */
    public function useRegistration($referenceId, $transactionData)
    {
        return VrpayecommercePaymentCore::useRegistration($referenceId, $transactionData);
    }

    /**
    * Back Office Operation : Capture, Refund, Reversal
    *
    * @param string $referenceId
    * @param string $transactionData
    * @return array
    */
    public function backOfficeOperation($referenceId, $transactionData)
    {
        return VrpayecommercePaymentCore::backOfficeOperation($referenceId, $transactionData);
    }

    /**
    * delete payment account that already registered on API
    *
    * @param string $referenceId
    * @param string $transactionData
    * @return array
    */
    public function deleteRegistration($referenceId, $transactionData)
    {
        return VrpayecommercePaymentCore::deleteRegistration($referenceId, $transactionData);
    }

    /**
    * change money format
    *
    * @param string $number
    * @return string
    */
    public function setNumberFormat($number)
    {
        return VrpayecommercePaymentCore::setNumberFormat($number);
    }

    /**
    * Execute payment process
    *
    * @param array $data
    * @param string $action
    * @param string $returnCode
    * @return string
    */
    public function executePayment($data, $action, &$returnCode)
    {
        $this->setPaymentMethod($data['payment']);
        $referenceId = $data['refId'];

        $transactionData = $this->getCredentials();
        if ($this->isMultiChannel()) {
            $transactionData['channel_id'] = $transactionData['channel_id_moto'];
        }
        $transactionData['currency'] = $data['currency'];
        $transactionData['amount'] = $data['amount'];

        $transactionData['test_mode'] = $this->getTestMode();
        $transactionData['payment_type'] = $action;

        $response = VrpayecommercePaymentCore::backOfficeOperation($referenceId, $transactionData);

        $returnCode = $response['response']["result"]["code"];
        $transactionResult = VrpayecommercePaymentCore::getTransactionResult($returnCode);

        return $transactionResult;
    }

    /**
    * update order status when current payment status from gateway is
    * PA(preauthorization payment) or DB(payment accepted)
    *
    * @param array $data
    * @param string $returnCode
    * @return string
    */
    public function updateStatus($data, &$returnCode)
    {
        $this->setPaymentMethod($data['payment']);
        $referenceId = $data['refId'];
        $transactionData = $this->getCredentials();
        $transactionData['test_mode'] = $this->getTestMode();

        $result = VRpayecommercePaymentCore::getCurrentPaymentStatus($referenceId, $transactionData);
        $returnCode = $result['response']['result']['code'];
        $transactionResult = VRpayecommercePaymentCore::getTransactionResult($returnCode);

        if ($transactionResult == 'ACK') {
            $paymentType = $result['response']['paymentType'];

            if ($paymentType == 'PA') {
                if ($this->isAboveShopwareVersion52()) {
                    $_POST['cleared'] = Shopware_Controllers_Frontend_PaymentProcessorCsrf::VRPAY_PREAUTHORIZATION;
                } else {
                    $_POST['cleared'] = Shopware_Controllers_Frontend_PaymentProcessor::VRPAY_PREAUTHORIZATION;
                }
            } elseif ($paymentType == 'DB') {
                if ($this->isAboveShopwareVersion52()) {
                    $_POST['cleared'] = Shopware_Controllers_Frontend_PaymentProcessorCsrf::VRPAY_PAYMENTACCEPTED;
                } else {
                    $_POST['cleared'] = Shopware_Controllers_Frontend_PaymentProcessor::VRPAY_PAYMENTACCEPTED;
                }
            }
        }

        return $transactionResult;
    }

    /**
    * Provide random number
    *
    * @param int $length
    * @return string
    */
    private function randomNumber($length)
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }

    /**
    * Provide config value
    *
    * @param string $identifier
    * @return string
    */
    public function getConfig($identifier)
    {
        return $this->Plugin()->Config()->get($identifier);
    }

    /**
    * initial Shopware_Plugins_Frontend_Vrpayecommerce_Bootstrap class
    *
    * @return object
    */
    public function Plugin()
    {
        return Shopware()->Plugins()->Frontend()->Vrpayecommerce();
    }

    /**
    * provide value of frontend error message
    *
    * @return string
    */
    public function getErrorIdentifier($returnCode)
    {
        return VrpayecommercePaymentCore::getErrorIdentifier($returnCode);
    }

    /**
    * provide transaction result
    *
    * @return string
    */
    public function getTransactionResult($returnCode)
    {
        return VrpayecommercePaymentCore::getTransactionResult($returnCode);
    }

    /**
    * provide value of backend error message
    *
    * @param string $returnCode
    * @return string
    */
    public function getErrorIdentifierBackend($returnCode)
    {
        return VrpayecommercePaymentCore::getErrorIdentifierBackend($returnCode);
    }

    /**
    * Returns if success change payment status
    *
    * @param string $returnCode
    * @return boolean
    */
    public function isSuccessReview($returnCode)
    {
        return VrpayecommercePaymentCore::isSuccessReview($returnCode);
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
}
