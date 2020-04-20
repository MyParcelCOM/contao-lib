<?php

namespace MyParcelCom\ContaoApi\Classes;

use Contao\Database;
use MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCom\ApiSdk\Exceptions\InvalidResourceException;
use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCom\ApiSdk\Resources\Address;
use MyParcelCom\ApiSdk\Resources\Interfaces\AddressInterface;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
use MyParcelCom\ApiSdk\Resources\Shipment;

class ApiAccessService
{
    private $clientID = "";
    
    private $clientSecret = "";
    
    // URL of the API
    private $url = "";
    
    // URL of the authentication server
    private $authUrl = "";
    
    private $authenticated = false;
    
    /**
     * @var MyParcelComApi
     */
    private $api = null;
    
    /**
     * ApiAccessService constructor.
     * @param string $clientID
     * @param string $clientSecret
     * @param string $url
     */
    public function __construct(string $clientID, string $clientSecret, string $url, $authUrl)
    {
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->url = $url;
        $this->authUrl = $authUrl;
        $this->api = new MyParcelComApi($url);
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
    
    /**
     * Connects with the api and creates the shipment resource with the required fields.
     * @param $weight
     * @param $recipientAddress
     * @param $senderAddress
     * @param $returnAddress
     */
    public function createShipment($weight, array $recipientAddress, $senderAddress = [], $returnAddress = [])
    {
        $this->authenticate();
        $shipment = new Shipment();
        $shipment->getPhysicalProperties()->setWeight($weight);
        $shipment->setRecipientAddress($this->convertAddress($recipientAddress));
        // TODO correct shop?
        $shop = $this->api->getDefaultShop();
        if ($senderAddress !== []) {
            $shipment->setSenderAddress($this->convertAddress($senderAddress));
        } else {
            $shipment->setSenderAddress($shop->getSenderAddress());
        }
        if ($returnAddress !== []) {
            $shipment->setReturnAddress($this->convertAddress($returnAddress));
        } else {
            $shipment->setReturnAddress($shop->getReturnAddress());
        }
        try {
            $createdShipment = $this->api->createShipment($shipment);
        } catch (InvalidResourceException $exception) {
            // TODO fehlermeldung geben
            return false;
        }
        // store shipment to database
        $status = $createdShipment->getShipmentStatus()->getStatus()->getLevel();
        $shipmentID = $createdShipment->getId();
        $weight = $createdShipment->getPhysicalProperties()->getWeight();
        $insertData = [
            'status' => $status,
            'shipmentID' => $shipmentID,
            'weight' => $weight
        ];
        $result = Database::getInstance()->prepare(
            "INSERT INTO tl_myparcelcom_api_shipment %s"
        )->set($insertData)->execute();
        if ($result->insertId) {
            return true;
        } else {
            return false;
        }
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
            // TODO fehlermeldung geben
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
        // TODO street number ???
        $address = new Address();
        $address->setStreet1($addressData['street']);
        $address->setCity($addressData['city']);
        $address->setCountryCode($addressData['country']);
        $address->setFirstName($addressData['firstname']);
        $address->setLastName($addressData['lastname']);
        return $address;
    }
}