# SymconJvcProjectorControl

SymconJvcProjectorControl ist ein Erweiterungsmodul für IP-Symcon und dient dazu, einen JVC Projektor über das Netzwerk zu steuern.

### Inhalt

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation und Konfiguration](#3-installation)
4. [Variablen und Variablenprofile](#4-variablen-und-variablenprofile)
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
6. [Anhang](#6-anhang)
7. [ToDo](7#-todo)

### 1. Funktionsumfang

Derzeit ist folgende Basisfunktionalität implementiert:

- Ein- und Ausschalten
- Umschalten des Eingangs
- Lampenmodus einstellen
- Zugriff auf wesentliche Projektorinformationen

Mehr brauche ich persönlich in der täglichen Nutzung nicht. Ich kann aber bei Bedarf weitere Aktionen implementieren, da das mit verhältnismäßig wenig Aufwand möglich ist. Was der Projektor grundsätzlich zulässt, kann der JVC Doku (siehe Link im [Anhang](#6-anhang)) entnommen werden.

### 2. Voraussetzungen

- IP-Symcon ab Version 4.0
- kompatibler JVC Beamer mit aktivierter Netzwerkschnittstelle

### 3. Installation

Die Einrichtung erfolgt über die Modulverwaltung von Symcon.

Über das Modul-Control folgende URL hinzufügen: `git://github.com/bernd70/SymconJvcProjectorControl.git`  

Danach können JVC Projektor Instanzen erstellt werden.

__Konfigurationsseite__

Name                          | Beschreibung
----------------------------- | ----------------------------------------------
Host                          | Die IP-Adresse des JVC Projektors
Port                          | TVP Port des Pojektors (Default: 20554)
Abfrageintervall              | In welchem Abstand soll der Projektor abgefragt werden. (Default: 10 Sekunden)
LogLevel                      | Detailgrad der Logmeldungen
Button "Einschalten"          | Schaltet den Projektor ein
Button "Ausschalten"          | Schaltet den Projektor in den Standby.

### 4. Variablen und Variablenprofile

Die Variablen und Variablenprofile werden automatisch angelegt.

#### Variablen

Die nachfolgenden Variablen stehen zur Verfügung und werden zyklisch aktualisiert. Teilweise besteht eine Voraussetzung für das Lesen der Information.

Name          | Typ                                 | Beschreibung                            | Lese-Voraussetzung       | Anmerkung
------------- | ----------------------------------- | --------------------------------------- | ------------------------ | ----------------------------------
Model         | String                              | Projektormodell                         |                          | Wird einmalig nach Ändern der Modulkonfiguration gelesen
Power         | Boolean                             | Variable zum Schalten der Projektor     |                          | Die Variable "Power" dient zum einfachen ein- und ausschalten über das UI. Der Zustand ist true, wenn der PowerState "Powered On" ist, ansonsten ist sie false.
PowerState    | JvcProjectorControl.PowerState      | Power Status                            |                          | 
MACAddress    | string                              | MAC Adresse                             |                          |
CurrentInput  | JvcProjectorControl.Input           | Aktueller Eingang                       | PowerState == PoweredOn  |
SourceState   | JvcProjectorControl.SourceStatus    | Status der Quelle                       | PowerState == PoweredOn  |
Signal        | string                              | Anliegendes Signal                      | PowerState == PoweredOn  |
LampHours     | Integer                             | Laufzeit der Lampe in Stunden           | PowerState == PoweredOn  |
Version       | string                              | Firmware Version                        | PowerState == PoweredOn  |
ColroModel    | string                              | Farbmodell und Farbtiefe                | SourceStatus == Okay     |
ColorSpace    | string                              | Farbraum                                | SourceStatus == Okay     |
HDRMode       | string                              | HDR Modus                               | SourceStatus == Okay     |

#### Variablenprofile

__JvcProjectorControl.PowerState__

Wert | Bezeichnung     | Anmerkung
---- | --------------- | -----------------
0    | Unbekannt       | Der Zustand wurde noch nicht ermittelt oder kann nicht ermittelt werden
1    | Standby         | Der Projekor ist ausgeschaltet
2    | Hochfahren      | Zwischenzustand zwischen Standby und Eingeschaltet
3    | Eingeschaltet   | Der Projekor ist eingeschaltet
4    | Abkühlen        | Zwischenzustand zwischen Eingeschaltet und Standby
5    | Notfall         | Notfallzustand

__JvcProjectorControl.Input__

Wert | Bezeichnung
---- | --------------
0    | Unbekannt
1    | HDMI 1
2    | HDMI 2
3    | Component
4    | PC
5    | Video
6    | SVideo

__JvcProjectorControl.SourceStatus__

Wert | Bezeichnung          | Anmerkung
---- | -------------------- | ----------------
0    | Unbekannt            | Der Zustand wurde noch nicht ermittelt oder kann nicht ermittelt werden (z.B. Projektor ist aus)
1    | JVC Logo             | Das JVC Logo wird angezeigt
2    | Okay                 | Ein gültiges Signal liegt an
3    | Kein gültiges Signal | Es liegt kein gültiges Signal an

### 5. PHP-Befehlsreferenz

Soweit nicht anders angegeben, liefern die Funktionen keinen Rückgabewert.

```php
JvcProjectorControl_GetProjectorStatus(integer $InstanzID);
```
Liest den Status des Projektors mit der InstanzID $InstanzID und setzt alle Symcon Variablen.

```php
JvcProjectorControl_PowerOn(integer $InstanzID);
```
Schaltet den Projektor mit der InstanzID $InstanzID ein.
Der Befehl kann nur ausgeführt werden, wenn der Projektor aus ist (PowerState = "Standby").

```php
JvcProjectorControl_PowerOff(integer $InstanzID);
```
Schaltet den Projektor mit der InstanzID $InstanzID aus.
Der Befehl kann nur ausgeführt werden, wenn der Projektor an ist (PowerState = "PoweredOn").

```php
JvcProjectorControl_SwitchInput(integer $InstanzID, integer $input);
```
Schaltet den Projektor mit der InstanzID $InstanzID auf einen bestimmten Eingang.
Der Befehl kann nur ausgeführt werden, wenn der Projektor an ist (PowerState = "PoweredOn").

```php
JvcProjectorControl_SetLampPower(integer $InstanzID, bool $high);
```
Schaltet den Lampenmodus des Projektors mit der InstanzID $InstanzID auf Normal ($high = False) oder Hoch ($high = True)
Der Befehl kann nur ausgeführt werden, wenn der Projektor an ist (PowerState = "PoweredOn").

### 6. Anhang

__Quellen__

- [JVC D-ILA® Projector Remote Control Guide](http://support.jvc.com/consumer/support/documents/DILAremoteControlGuide.pdf)

### 7. ToDo

- evtl. DE/EN Unterstützung
- Projektorcodes ergänzen
