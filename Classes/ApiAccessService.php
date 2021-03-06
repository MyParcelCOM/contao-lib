<?php

namespace MyParcelCom\ContaoLib\Classes;

use Contao\Database;
use Contao\System;
use MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCom\ApiSdk\Exceptions\InvalidResourceException;
use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCom\ApiSdk\Resources\Address;
use MyParcelCom\ApiSdk\Resources\Customs;
use MyParcelCom\ApiSdk\Resources\Interfaces\AddressInterface;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
use MyParcelCom\ApiSdk\Resources\PhysicalProperties;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\Resources\ShipmentItem;
use MyParcelCom\ApiSdk\Resources\Shop;
use Psr\Log\LoggerInterface;

class ApiAccessService
{
    private $clientID = "";
    
    private $clientSecret = "";
    
    // URL of the API
    private $url = "";
    
    // URL of the authentication server
    private $authUrl = "";
    
    private $shopName = "";
    
    private $authenticated = false;
    
    /**
     * @var Shop
     */
    private $currentShop = null;
    
    /**
     * @var MyParcelComApi
     */
    private $api = null;
    
//    /**
//     * @var LoggerInterface
//     */
//    private $logger = null;
    
    /**
     * ApiAccessService constructor.
     * @param string $clientID
     * @param string $clientSecret
     * @param string $url
     */
    public function __construct(string $clientID, string $clientSecret, string $url, $authUrl, $shopName)
    {
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->url = $url;
        $this->authUrl = $authUrl;
        $this->api = new MyParcelComApi($url);
        $this->shopName = $shopName;
//        $this->logger = System::getContainer()->get('logger');
    }
    
    /**
     * Checks if the authentication has already been done and does so, if not.
     * @return bool
     */
    private function authenticate()
    {
        if (!$this->authenticated) {
            $authenticator = new ClientCredentials(
                $this->clientID,
                $this->clientSecret,
                $this->authUrl
            );
            $this->api->authenticate($authenticator);
            $this->authenticated = true;
        }
        return $this->authenticated;
    }
    
    private function getCurrentShop()
    {
        $this->authenticate();
        if ($this->currentShop === null) {
            $availableShops = $this->api->getShops();
            foreach ($availableShops as $shop) {
                if ($shop->getName() === $this->shopName) {
                    $this->currentShop = $shop;
                    break;
                }
            }
        }
        return $this->currentShop;
    }
    
    /**
     * Connects with the api and creates the shipment resource with the required fields.
     * @param $weight
     * @param $authID
     * @param $orderID
     * @param $recipientAddress
     * @param $additionalData
     */
    public function createShipment(
        $weight,
        int $authID,
        int $orderID,
        array $recipientAddress,
        $additionalData = []
    ) {
        $this->authenticate();
        $shipment = new Shipment();
        $physProps = new PhysicalProperties();
        $physProps->setWeight($weight, PhysicalProperties::WEIGHT_GRAM);
        $shipment->setPhysicalProperties($physProps);
        $shipment->setRecipientAddress($this->convertAddress($recipientAddress));
        $shop = $this->getCurrentShop();
        $shipment->setShop($shop);
        $shipment->setSenderAddress($shop->getSenderAddress());
        $shipment->setReturnAddress($shop->getReturnAddress());
        // set optional, additional data
        // value amount should be specified in cents
        $shipment->setRegisterAt(0);
        if ($additionalData['amount'] && $additionalData['currency']) {
            $shipment->setTotalValueAmount((int) $additionalData['amount']);
            $shipment->setTotalValueCurrency($additionalData['currency']);
        }
        if ($additionalData['description']) {
            $shipment->setDescription($additionalData['description']);
        }
        if ($additionalData['dimensions']) {
            $shipment->getPhysicalProperties()->setHeight($additionalData['dimensions']['height']);
            $shipment->getPhysicalProperties()->setWidth($additionalData['dimensions']['width']);
            $shipment->getPhysicalProperties()->setLength($additionalData['dimensions']['length']);
        }
        $international = false;
//        $availableServices = $this->api->getServices();
//        foreach ($availableServices as $service) {
//            if ($service->getName() === "Classic") {
//                $shipment->setService($service);
//                break;
//            }
//        }

        if ($this->isInternationalShipment($shipment)) {
            $international = true;
            $items = $additionalData['items'];
            $objCustoms = new Customs();
            $objCustoms->setContentType($additionalData['content_type']);
            $objCustoms->setNonDelivery($additionalData['non_delivery']);
            $objCustoms->setIncoterm($additionalData['incoterm']);
            $objCustoms->setInvoiceNumber($additionalData['invoice_number'] ?: "1");
            $shipment->setCustoms($objCustoms);
            $arrItems = [];
            foreach ($items as $item) {
                $objItem = new ShipmentItem();
                $objItem->setDescription($item['description']);
                $objItem->setHsCode($item['hs_code']);
                $objItem->setItemValue($item['item_value']);
                $objItem->setQuantity($item['quantity']);
                $objItem->setOriginCountryCode($item['origin_country_code']);
                $objItem->setCurrency($additionalData['currency']);
                $objItem->setSku($item['sku']);
                $arrItems[] = $objItem;
            }
            $shipment->setItems($arrItems);
        }
        
        try {
            $createdShipment = $this->api->createShipment($shipment);
        } catch (InvalidResourceException $exception) {
//            $this->logger->error($exception->getMessage());
            return false;
        }
        // store shipment to database
        $status = $createdShipment->getShipmentStatus()->getStatus()->getLevel();
        
        $shipmentID = $createdShipment->getId();
        $weight = $createdShipment->getPhysicalProperties()->getWeight();
        $insertData = [
            'status' => $status,
            'shipmentID' => $shipmentID,
            'weight' => $weight,
            'authID' => $authID,
            'orderID' => $orderID
        ];
        $result = Database::getInstance()->prepare(
            "INSERT INTO tl_myparcelcom_api_shipment %s"
        )->set($insertData)->execute();
        if ($result->insertId) {
            return ['success' => true, 'international' => $international];
        } else {
            return false;
        }
    }
    
    /**
     * Returns true if the sender country and the recipient country are different.
     * @param Shipment $shipment
     * @return bool
     */
    private function isInternationalShipment(Shipment $shipment)
    {
        $senderAddress = $shipment->getShop()->getSenderAddress();
        return $senderAddress->getCountryCode() !== $shipment->getRecipientAddress()->getCountryCode();
    }
    
    /**
     * Updates the shipment with registered_at set to the current time.
     * @param $shipmentID
     */
    public function registerShipment($shipmentID)
    {
        $this->authenticate();
        $registered_at = time();
        $shipment = $this->api->getShipment($shipmentID);
        $shipment->setRegisterAt($registered_at);
        try {
            $this->api->updateShipment($shipment);
        } catch (InvalidResourceException $exception) {
//            $this->logger->error($exception->getMessage());
            return false;
        }
        return true;
        
    }
    
    public function getShipment($shipmentId)
    {
        $this->authenticate();
        return $this->api->getShipment($shipmentId);
    }
    
    /**
     * Returns the label in base64 encoded format for the given shipment.
     * @param $shipmentId
     */
    public function getLabel($shipmentId)
    {
        $this->authenticate();
        $shipment = $this->api->getShipment($shipmentId);
        $files = $shipment->getFiles();
        foreach ($files as $file) {
            if ($file->getDocumentType() === FileInterface::DOCUMENT_TYPE_LABEL) {
                // save file and store path into
                $base64Pdf = $file->getBase64Data();
                return $base64Pdf;
            }
        }
        return "";
    }
    
    private function convertAddress(array $addressData) : AddressInterface
    {
        $address = new Address();
        $address->setStreet1($addressData['street'] . " " . $addressData['streetnumber']);
        $address->setCity($addressData['city']);
        $address->setCountryCode(strtoupper($addressData['country']));
        $address->setFirstName($addressData['firstname']);
        $address->setLastName($addressData['lastname']);
//        $address->setStreetNumber($addressData['streetnumber']);
        $address->setPostalCode($addressData['postalCode']);
        // set default "-" as company since it is mandatory
        $address->setCompany($addressData['company'] ?: "-");
        $address->setPhoneNumber($addressData['phoneNumber'] ?: "+4915712345678");
        return $address;
    }
}