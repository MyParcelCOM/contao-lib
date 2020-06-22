# MyParcelCom - contao-lib (Api)

First we recommend to config two new order status at your isotope configuration:

e.g.

1.) Fullfillment in process (Status if order details transfered)
2.) Order shipped (Status if tracking info get from MyParcel.com - Portal)


##Backend-Modul: MyParcel.com API-Konfiguration
Here you have to setup (fill in) your MyParcel.com API credentials which you have created at the MyParcel.com Portal under the menu section "API client". Its located under the profil icon !

The following setup data needed:

- API-client credentials (from MyParcel.com Portal)
- Name of MyParcel.com-Shop
- Dropdownvalue (in this case Isotope)
- your choosen orderstatus

For the orderstatus you have two choices. The field "MyParcel.com-Bestellstatus" is the shown status if the order data are transfered e.g. Fullfillment in process.
The field "Status für Tracking" setup which status is shown if tracking data available e.g. Order shipped.
The configured/set status are changed by the bundles automatically.

## Backend-Modul: MyParcel.com API Lieferung
This menu shows all orders which are transfered to the MyParcel.com Portal. Here you can see the "shipment-id" for each order from the MyParcel.com portal, too.

More detailinformations about installation and configuration at https://help.myparcel.com/support/solutions ,too.


(German part)
Im Folgenden werden ein paar Hinweise für die Einrichtung des Bundles gegeben.
## Backend-Modul: MyParcel.com API-Konfiguration
In diesem Modul kann der Zugriff auf die MyParcel.com-API konfiguriert werden.
In den meisten Fällen wird hier ein Eintrag reichen. Ein Eintrag besteht aus
- den API-Zugangsdaten
- Name des zu verwendenden MyParcel.com-Shops
- Auswahl des verwendeten Shop-Systems (markiert diese Konfiguration für die Verwendung in z.B. Isotope)
- Auswahl von Bestellstatus

Zum Bestellstatus gibt es zwei Auswahlfelder. Das Feld "MyParcel.com-Bestellstatus" markiert den Status, der gesetzt
werden soll, wenn die Daten einer Bestellung ins MyParcel.com-Portal übertragen wurden. Das Feld "Status für Tracking" 
legt den Status fest, der gesetzt werden soll, wenn für eine Bestellung Tracking-Informationen zuer Verfügung stehen.
Die Statusoptionen und auch die Optionen für die Auswahl des Shop-Systems werden durch die
entsprechenden "Shop-Bundles" dazu gebracht.

## Backend-Modul: MyParcel.com API Lieferungen
In diesem Modul ist eine Liste der Bestellungen, die aus Contao in das MyParcel.com-Portal
gesendet wurden, zu sehen. Es werden die eindeutige "Shipment-ID" aus dem MyParcel.com-Portal,
die zugehörige Bestellungs-ID und der aktuelle Status angezeigt.
