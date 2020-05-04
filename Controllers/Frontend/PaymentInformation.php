<?php

/**
 * Vrpayecommerce controller of frontend payment information
 */

class Shopware_Controllers_Frontend_PaymentInformation extends Shopware_Controllers_Frontend_Account
{
    private $action;
    private $paymentMethod;
    private $recurringId;

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
    * Provide user data
    *
    * @return array
    */
    private function getUser()
    {
        return $this->admin->sGetUserData();
    }

    /**
    * Index action method.
    *
    * Forwards to correct the action.
    */
    public function indexAction()
    {
        $this->foward('information');
    }

    /**
    * Return error action
    *
    * @param string $errorIdentifier
    * @param boolean $mainPage
    */
    private function redirectError($errorIdentifier, $errorDefault = 'ERROR_UNKNOWN', $mainPage = false)
    {
        if ($errorIdentifier == 'ERROR_UNKNOWN') {
            $errorIdentifier = $errorDefault;
        }
        if ($this->paymentMethod == 'vrpay_paypalsaved' || $mainPage) {
            $this->redirect(array(
                'controller' => 'payment_information',
                'action' => 'information',
                'sError' => $errorIdentifier
            ));
        } else {
            $this->redirect(array(
                'controller' => 'payment_information',
                'action' => $this->action,
                'pm'    => $this->paymentMethod,
                'id'    => $this->recurringId,
                'sError' => $errorIdentifier
            ));
        }
    }

    /**
    * Return success action
    */
    private function redirectSuccess()
    {
        $this->redirect(array(
            'controller' => 'payment_information',
            'action' => 'information',
            'sSuccess' => $this->action
        ));
    }


    /**
    * Proccess save paypal account
    *
    * @param array $resultJson
    * @param object $client
    * @param array $transactionData
    * @param string $userId
    * @return boolean
    */
    public function processPaypalSavedRecurring($resultJson, $client, $transactionData, $userId)
    {
        $registrationId = $resultJson['id'];
        $transactionData['payment_type'] = 'DB';
        $response = $client->useRegistration($registrationId, $transactionData);
        if ($response['isValid']) {
            $returnCode = $response['response']["result"]["code"];
            $returnMessage = $client->getErrorIdentifier($returnCode);
            $resultDB = $client->getTransactionResult($returnCode);

            if ($resultDB == "ACK") {
                $referenceId = $response['response']['id'];
                $transactionData['payment_type'] = "RF";
                $response = $client->backOfficeOperation($referenceId, $transactionData);

                if ($this->action == 'register') {
                    $client->insertRegistration($userId, $registrationId, $resultJson);
                } else {
                    $client->updateRegistration($this->recurringId, $userId, $registrationId, $resultJson);
                }
            } elseif ($resultDB == "NOK") {
                $this->redirectError($returnMessage);
                return false;
            } else {
                $this->redirectError('ERROR_UNKNOWN');
                return false;
            }
            return true;
        } else {
            $this->redirectError($response['response']);
        }
    }

    /**
    * Provide transaction data
    *
    * @param object $client
    * @param string $userId
    * @param array $resultJson
    * @return array
    */
    public function getTransactionData($client, $userId, $resultJson)
    {
        $transactionData = array();
        $transactionData = $client->getCredentials();
        if ($client->isMultiChannel()) {
            $transactionData['channel_id'] = $transactionData['channel_id_moto'];
        }
        $transactionData['amount'] = $client->getRegisterAmount();
        $transactionData['currency'] = Shopware()->Shop()->getCurrency()->getCurrency();
        if ($this->action == 'register') {
            $transactionData['transaction_id'] = $userId;
        } else {
            $transactionData['transaction_id'] = $resultJson['merchantTransactionId'];
        }
        $transactionData['test_mode'] = $client->getTestMode();

        return $transactionData;
    }

    /**
    * Proccess save card account
    *
    * @param string $userId
    * @param array $resultJson
    * @param object $client
    * @param array $transactionData
    * @return boolean
    */
    public function processPaymentSavedRecurring($userId, $resultJson, $client, $transactionData)
    {
        $referenceId = $resultJson['id'];
        $registrationId = $resultJson['registrationId'];

        if ($client->getPaymentType() == 'PA') {
            $transactionData['payment_type'] = "CP";
            $response = $client->backOfficeOperation($referenceId, $transactionData);
            $returnCode = $response['response']["result"]["code"];
            $returnMessage = $client->getErrorIdentifier($returnCode);
            $resultCP = $client->getTransactionResult($returnCode);

            if ($resultCP == 'ACK') {
                $referenceId = $response['response']['id'];
            } elseif ($resultCP == 'NOK') {
                $this->redirectError($returnMessage);
                return false;
            } else {
                $this->redirectError('ERROR_UNKNOWN');
                return false;
            }
        }
        $transactionData['payment_type'] = "RF";
        $client->backOfficeOperation($referenceId, $transactionData);

        if ($this->action == 'register') {
            $client->insertRegistration($userId, $registrationId, $resultJson);
        } else {
            $client->updateRegistration($this->recurringId, $userId, $registrationId, $resultJson);
        }
        return true;
    }

    /**
    * Proccess save or remove card account
    *
    * @param array $resultJson
    * @param object $client
    */
    public function processPaymentSuccess($resultJson, $client)
    {
        $success = false;
        $user = $this->getUser();
        $userId = $user['additional']['user']['id'];

        $transactionData = $this->getTransactionData($client, $userId, $resultJson);

        if ($this->paymentMethod == 'vrpay_paypalsaved') {
            $success = $this->processPaypalSavedRecurring($resultJson, $client, $transactionData, $userId);
        } else {
            $success = $this->processPaymentSavedRecurring($userId, $resultJson, $client, $transactionData);
        }

        if ($success) {
            if ($this->action == 'change') {
                $referenceId = $resultJson['merchantTransactionId'];
                $client->deleteRegistration($referenceId, $transactionData);
            }
            $this->redirectSuccess();
        }
    }

    /**
    * Change or register payment account
    */
    public function resultAction()
    {
        $this->recurringId = $this->Request()->getParam('recurring_id');
        if ($this->recurringId) {
            $this->action = 'change';
        } else {
            $this->action = 'register';
        }

        $checkoutId = $this->Request()->getParam('id');
        $this->paymentMethod = $this->Request()->getParam('pm');
        $client = $this->Plugin()->Client();
        $client->setPaymentMethod($this->paymentMethod);

        $resultJson = $client->getPaymentStatus($checkoutId);

        if (!$resultJson['isValid']) {
            $this->redirectError($resultJson['response']);
        } else {
            $returnCode = $resultJson['response']["result"]["code"];
            $returnMessage = $client->getErrorIdentifier($returnCode);
            $transactionResult = $client->getTransactionResult($returnCode);

            if ($transactionResult == "ACK") {
                $this->processPaymentSuccess($resultJson['response'], $client);
            } elseif ($transactionResult == "NOK") {
                $this->redirectError($returnMessage);
            } else {
                $this->redirectError('ERROR_UNKNOWN');
            }
        }
    }


    public function informationAction()
    {
        $client = $this->Plugin()->Client();
        if (!$client->isRecurringActive()) {
            $this->redirect(array(
                'controller' => 'account',
                'action' => 'index'
            ));
            return;
        }

        $sSuccess = $this->Request()->getParam('sSuccess');
        if ($sSuccess) {
            $this->View()->sSuccess = $sSuccess;
        }

        $sError = $this->Request()->getParam('sError');
        if ($sError) {
            $this->View()->sError = $sError;
        }

        $user = $this->getUser();
        $userId = $user['additional']['user']['id'];

        $setDefault = $this->Request()->getParam('set_default');
        $id = $this->Request()->getParam('id');
        $paymentShortName = $this->Request()->getParam('selected_payment');
        $client->setPaymentMethod($paymentShortName);
        if ($setDefault) {
            $client->updateDefaultRegistration($id);
        }

        $isRecurringActive = $client->isRecurringActive();
        $isCardsSavedActive = $client->getConfig('BACKEND_CH_CCSAVED_ACTIVE');
        $isDDSavedActive = $client->getConfig('BACKEND_CH_DDSAVED_ACTIVE');
        $isPayPalSavedActive = $client->getConfig('BACKEND_CH_PAYPALSAVED_ACTIVE');

        $this->View()->isRecurringActive = $isRecurringActive;
        $this->View()->isCardsSavedActive = $isCardsSavedActive;
        $this->View()->isDDSavedActive = $isDDSavedActive;
        $this->View()->isPayPalSavedActive = $isPayPalSavedActive;

        if ($isRecurringActive) {
            if ($isCardsSavedActive) {
                $client->setPaymentMethod('vrpay_ccsaved');
                $this->View()->customerDataCC = $client->getRegistrations($userId);
            }
            if ($isDDSavedActive) {
                $client->setPaymentMethod('vrpay_ddsaved');
                $this->View()->customerDataDD = $client->getRegistrations($userId);
            }
            if ($isPayPalSavedActive) {
                $client->setPaymentMethod('vrpay_paypalsaved');
                $this->View()->customerDataPAYPAL = $client->getRegistrations($userId);
            }
        }
    }

    public function registerAction()
    {
        $client = $this->Plugin()->Client();
        if (!$client->isRecurringActive()) {
            $this->redirect(array(
                'controller' => 'account',
                'action' => 'index'
            ));
            return;
        }

        $sError = $this->Request()->getParam('sError');
        if ($sError) {
            $this->View()->sError = $sError;
        }

        $this->action = 'register';
        $user = $this->getUser();
        $selected_payment = $this->Request()->getParam('selected_payment');
        if ($selected_payment) {
            $this->paymentMethod = $selected_payment;
        } else {
            $this->paymentMethod = $this->Request()->getParam('pm');
        }

        $client->setPaymentMethod($this->paymentMethod);

        $recurringChekoutResult = $client->getRecurringChekoutResult($user);

        if (!$recurringChekoutResult['isValid']) {
            $this->redirectError($recurringChekoutResult['response'], 'ERROR_GENERAL_REDIRECT', true);
        } else {
            $this->View()->lang = $this->getShopLocale();
            $this->View()->paymentMethod = $this->paymentMethod;
            $this->View()->paymentWidgetUrl = $recurringChekoutResult['widgetUrl'];
            $this->View()->testMode = $client->getTestMode();
            $this->View()->brand = $client->getBrand();
            $this->View()->paymentFormPath = $this->getPaymentFormPath($this->paymentMethod);
        }
    }

    public function changeAction()
    {
        $client = $this->Plugin()->Client();
        if (!$client->isRecurringActive()) {
            $this->redirect(array(
                'controller' => 'account',
                'action' => 'index'
            ));
            return;
        }

        $sError = $this->Request()->getParam('sError');
        if ($sError) {
            $this->View()->sError = $sError;
        }

        $this->action = 'change';
        $user = $this->getUser();
        $selected_payment = $this->Request()->getParam('selected_payment');
        if ($selected_payment) {
            $this->paymentMethod = $selected_payment;
        } else {
            $this->paymentMethod = $this->Request()->getParam('pm');
        }

        $id = $this->Request()->getParam('id');
        $client->setPaymentMethod($this->paymentMethod);

        $recurringChekoutResult = $client->getRecurringChekoutResult($user, $id);

        if (!$recurringChekoutResult['isValid']) {
            $this->redirectError($recurringChekoutResult['response'], 'ERROR_GENERAL_REDIRECT', true);
        } else {
            $this->View()->id = $id;
            $this->View()->lang = $this->getShopLocale();
            $this->View()->paymentMethod = $this->paymentMethod;
            $this->View()->paymentWidgetUrl = $recurringChekoutResult['widgetUrl'];
            $this->View()->testMode = $client->getTestMode();
            $this->View()->brand = $client->getBrand();
            $this->View()->paymentFormPath = $this->getPaymentFormPath($this->paymentMethod);
        }
    }

    public function deleteAction()
    {
        $client = $this->Plugin()->Client();
        if (!$client->isRecurringActive()) {
            $this->redirect(array(
                'controller' => 'account',
                'action' => 'index'
            ));
            return;
        }
        $this->action = 'delete';
        $user = $this->getUser();
        $userId = $user['additional']['user']['id'];

        $paymentShortName = $this->Request()->getParam('selected_payment');
        $id = $this->Request()->getParam('id');
        $isDelete = $this->Request()->getParam('isDelete');
        $client->setPaymentMethod($paymentShortName);

        if ($isDelete) {
            $transactionData = $client->getCredentials();
            $transactionData['test_mode'] = $client->getTestMode();

            $referenceId = $client->getReferenceId($id, $userId);
            $resultJson = $client->deleteRegistration($referenceId, $transactionData);
            if ($resultJson['isValid']) {
                $returnCode = $resultJson['response']["result"]["code"];
                $returnMessage = $client->getErrorIdentifier($returnCode);
                $result = $client->getTransactionResult($returnCode);
                if ($result == "ACK") {
                    $client->deleteRecurring($id, $userId);
                    $this->action = 'delete';
                    $this->redirectSuccess();
                } else {
                    $this->View()->sError = $returnMessage;
                }
            } else {
                $this->View()->sError = $resultJson['response'];
            }
        }

        $this->View()->id = $id;
        $this->View()->selected_payment = $paymentShortName;
    }

    /**
     * Determines if session exist action.
     */
    public function isSessionExistAction()
    {
        if (!$this->getUser()) {
            die('false');
        } else {
            die('true');
        }
    }

    /**
    * @return string
    */
    private function getShopLocale()
    {
        return Shopware()->Shop()->getLocale()->getLocale() == "de_DE" ? "de" : "en";
    }

    /**
    * @param string $paymentShortName
    * @return string
    */
    private function getPaymentFormPath($paymentShortName)
    {
        switch ($paymentShortName) {
            case 'vrpay_paypalsaved':
                $template = 'redirect';
                break;
            default:
                $template = $this->action.'_cp';
                break;
        }
        return "frontend/payment_information/form/".$template.".tpl";
    }
}
