# Licht Zeitschaltuhr (Light Timer)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-5.2-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.0.20210322-orange.svg)](https://github.com/Wilkware/IPSymconWeatherWarning)
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

* Zeitschaltung (Automatik) anhand 4 verschiedener Modi möglich:
  1. Aus -> Schaltung direkt über Zeitplan/Wochenplan
  2. Morgens (Halbautomatik) -> Morgens bedinges Schalten entsprechend eingestelltem Einschaltverhalten, abends zeitliche Schaltung aus Zeitplan
  3. Abends (Halbautomatik) -> Morgens zeitliches Schalten aus Zeitplan, abends bedinges Schalten entsprechend eingestelltem Auschaltverhalten
  4. Früh & Abend (Vollautomatik) -> Morgens und abends bedinges Schalten entsprechend eingestelltem Ein- und Auschaltverhalten
* Zusätzlich bzw. ausschließlich kann ein Script ausgeführt werden.
* Anlegen und Einbinden eines Wochenplans zum gezielten zeitlichen Ein- bzw. Ausschalten
* Schaltvariable muss nicht eine Aktionsvariable sein, sondern kann auch einfach eine boolsche Variable sein.
* Statusvariable als Proxy-Schalter, z.B. für Verwendung im WebFront

Vielleicht noch ein paar Worte zur Verwendung des Wochenplanes. Natürlich kann man bei einer reinen Zeitschaltung mehrere Zyklen an einen Tag vornehemn.
In Kombination mit der bedingten Schaltung (Halb- bzw. Vollautomatik) ist das wahrscheinlich nur bedingt sinnvoll.  
Bei einer eingestellten Halbautomatik wird immer nur einer der möglichen Schaltvorgänge genutzt, also nur der Zeitpunkt für das Einschalten oder eben nur der Zeitpunkt für das Ausschalten. Der andere Schaltvorgang wird durch das bedingte Ein bzw. Aus übernommen. Somit machen mehrere Zyklen im Programm keinen Sinn.
Es gibt halt nur einen Sonnenaufgang bzw. Sonnenuntergang ;-)

### 2. Voraussetzungen

* IP-Symcon ab Version 5.2

### 3. Installation

* Über den Modul Store das Modul Weather Warning installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconLightTimer` oder `git://github.com/Wilkware/IPSymconLightTimer.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das 'Light Timer'-Modul (Alias: Licht Zeitschaltuhr, Zeitschaltuhr) unter dem Hersteller '(Sonstige)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Zeitschaltung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Automatik             | Auswahl des gewünschten Modus (4 Möglichkeiten)
Bedingtes Einschalten | Auswahl Sonnenaufgang, ziviler, nautischer oder astronomischer Dämmerungsbeginn
Bedingtes Ausschalten | Auswahl Sonnenuntergang, ziviles, nautisches oder astronomisches Dämmerungsende
Zeitplan              | Hinterlegung eines zu verwendeneden Wochenplans

> Gerät ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariable        | Schalt(Aktions-)variable
Skript                | Auszuführendes Script (Status true/false wird als Array 'State' übergeben)

> Einstellungen ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariable ist eine reine boolsche Variable! | true/false
SkrZusätzlich noch eine normale Schaltervariable anlegen (z.B. für Webfront)? | true/false

### 6. WebFront

Man kann die Statusvariablen (Schalter, Zeitplan) direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 8. Versionshistorie

v1.0.20210322

* _NEU_: Initialversion

## Entwickler

* Heiko Wilknitz ([@wilkware](https://github.com/wilkware))

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Entwickler bitte hier:

[![License](https://img.shields.io/badge/Einfach%20spenden%20mit-PayPal-blue.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

### Lizenz

[![Licence](https://licensebuttons.net/i/l/by-nc-sa/transparent/00/00/00/88x31-e.png)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
