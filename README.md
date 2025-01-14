

# D.U.M.M. - Datenbasierter universeller Mitgliedermanager
## Installieren
- In der Datenbank muss es eine Log-Tabelle geben. Diese kann so angelegt werden:
```
CREATE TABLE `log` ( 
`ID` BIGINT AUTO_INCREMENT NOT NULL,
`zeit` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ,
`eintrag` VARCHAR(1000) NULL,
    PRIMARY KEY (`ID`)
)
ENGINE = InnoDB;
INSERT INTO log (eintrag) VALUES ('Herzlich Willkommen!');
```
  
  - In einer Tabelle muss zunächst mindestens ein Datensatz existieren, bevor die Darstellung dieser Tabelle korrekt funktioniert.

## Berechtigungsstruktur
Es gilt der Grundsatz, dass immer eine Ebene nach unten berechtigt wird.

### Definition
V := Regionalverband (Berechtigung in b_regionalverband_rechte), im folgenden auch 'Verband' genannt
B := Betriebssportgemeinschaft (b_bsg_rechte)
S := Sparten
M := Mitglieder


Dann gilt:
(1) V => B
(2)      B => M
(3) V => S
(4) Als Sonderfall: M => M
offen (5) B => M->S

(1) Nur der Verband kann seine BSG anlegen und (z.B. ANsprechpartner) ändern.
(2) Nur (außer (5)) BSG kann seine Mitglieder anlegen und ändern.
(3) Nur der Verband kann seine Sparten anlegen oder ändern.
(4) Als Sonderfall kann ein Mitglied seine eigenen Daten ändern.
(5) Nur die BSG kann ihre Mitglieder Sparten zuweisen
... Sollten sich Mitglieder selbst Sparten zuweisen/anmelden können? Bislang nur über BSG (die dann auch die Rechnung zahlt...)

# LoP
## Berechtigungen weitermachen
## fix: Spaltenbreite ist nicht ausreichend implementiert.
## Wenn M vorhanden:
BSG-Vewrbandsansicht, Ansprechpartner verbinden

## ####################################
Suche nicht nur in den Datenfeldern des Queries, sondern erweitere die Felder auf * (from und joins bleiben natürlich)
## ####################################

