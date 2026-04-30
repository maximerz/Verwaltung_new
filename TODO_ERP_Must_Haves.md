# TODO: ERP Must-Haves

## Ziel
Diese Liste enthält nur die Funktionen, die für ein kleines, ernsthaft nutzbares ERP in diesem Projekt als Pflichtumfang gelten. Sie ist bewusst enger als eine allgemeine ERP-Wunschliste.

## Bereits vorhanden
- [x] Benutzer-Login
- [x] Rollen-/Admin-Grundsystem
- [x] 2FA-Basis
- [x] Audit-Log
- [x] Kundenverwaltung
- [x] Lieferanten-Grunddaten
- [x] Produktkatalog
- [x] Angebotsprozess
- [x] Auftragsbestätigung
- [x] Rechnungs-PDF / Lieferschein-PDF
- [x] Lagerbestand / Reservierung
- [x] Basis-Reporting
- [x] API-Grundfunktionen
- [x] Backup-/Betriebsskripte

## Pflichtlücken

### 1. Belegfluss sauber schließen
- [ ] Angebotsstatus, Auftragsstatus, Rechnungsstatus und Lieferscheinstatus konsistent modellieren
- [ ] Nachvollziehbarer Belegfluss: Angebot -> Auftrag -> Lieferschein -> Rechnung -> Zahlung
- [ ] Eindeutige Verknüpfung zwischen allen Belegen in der UI
- [ ] Storno-/Abbruchlogik für Angebot, Auftrag und Rechnung

### 2. Einkauf vollständig machen
- [ ] Lieferantenbestellung anlegen
- [ ] Bestellpositionen für Einkauf
- [ ] Wareneingang buchen
- [ ] Offene Bestellungen bei Lieferanten verfolgen
- [ ] Lieferantenrechnung erfassen

### 3. Finanzmodul auf Mindestniveau bringen
- [ ] Offene-Posten-Logik für Debitoren
- [ ] Fälligkeiten und Zahlungsziel sauber verwalten
- [ ] Mahnwesen / Zahlungserinnerungen
- [ ] Gutschriften / Stornos für Rechnungen
- [ ] Export für Steuerberater/Buchhaltung, mindestens CSV

### 4. Lagerprozesse absichern
- [ ] Wareneingänge und Warenausgänge historisieren
- [ ] Lagerbewegungen protokollieren
- [ ] Mindestbestände / Nachbestellvorschläge
- [ ] Bestandskorrekturen mit Begründung
- [ ] Inventur-Funktion

### 5. Rechte- und Sicherheitsmodell vervollständigen
- [ ] Feingranulare Rechte statt nur User/Admin
- [ ] Berechtigungen pro Modul oder Aktion
- [ ] Passwort-Richtlinien / Passwort-Reset
- [ ] Session-/Login-Härtung prüfen
- [ ] Sicherheitsrelevante Aktionen vollständig auditieren

### 6. Datenqualität und Stammdatenpflege
- [ ] Pflichtfelder und Validierungen vereinheitlichen
- [ ] Dublettenprüfung für Kunden und Lieferanten
- [ ] Artikelpreise, Steuersätze, Konditionen konsistent verwalten
- [ ] Einheitliche Nummernkreise für Angebot, Auftrag, Rechnung, Lieferschein

### 7. Reporting für operative Nutzung
- [ ] Offene Angebote
- [ ] Offene Aufträge
- [ ] Offene Rechnungen
- [ ] Lagerwarnungen / Nachbestellbedarf
- [ ] Umsatz nach Kunde / Produkt / Zeitraum

### 8. Technische Mindestanforderungen
- [ ] Zentrale Konfiguration statt verteilter Logik
- [ ] Konsistente Fehlerbehandlung
- [ ] Basis-Tests für Kernprozesse
- [ ] Installations-/Betriebsdoku aktualisieren
- [ ] Datenbankmigrationen statt ad-hoc Tabellenänderungen

## Wichtig, aber nicht zwingend für Version 1
- [ ] CRM-Pipeline / Leads
- [ ] DATEV / Bank / SEPA
- [ ] DMS / Dokumentenablage
- [ ] Seriennummern / Chargen
- [ ] Multi-Lager / Umlagerungen
- [ ] Produktionsplanung / Stücklisten
- [ ] Ticket-/Service-Modul
- [ ] Mobile App / PWA

## Empfohlene Umsetzungsreihenfolge
1. Belegfluss konsolidieren
2. Lagerbewegungen + Wareneingang
3. Debitoren / offene Posten / Mahnwesen
4. Rechte- und Validierungsmodell
5. Einkauf vervollständigen
6. Reporting und Tests nachziehen

## Nächster sinnvoller Implementierungsschritt
Der wichtigste fachliche Ausbau ist aktuell:

- [ ] Gemeinsames Belegmodell für Angebot, Auftrag, Lieferschein und Rechnung aufbauen

Ohne diesen Schritt bleiben Vertrieb, Lager und Buchhaltung inkonsistent.
