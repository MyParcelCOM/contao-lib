# MyParcelCom - contao-lib (Api)
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
