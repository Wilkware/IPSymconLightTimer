# Licht Zeitschaltuhr (Light Timer)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-5.2-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.4.20210505-orange.svg)](https://github.com/Wilkware/IPSymconWeatherWarning)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconLightTimer/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconLightTimer/actions)

Dieses Modul ermöglicht das Schalten eines Gerätes (Variable und/oder Skripts) in Abhängigkeit von Uhrzeit und/oder des täglichen Sonnenganges.

## Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

Für eine einfache Zeitschaltung wäre dieses Modul normalerweise nicht notwendig. Die Erstellung einen Wochenplanes oder eines zyklischen Ereignisses ist mit IPS Bordmitteln recht einfach möglich. Interessant wird die Sache erst wenn man bedingtes und zyklisches Schalten verbinden möchte.
Für eine solche Kombination gibt es eine Reihe von Anwendungsfälle, wie z.B ...

* Rollläden/Jalousien am Morgen zu einer definierten Zeit hochfahren (Arbeitstag unabhängig von Jahreszeit), aber abends zum Sonnenuntergang runterfahren
* Außenbeleutung bei einsetzender Dunkelheit einschalten, aber pünktlich um Mitternacht wieder ausschalten
* Haustür Notlicht einsetzenden der Dämmerung Ein- bzw.- Ausschalten
* oder zur Weihnachtszeit die Beleutung situativ schalten.

Das nur um einige Anregungen sogeben. Wahrscheinlich gibt es da noch einiges mehr an Ideen, welche sich so umsetzen lassen.

* Zeitschaltung anhand verschiedener Einstellmöglichkeiten:
  1. Aus => Ein- bzw. Ausschalten wird nicht vollzogen (externer Auslöser)
  2. Sonnengang => 8 mögliche Zeitpunkte wählbar (Sonnenaufgang und -untergang; zivile, nautische oder astronomische Dämmerung)
  3. Wochenplan => Steuerung über Zeitplan
* Zusätzlich bzw. ausschließlich kann ein Script ausgeführt werden.
* Schaltvariable muss nicht eine Aktionsvariable sein, sondern kann auch einfach eine boolsche Variable sein.
* Option das Einschalten nur zu erlauben, wenn sich die Zeiten nicht überschneiden (zeitlich korrekte Abfolge, AN-vor-AUS).
* Statusvariable als Proxy-Schalter, z.B. für Verwendung im WebFront.
* Schalten kann über mehrere Tage hinweg organisiert werden (gezielter Einsatz des täglichen Zeitplanes).

### 2. Voraussetzungen

* IP-Symcon ab Version 5.2

### 3. Installation

* Über den Modul Store das Modul Licht Zeitschaltuhr installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconLightTimer` oder `git://github.com/Wilkware/IPSymconLightTimer.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das 'Light Timer'-Modul (Alias: Licht Zeitschaltuhr, Zeitschaltuhr) unter dem Hersteller '(Sonstige)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Zeitschaltung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Auslöser Einschalten  | Auswahlmöglichkeiten: Aus; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (An)
Auslöser Ausschalten  | Auswahlmöglichkeiten: ; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (Aus)
(Zeitplan)            | Hinterlegung einer täglichen Uhrzeit für AN & AUS (Montag - Sonntag)

> Gerät ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariable        | Schalt(Aktions-)variable
Skript                | Auszuführendes Skript (Status true/false wird als Array 'State' übergeben)

> Einstellungen ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariable ist eine reine boolesche Variable! | true/false
Schaltvariable nur ein- bzw. ausschalten wenn zeitliche Abfolge korrekt ist (nur in Verbindung mit einem Wochenplan)! | true/false
Zusätzlich noch eine normale Schaltervariable anlegen (z.B. für Webfront)? | true/false

### 6. WebFront

Man kann die Statusvariable (Schalter) direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 8. Versionshistorie

v1.4.20210505

* _FIX_: Komplett neue Steuerung für die Einhaltung der zeitlichen Reihenfolge
* _NEU_: Die eingestellte Zeit kann jetzt vom Sontag auf den Montag kopiert werden

v1.3.20210426

* _FIX_: Fix für die Einhaltung der zeitlichen Reihenfolge

v1.2.20210330

* _FIX_: Umstellung auf direkte Eingabe der Uhrzeiten (kein externer Wochenplan mehr notwendig)
* _NEU_: Beachtung der zeitlichen Reihenfolge (EIN-vor-AUS) hinzugefügt

v1.1.20210326

* _NEU_: Umstellung auf frei wählbaren Ein- und Ausschaltzeitpunkt
* _NEU_: Schaltung über Tagesgrenze hinweg möglich

v1.0.20210322

* _NEU_: Initialversion

## Entwickler

* Heiko Wilknitz ([@wilkware](https://github.com/wilkware))

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Entwickler bitte hier:

[![License](https://img.shields.io/badge/Einfach%20spenden%20mit-PayPal-blue.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

### Lizenz

[![Licence](https://licensebuttons.net/i/l/by-nc-sa/transparent/ff/66/00/88x31-e.png)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
