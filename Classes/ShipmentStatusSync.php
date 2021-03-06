<?php


namespace MyParcelCom\ContaoLib\Classes;


use Contao\Database;
use Contao\Message;
use MyParcelCom\ApiSdk\Http\Exceptions\RequestException;
use MyParcelCom\ContaoLib\Resources\contao\models\MyParcelComAuthModel;
use MyParcelCom\ContaoLib\Resources\contao\models\MyParcelComShipmentModel;

class ShipmentStatusSync
{
    
    public function synchronizeStatus($arrOrderIDs, MyParcelComAuthModel $authModel)
    {
        if (!$authModel) {
            return;
        }
        $apiService = new ApiAccessService(
            $authModel->clientid,
            $authModel->clientsecret,
            $authModel->apiUrl,
            $authModel->authUrl,
            $authModel->shopName
        );
        $changeCtr = 0;
        foreach ($arrOrderIDs as $orderID) {
            $shipment = MyParcelComShipmentModel::findBy('orderID', $orderID);
            if ($shipment) {
                $shipmentId = $shipment->shipmentID;
                try {
                    $objShipment = $apiService->getShipment($shipmentId);
                } catch (RequestException $e) {
                    if ($e->getResponse()->getStatusCode() === 404) {
                        $sql = "UPDATE tl_myparcelcom_api_shipment SET status = ? WHERE shipmentID = ?";
                        Database::getInstance()->prepare($sql)->execute(ShipmentStatus::STATUS_DELETED, $shipmentId);
                        Message::addError("Die Sendung mit der ID $orderID wurde im MyParcel-Portal gelöscht.");
                    }
                    continue;
                }
                $status = $objShipment->getShipmentStatus()->getStatus();
                $statusLevel = $status->getLevel();
                $statusCode = $status->getCode();
                if ($statusLevel === "success") {
                
                }
                if ($statusCode === "shipment-registered") {
                    $success = $this->updateShipmentStatus($shipmentId, $authModel);
                    if ($success) {
                        $changeCtr++;
                    }
                }
            }
        }
        return $changeCtr;
    }
    
    public function updateShipmentStatus($shipmentID, $authModel)
    {
        $db = Database::getInstance();
        $sql = "UPDATE tl_myparcelcom_api_shipment SET status = ? WHERE shipmentID = ?";
        $rowCount = $db->prepare($sql)->execute(ShipmentStatus::STATUS_TRACKABLE, $shipmentID)->affectedRows;
        $trackingStatus = $authModel->trackingStatus;
        $shipment = MyParcelComShipmentModel::findOneBy('shipmentID', $shipmentID);
        $orderID = $shipment->orderID;
        $sql = "UPDATE tl_iso_product_collection SET order_status = ? WHERE id = ?";
        $rowCount += $db->prepare($sql)->execute($trackingStatus, $orderID)->affectedRows;
        return $rowCount > 0;
    }
}