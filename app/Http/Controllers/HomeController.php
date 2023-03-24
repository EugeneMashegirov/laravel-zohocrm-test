<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use com\zoho\crm\api\UserSignature;
use com\zoho\crm\api\dc\EUDataCenter;
use com\zoho\api\authenticator\OAuthToken;
use com\zoho\api\authenticator\TokenType;
use com\zoho\api\authenticator\store\FileStore;
use com\zoho\crm\api\SDKConfigBuilder;
use com\zoho\crm\api\Initializer;

use com\zoho\crm\api\record\Record;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\record\BodyWrapper;
use com\zoho\crm\api\record\{Deals, Contacts};
use com\zoho\crm\api\record\APIException;
use com\zoho\crm\api\exception\SDKException;
use com\zoho\crm\api\util\Choice;

class HomeController extends Controller
{
    public function index() {
        $user = new UserSignature("your.email@domain.com");
        $environment = EUDataCenter::PRODUCTION();
        $token = new OAuthToken("Client ID", "Client Secret", "Grant Token", TokenType::GRANT);
        $tokenstore = new FileStore("your/path");
        $sdkConfig = (new SDKConfigBuilder())->setAutoRefreshFields(false)->setPickListValidation(false)->setSSLVerification(true)->connectionTimeout(2)->timeout(2)->build();
        $resourcePath = "your/path";

        $recordOperations = new RecordOperations();
        $bodyWrapper1 = new BodyWrapper();
        $bodyWrapper2 = new BodyWrapper();

        $contact = new Record();
        $contact->addFieldValue(Contacts::FirstName(), 'John');
        $contact->addFieldValue(Contacts::LastName(), 'Doe');
        $contact->addFieldValue(Contacts::Email(), 'john.doe@example.com');
        $contact->addFieldValue(Contacts::Phone(), '1234567890');

        $deal = new Record();
        $deal->addFieldValue(Deals::DealName(), 'Sample Deal');
        $deal->addFieldValue(Deals::Stage(), new Choice('Prospecting'));
        $deal->addFieldValue(Deals::Amount(), 1000.0);
        $deal->addFieldValue(Deals::ClosingDate(), new \DateTime());
        
        $bodyWrapper1->setData([$contact]);
        $bodyWrapper2->setData([$deal]);

        try {
            Initializer::initialize($user, $environment, $token, $tokenstore, $sdkConfig, $resourcePath);

            $contactResponse = $recordOperations->createRecords('Contacts', $bodyWrapper1);
            $contactId = $contactResponse->getObject()->getData()[0]->getDetails()['id'];
            $contact->setId($contactId);

            $deal->addFieldValue(Deals::ContactName(), $contact);
            $dealResponse = $recordOperations->createRecords('Deals', $bodyWrapper2);
        } catch (SDKException $e) {
            return print_r($e->getDetails());
        } catch (APIException $e) {
            return print_r($e->getDetails());
        }
            
        return response()->json([$contactResponse->getStatusCode(), $dealResponse->getStatusCode()]);
    }
}
