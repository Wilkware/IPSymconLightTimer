# Licht Zeitschaltuhr (Light Timer)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.0-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.0.20220216-orange.svg)](https://github.com/Wilkware/IPSymconLightTimer)
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

### [1. Funktionsumfang](#1-funktionsumfang)

Für eine einfache Zeitschaltung wäre dieses Modul normalerweise nicht notwendig. Die Erstellung einen Wochenplanes oder eines zyklischen Ereignisses ist mit IPS Bordmitteln recht einfach möglich. Interessant wird die Sache erst wenn man bedingtes und zyklisches Schalten verbinden möchte.
Für eine solche Kombination gibt es eine Reihe von Anwendungsfälle, wie z.B. ...

* Rollläden/Jalousien am Morgen zu einer definierten Zeit hochfahren (Arbeitstag unabhängig von Jahreszeit), aber abends zum Sonnenuntergang runterfahren
* Außenbeleuchtung bei einsetzender Dunkelheit einschalten, aber pünktlich um Mitternacht wieder ausschalten
* Haustür Notlicht einsetzenden der Dämmerung Ein- bzw.- Ausschalten
* oder zur Weihnachtszeit die Beleuchtung situativ schalten.

Das nur um einige Anregungen zu geben. Wahrscheinlich gibt es da noch einiges mehr an Ideen, welche sich so umsetzen lassen.

* Zeitschaltung anhand verschiedener Einstellmöglichkeiten:
  1. Aus => Ein- bzw. Ausschalten wird nicht vollzogen (externer Auslöser)
  2. Sonnengang => 8 mögliche Zeitpunkte wählbar (Sonnenaufgang und -untergang; zivile, nautische oder astronomische Dämmerung)
  3. Wochenplan => Steuerung über Zeitplan
* Zusätzlich bzw. ausschließlich kann ein Skript ausgeführt werden.
* Schaltvariable muss nicht eine Aktionsvariable sein, sondern kann auch einfach eine boolesche Variable sein.
* Option das Einschalten nur zu erlauben, wenn sich die Zeiten nicht überschneiden (zeitlich korrekte Abfolge, AN-vor-AUS).
* Statusvariable als Proxy-Schalter, z.B. für Verwendung im WebFront.
* Schalten kann über mehrere Tage hinweg organisiert werden (gezielter Einsatz des täglichen Zeitplanes).

### [2. Voraussetzungen](#2-voraussetzungen)

* IP-Symcon ab Version 6.0

### [3. Installation](#3-installation)

* Über den Modul Store das Modul Licht Zeitschaltuhr installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconLightTimer` oder `git://github.com/Wilkware/IPSymconLightTimer.git`

### [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)

* Unter "Instanz hinzufügen" ist das 'Light Timer'-Modul (Alias: Licht Zeitschaltuhr, Zeitschaltuhr) unter dem Hersteller '(Sonstige)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Schaltung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
An /Aus               | Schalter zum Aktivieren bzw. Deaktivieren der gesamten Schaltung, z.B. Weihnachtsbeleuchtung nur im Winter ;-)

> Zeitssteuerung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Auslöser Einschalten  | Auswahlmöglichkeiten: Aus; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (An)
Auslöser Ausschalten  | Auswahlmöglichkeiten: ; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (Aus)
(Zeitplan)            | Hinterlegung einer täglichen Uhrzeit für AN & AUS (Montag - Sonntag)

> Geräte ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Geräteanzahl          | Auswahl bzw. Umschalter zwischen einzelnen und mehreren Geräten
Schaltvariable        | Schalt(Aktions-)variable (ein Gerät)
Schaltvariablen       | Liste von Geräten (mehrere Geräte)
Skript                | Auszuführendes Skript (Status true/false wird als Array 'State' übergeben)

> Einstellungen ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariable nur ein- bzw. ausschalten wenn zeitliche Abfolge korrekt ist (nur in Verbindung mit einem Wochenplan)! | true/false
Zusätzlich noch eine normale Schaltervariable anlegen (z.B. für Webfront)? | true/false

### [5. Statusvariablen und Profile](#5-statusvariablen-und-profile)

Die Statusvariablen werden je nach Einstellung automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                   | Typ       | Beschreibung
---------------------- | --------- | ----------------
Helligkeit             | Integer   | Helligkeitsslider (0-100%)
Modus                  | Integer   | Schalter für alle 4 Modi (AUS|AN|DEMO|ECHTZEIT)
Schalter               | Integer   | Einfacher Schalter für AN und AUS

Folgende Profile werden angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
Twinkly.Mode         | Integer   | 0(Aus), 1(An), 2(Demo), 3(Echtzeit)
Twinkly.Switch       | Integer   | 0(Aus), 1(An)

### [6. WebFront](#6-webfront)

Man kann die Statusvariable (Schalter) direkt im WF verlinken.

### [7. PHP-Befehlsreferenz](#7-php-befehlsreferenz)

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### [8. Versionshistorie](#8-versionshistorie)

v2.0.20220216

* _NEU_: Umschalten zwischen einem oder mehreren Geräten
* _NEU_: Eine reine boolesche Schaltvariable (ein Gerät) wird automatisch erkannt
* _NEU_: Referenzieren der Gerätevariablen hinzugefügt
* _FIX_: Globale Aktivierung bzw. Deaktivierung der Schaltung umgebaut
* _FIX_: Schaltung der Proxy Schaltvariable für Webfront korrigiert
* _FIX_: Übersetzungen erweitert bzw. korrigiert

v1.6.20220119

* _NEU_: Schalter zum manuellen aktivieren bzw. deaktivieren der Instanz (Zeitschaltuhr)
* _NEU_: Kompatibilität auf IPS 6.0 hoch gesetzt
* _NEU_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _NEU_: Konfigurationsdialog überarbeitet (v6 Möglichkeiten genutzt)

v1.5.20210625

* _FIX_: Start Bedingung korrigiert
* _FIX_: Timer Update Berechnung vereinheitlicht

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

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
