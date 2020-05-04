<?php

/**
 * Vrpayecommerce controller
 */

class Shopware_Controllers_Frontend_PaymentProcessor extends Shopware_Controllers_Frontend_Payment
{
    // Payment status
    const VRPAY_PREAUTHORIZATION = 1751;
    const VRPAY_PAYMENTACCEPTED = 1752;
    const VRPAY_REFUND = 1753;
    const VRPAY_INREVIEW = 1750;

    /**
     * Index action method.
     *
     * Forwards to correct the action.
     */
    public function indexAction()
    {
        $this->forward('process');
    }

    /**
     * Returns the payment plugin config data.
     *
     * @return Shopware_Plugins_Frontend_Vrpayecommerce_Bootstrap
     */
    public function Plugin()
    {
        return Shopware()->Plugins()->Frontend()->Vrpayecommerce();
    }

    /**
    * @param string $identifier
    * @return string
    */
    public function getConfig($identifier)
    {
        return $this->Plugin()->Config()->get($identifier);
    }

    /**
    * @param string $name
    * @param string $localeId
    * @param string $namespace
    * @param int $shopId
    * @return string
    */
    public function getSnippet($name, $localeId, $namespace = 'frontend/payment_processor/result', $shopId = 1)
    {
        $sql = 'SELECT `value` FROM `s_core_snippets` WHERE `namespace` = ? AND'.
               ' `shopID` = ? AND `localeID` = ? AND `name` = ?';
        $data = current(Shopware()->Db()->fetchAll($sql, array($namespace, $shopId, $localeId, $name)));
        return $data['value'];
    }

    /**
    * do redirection to error page with message from errorIdentifier
    *
    * @param string $errorIdentifier
    * @param boolean $redirect
    * @param boolean $errorIdentifier
    */
    private function redirectError($errorIdentifier, $redirect = false, $useTranslation = true)
    {
        if ($redirect) {
             $this->redirect(array(
            'controller' => 'payment_processor',
            'action' => 'result',
             strtolower($errorIdentifier) => 1
             ));
        } else {
            $localeId = ($this->getShopLocale() == 'de') ? 1 : 2;
            if ($useTranslation) {
                $errorMessage = $this->getSnippet($errorIdentifier, $localeId);
            } else {
                $errorMessage = $errorIdentifier;
            }
            $this->View()->errorMessage = $errorMessage;
        }
    }

    /**
    * @param array $resultJson
    */
    private function redirectSuccess($resultJson)
    {
        $paymentStatus = $this->determinePaymentStatus($resultJson);
        $internalUniqueId = $this->createPaymentUniqueId();
        $this->saveOrder($resultJson['id'], $internalUniqueId, $paymentStatus);

        $this->setOrderInformation($resultJson);

        $this->redirect(array(
            'controller' => 'checkout',
            'action' => 'finish',
            'sUniqueID' => $internalUniqueId,
            'sAGB' => 'true',
            'sMessage' => 'Success'
        ));
    }

    private function redirectGeneralError()
    {
        $this->redirect(array(
            'controller' => 'payment_processor',
            'action' => 'result',
            'error_general_redirect' => 1
        ));
    }

    private function redirectSessionTimeoutError()
    {
        $this->redirect(array(
            'controller' => 'payment_processor',
            'action' => 'result',
            'error_message_session_timeout' => 1
        ));
    }

    /**
    * @param string $registrationId
    * @param string $paymentMethod
    * @param object $client
    */
    public function getPaypalSavedResponse($registrationId, $client)
    {
        if (!$this->getUser()) {
            $this->redirectError('ERROR_MESSAGE_SESSION_TIMEOUT_WHITOUT_REFUND');
        } else {
            $transactionData = Shopware()->Session()->vrpayecommerce['transaction_data'];
            $transactionData['payment_recurring'] = 'REPEATED';
            $transactionData['payment_type'] = 'DB';

            $debitResponse = $client->useRegistration($registrationId, $transactionData);

            if (!$debitResponse['isValid']) {
                $this->redirectError($debitResponse['response']);
            } else {
                $returnCode = $debitResponse['response']["result"]["code"];
                $returnMessage = $client->getErrorIdentifier($returnCode);
                $transactionResult = $client->getTransactionResult($returnCode);

                if ($transactionResult == "ACK") {
                    $this->redirectSuccess($debitResponse['response']);
                } elseif ($transactionResult == "NOK") {
                    $this->redirectError($returnMessage);
                } else {
                    $this->redirectError('ERROR_UNKNOWN');
                }
            }
        }
    }

    /**
    * @param array $resultJson
    * @param string $userId
    * @param object $client
    */
    public function processPaypalSaved(&$resultJson, $userId, $client)
    {
        $paymentMethod = $this->Request()->getParam('pm');
        $registrationId = $resultJson['id'];
        $credential = $client->getCredentials();
        $transactionData = $credential;
        $transactionData['amount'] = Shopware()->Session()->vrpayecommerce['transaction_data']['amount'];
        $transactionData['currency'] = Shopware()->Session()->vrpayecommerce['transaction_data']['currency'];
        $transactionData['transaction_id'] = $resultJson['merchantTransactionId'];
        $transactionData['payment_recurring'] = 'INITIAL';
        $transactionData['test_mode'] = $client->getTestMode();
        $transactionData['payment_type'] = 'DB';

        $debitResponse = $client->useRegistration($registrationId, $transactionData);
        if ($debitResponse['isValid']) {
            $returnCode = $debitResponse['response']["result"]["code"];
            $returnMessage = $client->getErrorIdentifier($returnCode);
            $transactionResult = $client->getTransactionResult($returnCode);

            if ($transactionResult == "ACK") {
                Shopware()->Session()->vrpayecommerce['resultJson']['id'] = $debitResponse['response']['id'];
                $client->insertRegistration($userId, $registrationId, $resultJson);
                $this->redirectSuccess($debitResponse['response']);
            } elseif ($transactionResult == "NOK") {
                $this->redirectError($returnMessage);
            } else {
                $this->redirectError('ERROR_UNKNOWN');
            }
        } else {
            $this->redirectError($debitResponse['response']);
        }
    }

    /**
    * @param object $client
    * @param array $resultJson
    * @param string $paymentMethod
    */
    public function processPaymentSuccess($client, $resultJson, $paymentMethod)
    {
        if ($client->isRecurring()) {
            $registrationId = $resultJson['registrationId'];
            $user = $this->getUser();
            $userId = $user['additional']['user']['id'];
            
            if (empty($userId)) {
                $userId = Shopware()->Session()->vrpayecommerce['userid'];
            }

            if ($paymentMethod == 'vrpay_paypalsaved') {
                $this->processPaypalSaved($resultJson, $userId, $client);
            } else {
                $client->insertRegistration($userId, $registrationId, $resultJson);
                $this->redirectSuccess($resultJson);
            }
        } else {
            $this->redirectSuccess($resultJson);
        }
    }

    public function resultAction()
    {
        $errorGeneralRedirect = $this->Request()->getParam('error_general_redirect');
        $errorCaptureBackend = $this->Request()->getParam('error_capture_backend');
        $errorMessageSessionTimeout = $this->Request()->getParam('error_message_session_timeout');
        $errorGeneralDeclinedRisk = $this->Request()->getParam('error_general_declined_risk');
        
        if ($errorCaptureBackend) {
            $this->redirectError('ERROR_CAPTURE_BACKEND');
        } elseif ($errorGeneralRedirect) {
            $this->redirectError('ERROR_GENERAL_REDIRECT');
        } elseif ($errorMessageSessionTimeout) {
            $this->redirectError('ERROR_MESSAGE_SESSION_TIMEOUT_WHITOUT_REFUND');
        } elseif ($errorGeneralDeclinedRisk) {
            $this->redirectError('ERROR_GENERAL_DECLINED_RISK');
        } else {
            $checkoutId = $this->Request()->getParam('id');

            $paymentMethod = Shopware()->Session()->vrpayecommerce['payment_method'];
            if ($this->Request()->getParam('pm')) {
                $paymentMethod =  $this->Request()->getParam('pm');
            }

            $registrationId = $this->Request()->getParam('registrationId');
            $client = $this->Plugin()->Client();
            $client->setPaymentMethod($paymentMethod);

            $isServerToServer = $client->isServerToServer();

            if ($this->getConfig("BACKEND_CH_GENERAL_VERSION_TRACKER") == 'True') {
                $client->sendVersionTracker();
            }

            if (!empty($registrationId) && $paymentMethod == 'vrpay_paypalsaved') {
                $this->getPaypalSavedResponse($registrationId, $client);
            } else {
                $this->validatePayment($checkoutId, $paymentMethod, $client, $isServerToServer);
            }
        }
    }


    /**
    * @param string $checkoutId
    * @param string $paymentMethod
    * @param object $client
    */
    private function validatePayment($checkoutId, $paymentMethod, $client, $isServerToServer)
    {
        $paymentResponse = $client->getPaymentStatus($checkoutId, $isServerToServer);

        Shopware()->Session()->vrpayecommerce['payment_response'] = $paymentResponse['response'];

        if (!$paymentResponse['isValid']) {
            $this->redirectError($paymentResponse['response']);
        } else {
            if ($isServerToServer) {
                $this->processServerToServer($client, $paymentMethod);
            } else {
                $this->processNotServerToServer($client, $paymentMethod);
            }
        }
    }

    public function processNotServerToServer($client, $paymentMethod)
    {
        $paymentResponse = Shopware()->Session()->vrpayecommerce['payment_response'];
        $returnCode = $paymentResponse["result"]["code"];
        $returnMessage = $client->getErrorIdentifier($returnCode);
        $transactionResult = $client->getTransactionResult($returnCode);
        $user = $this->getUser();
        $userId = $user['additional']['user']['id'];

        if (empty($userId)) {
            Shopware()->PluginLogger()->info(
                "customer session is expired"
            );
            if ($paymentMethod == 'vrpay_paypalsaved') {
                Shopware()->PluginLogger()->info(
                    "start deregister paypal account"
                );
                $client->deRegistrationPaymentAccount($paymentResponse['id']);
                Shopware()->PluginLogger()->info(
                    "end deregister paypal account"
                );
                $this->redirectError('ERROR_MESSAGE_SESSION_TIMEOUT_WHITOUT_REFUND');
            } else {
                if ($transactionResult == "ACK") {
                    Shopware()->PluginLogger()->info(
                        "start refund payment"
                    );
                    
                    $client->refundByPaymentResponse($paymentResponse);

                    if ($client->isRecurring()) {
                        Shopware()->PluginLogger()->info(
                            "start deregister recurring account"
                        );
                        $registrationId = $paymentResponse['registrationId'];
                        $isRegisteredByRegistrationId = $client->isRegisteredByRegistrationId($registrationId);

                        if (!$isRegisteredByRegistrationId) {
                            $client->deRegistrationPaymentAccount($paymentResponse['registrationId']);
                        }
                        Shopware()->PluginLogger()->info(
                            "end deregister recurring account"
                        );
                    }
                    Shopware()->PluginLogger()->info(
                        "end refund payment"
                    );
                } else {
                    $this->redirectError('ERROR_MESSAGE_SESSION_TIMEOUT');
                }
            }
        } else {

            if ($transactionResult == "ACK") {
                $this->processPaymentSuccess($client, $paymentResponse, $paymentMethod);
            } elseif ($transactionResult == "NOK") {
                $this->redirectError($returnMessage);
            } else {
                $this->redirectError('ERROR_UNKNOWN');
            }
        }
    }

    public function processServerToServer($client, $paymentMethod)
    {
        $paymentResponse = Shopware()->Session()->vrpayecommerce['payment_response'];
        $returnCode = $paymentResponse["result"]["code"];
        $transactionResult = $client->getTransactionResult($returnCode);

        if (!$this->getUser()) {
            if ($transactionResult == "ACK") {
                $client->refundByPaymentResponse($paymentResponse);
            } else {
                $this->redirectError('ERROR_MESSAGE_SESSION_TIMEOUT');
            }
        } else {
            if ($transactionResult == "ACK") {
                $this->processPaymentConfirmation();
            } elseif ($transactionResult == "NOK") {
                if (isset($paymentResponse['resultDetails']['faultString'])) {
                    $returnMessage = $paymentResponse['resultDetails']['faultString'];
                } elseif ($paymentMethod == 'vrpay_easycredit') {
                    $returnMessage = $this->getEasyCreditErrorMessage($client, $paymentResponse);
                    return $this->redirectError($returnMessage, false, false);
                } else {
                    $returnMessage = $client->getErrorIdentifier($returnCode);
                }
                $this->redirectError($returnMessage);
            } else {
                $this->redirectError('ERROR_UNKNOWN');
            }
        }
    }

    /**
     * process payment confirmation
     *
     * @return void
     */
    public function processPaymentConfirmation()
    {
        $this->redirect(array(
            'controller' => 'payment_processor',
            'action' => 'confirmation'
        ));
    }

    public function confirmationAction()
    {
        if (Shopware()->Modules()->Basket()->sCountBasket() == 0) {
            $this->redirect(array(
                'controller' => 'checkout',
                'action' => 'confirm'
            ));
            return;
        }

        $user = $this->getUser();
        $basket = $this->getBasket();

        $paymentResponse = Shopware()->Session()->vrpayecommerce['payment_response'];

        $this->View()->paymentResponse = $paymentResponse;
        $this->View()->billingAddress = $user['billingaddress'];
        $this->View()->shippingAddress = $user['shippingaddress'];
        $this->View()->countryBillingAddress = $user['additional']['country']['countryname'];
        $this->View()->countryShippingAddress = $user['additional']['countryShipping']['countryname'];
        $this->View()->paymentMethod = $user['additional']['payment']['description'];
        $this->View()->paymentShortName = $this->getPaymentShortName();
        $this->View()->sBasket = $basket;
        $this->View()->sUserData = $user;
        $this->View()->sShippingcosts = $basket['sShippingcosts'];
        $this->View()->sAmountNet = $basket['AmountNetNumeric'];
        $this->View()->sAmount = $basket['sAmount'];
        $this->View()->sumOfInterest = $paymentResponse['resultDetails']['ratenplan.zinsen.anfallendeZinsen'];
        $this->View()->orderTotal = $paymentResponse['resultDetails']['ratenplan.gesamtsumme'];

        if (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '<')) {
            $this->View()->sAmountWithTax = $basket['AmountWithTax'];
        } elseif (version_compare(Shopware()->Config()->get( 'Version' ), '5.2.0', '>=')) {
            $this->View()->sAmountWithTax = $basket['sAmountWithTax'];
        }
    }

    public function captureAction()
    {
        $paymentResponse = Shopware()->Session()->vrpayecommerce['payment_response'];
        $client = $this->Plugin()->Client();

        $paymentMethod = $this->Request()->getParam('pm');

        $client->setPaymentMethod($paymentMethod);

        if (!$this->getUser()) {
            $this->redirectSessionTimeoutError();
        } else {
            $referenceId = $paymentResponse['id'];
            $transactionData = $client->getCredentials();
            $transactionData['currency'] = $paymentResponse['currency'];
            $transactionData['amount'] = $paymentResponse['amount'];
            $transactionData['payment_type'] = 'CP';
            $transactionData['test_mode'] = $client->getTestMode();
            $amount = $this->getBasket();
            $totalAmount = number_format(str_replace(',', '.', $amount['AmountNumeric']), 2, '.', '');
            $amountFromResponse = number_format($transactionData['amount'], 2, '.', '');

            if ($totalAmount != $amountFromResponse) {
                if ($paymentResponse == "success") {
                    $this->redirect(array(
                        'controller' => 'checkout',
                        'action' => 'finish',
                        'sUniqueID' => $this->createPaymentUniqueId(),
                        'sAGB' => 'true',
                        'sMessage' => 'Success'
                    ));
                } else {
                    $this->redirectError('ERROR_GENERAL_DECLINED_RISK', true);
                }
            } else {
                $captureResponse = $client->backOfficeOperation($referenceId, $transactionData);

                if (!$captureResponse['isValid']) {
                    $this->redirectError($captureResponse['response']);
                } else {
                    $returnCode = $captureResponse['response']["result"]["code"];
                    $transactionResult = $client->getTransactionResult($returnCode);
                    $returnMessage = $client->getErrorIdentifier($returnCode);
                    if ($transactionResult == "ACK") {
                        Shopware()->Session()->vrpayecommerce['payment_response'] = "success";
                        $this->processPaymentSuccess($client, $captureResponse['response'], $paymentMethod);
                    } elseif ($transactionResult == "NOK") {
                        if ($paymentMethod == "vrpay_easycredit") {
                            $this->redirectError($returnMessage, true);
                        } else {
                            $this->redirectError($returnMessage);
                        }
                    } else {
                        $this->redirectError('ERROR_UNKNOWN');
                    }
                }
            }
        }
    }

    /**
     * get error message from easycredit
     *
     * @param array $paymentResponse
     * @return string
     */
    public function getEasyCreditErrorMessage($client, $paymentResponse)
    {
        if ($client->isRiskPayment($paymentResponse) && isset($paymentResponse['resultDetails']['Error'])) {
            $easyCreditErrorDetail = $client->getEasyCreditErrorDetail($paymentResponse);
            if (isset($easyCreditErrorDetail['field']) && $easyCreditErrorDetail['field'] !== 'null') {
                return $easyCreditErrorDetail['field'] . ': ' . $easyCreditErrorDetail['renderedMessage'];
            } else {
                return $easyCreditErrorDetail['renderedMessage'];
            }
        } elseif (isset($paymentResponse['resultDetails']['decisionNOK'])) {
            return $paymentResponse['resultDetails']['decisionNOK'];
        } else {
            return $client->getErrorIdentifier($paymentResponse['result']['code']);
        }
    }

    /**
    * @param array $resultJson
    */
    private function setOrderInformation($resultJson)
    {
        $transactionData = Shopware()->Session()->vrpayecommerce['transaction_data'];

        $sql = "UPDATE s_order_attributes SET attribute4 = ? WHERE  orderID in
                    (select id from s_order where transactionID = ?)";
        Shopware()->Db()->query($sql, array($resultJson['merchantTransactionId'], $resultJson['id']));
    }

    /**
    * @param array $resultJson
    * @return int
    */
    private function determinePaymentStatus($resultJson)
    {
        $client = $this->Plugin()->Client();
        $successReview = $client->isSuccessReview($resultJson["result"]["code"]);

        if ($successReview) {
            return Shopware_Controllers_Frontend_PaymentProcessor::VRPAY_INREVIEW;
        } else {
            if ($resultJson['paymentType'] == "PA") {
                return Shopware_Controllers_Frontend_PaymentProcessor::VRPAY_PREAUTHORIZATION;
            }
            return Shopware_Controllers_Frontend_PaymentProcessor::VRPAY_PAYMENTACCEPTED;
        }
    }

    /**
    * @retun null
    */
    public function processAction()
    {
        if (Shopware()->Modules()->Basket()->sCountBasket() == 0) {
            $this->redirect(array(
                'controller' => 'checkout',
                'action' => 'confirm'
            ));
            return;
        }

        $paymentShortName = $this->getPaymentShortName();
        $user = $this->getUser();
        $userId = $user['additional']['user']['id'];
        $client = $this->Plugin()->Client();
        $client->setPaymentMethod($paymentShortName);

        $shopperResultUrl = $this->getShopperResultUrl();

        $chekoutResult = $client->getChekoutResult($this, $shopperResultUrl);

        if ($client->isServerToServer()) {
            if (!$chekoutResult['isValid']) {
                $sErrorFlag['payment'] = true;
                $sErrorMessages[] = Shopware()->Snippets()->getNamespace('frontend/checkout/error_messages')
                    ->get('ShippingPaymentSelectPayment', 'Please select a payment method');
                $this->View()->assign('sErrorFlag', $sErrorFlag);
                $this->View()->assign('sErrorMessages', $chekoutResult['response']);

                $location = array(
                                'controller' => 'checkout',
                                'action' => 'shippingPayment',
                                'error' => 'easycredit',
                            );
                $url = Shopware()->Router()->assemble($location);
                $this->redirect($url);
            } elseif (!isset($chekoutResult['response']['id']) ||
                !isset($chekoutResult['response']['redirect']['url']) ||
                empty($chekoutResult['response']['redirect']['url'])
                ) {
                $this->redirectError('ERROR_GENERAL_REDIRECT', true);
            }

            $this->View()->redirectUrl = $chekoutResult['response']['redirect']['url'];
            $this->View()->redirectParameters = $chekoutResult['response']['redirect']['parameters'];
            $this->View()->failedUrl = $this->getConfig('BACKEND_CH_GENERAL_SHOPURL').
                "/checkout/saveShippingPayment/sTarget/checkout/sTargetAction/index";
        } else {
            if (!$chekoutResult['isValid']) {
                $this->redirectError($chekoutResult['response'], true);
            } elseif (!isset($chekoutResult['response']['id'])) {
                $this->redirectError('ERROR_GENERAL_REDIRECT', true);
            } else {
                if (!$client->isPaymentWidgetValid($chekoutResult['widgetUrl'])) {
                    $this->redirectError('ERROR_GENERAL_REDIRECT', true);
                } else {
                    $this->View()->lang = $this->getShopLocale();
                    $this->View()->paymentMethod = $paymentShortName;
                    $this->View()->registrations = $client->getRegistrations($userId);
                    $this->View()->recurring = $client->isRecurring();
                    $this->View()->testMode = $client->getTestMode();
                    $this->View()->brand = $client->getBrand();
                    $this->View()->paymentWidgetUrl = $chekoutResult['widgetUrl'];
                    $this->View()->merchantLocation = $this->getConfig('BACKEND_CH_GENERAL_MERCHANT_LOCATION');
                }
            }
        }

        $this->View()->paymentFormPath = $this->getPaymentFormPath();
    }

    public function getShopperResultUrl()
    {
        $query = array(
            'controller' => 'payment_processor',
            'action' => 'result'
        );

        return Shopware()->Router()->assemble($query);
    }

    /**
    * @return string
    */
    private function getShopLocale()
    {
        return Shopware()->Shop()->getLocale()->getLocale() == "de_DE" ? "de" : "en";
    }

    /**
    * @return string
    */
    private function getPaymentFormPath()
    {
        switch ($this->getPaymentShortName()) {
            case 'vrpay_paypal':
            case 'vrpay_paydirekt':
                $template = 'redirect';
                break;
            case 'vrpay_easycredit':
                $template = 'response_redirect';
                break;
            case 'vrpay_paypalsaved':
                $template = 'paypalsaved';
                break;
            default:
                $template = 'cp';
                break;
        }
        return "frontend/payment_processor/form/".$template.".tpl";
    }
}
