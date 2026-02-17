# Testanleitung für Resource Manager

Diese Anleitung führt Sie durch das Testen aller User Stories mit den vorhandenen Seed-Daten.

## Vorbereitung

Starten Sie die Anwendung und führen Sie die Seed-Daten aus:

```bash
php artisan migrate:fresh --seed
```

### Testbenutzer

| E-Mail          | Passwort | Rolle                     |
| --------------- | -------- | ------------------------- |
| admin@admin.com | password | Admin                     |
| user@user.com   | password | Contributor (Mitarbeiter) |

Die restlichen Benutzer werden automatisch generiert (Planner, Manager, Viewer).

---

## 1. Ressourcenverwaltung

**Anforderung:** Erstellen, Bearbeiten und Löschen von Ressourcen (Mitarbeiter, Räume).

### Testschritte

1. Melden Sie sich als Admin an (`admin@admin.com`).
2. Navigieren Sie zu **Ressourcen** im Seitenmenü.
3. **Erstellen:** Klicken Sie auf "Neu", füllen Sie die Felder aus (z.B. "Besprechungsraum C", Typ "Room", 1 Slot).
4. **Bearbeiten:** Klicken Sie auf eine bestehende Ressource, ändern Sie den Namen.
5. **Löschen:** Löschen Sie eine nicht zugewiesene Ressource.

### Seed-Daten

- 8 Mitarbeiter (Person) mit je 8h/Tag Kapazität
- 2 Teams: Entwicklungsteam, Betriebsteam
- 4 Räume: Konferenzraum A, Werkstattbereich, Besprechungsraum B
- 4 Geräte: Gabelstapler #2, 3D-Drucker, Beamer

---

## 2. Aufgabenerstellung

**Anforderung:** Aufgaben mit Start-/Enddatum, Aufwand und Priorität erfassen.

### Testschritte

1. Navigieren Sie zu **Aufgaben**.
2. **Erstellen:** Klicken Sie auf "Neu", füllen Sie alle Felder aus:
    - Titel: "Test-Aufgabe"
    - Beschreibung: "Dies ist eine Test-Aufgabe"
    - Start: Heute + 3 Tage
    - Ende: Heute + 5 Tage
    - Aufwand: 16 Stunden
    - Priorität: Hoch
3. **Bearbeiten:** Ändern Sie die Priorität.
4. **Löschen:** Löschen Sie eine geplante Aufgabe.

### Seed-Daten

12 deutsche Aufgaben vorhanden, z.B.:

- Büroflügel Renovierung (In Bearbeitung)
- Produktversion 3.0 Start sprint (Geplant, Dringend)
- Jährliche Sicherheitsprüfung (Geplant, Hoch)
- IT-Infrastruktur Migration (Geplant, Dringend)

---

## 3. Manuelle Zuweisung

**Anweisung:** Aufgaben bestimmten Ressourcen zuweisen.

### Testschritte

1. Navigieren Sie zu **Zuweisungen**.
2. Wählen Sie eine unzugewiesene Aufgabe (z.B. "Einarbeitung neue Mitarbeiter — Q1 Kohorte").
3. Klicken Sie auf "Zuweisen".
4. Wählen Sie eine Ressource (z.B. "Entwicklungsteam") mit Zeitraum.
5. Speichern Sie die Zuweisung.

### Seed-Daten

9 von 14 Aufgaben sind bereits zugewiesen. Diese Aufgaben sind unzugewiesen (zum Testen):

- Einarbeitung neue Mitarbeiter — Q1 Kohorte
- Messestand Fertigung
- Prozessoptimierung Team-übergreifend

---

## 4. Automatische Zuweisung

**Anforderung:** Aufgaben automatisch basierend auf Qualifikationen zuweisen.

### Testschritte

1. Navigieren Sie zu **Zuweisungen**.
2. Wählen Sie eine unzugewiesene Aufgabe (z.B. "Messestand Fertigung").
3. Klicken Sie auf die Schaltfläche für automatische Zuweisung (Magic-Wand oder Auto).
4. Das System zeigt verfügbare Ressourcen mit passenden Qualifikationen.
5. Wählen Sie eine Ressource aus und bestätigen Sie.

### Test-Tipp

Aufgabe "Messestand Fertigung" benötigt:

- Frontend-Entwicklung (Stufe: Beginner)
- UX-Forschung (Stufe: Advanced)
- Audio-Einrichtung (Stufe: Intermediate)

Person-Ressourcen haben zufällige Qualifikationen. Die automatische Zuweisung findet passende Ressourcen basierend auf diesen Anforderungen.

---

## 5. Konflikt-Warnung

**Anforderung:** Warnung bei Überlastung oder Doppelbelegung.

### Testschritte

1. Navigieren Sie zu **Zuweisungen**.
2. Wählen Sie "Dringende Reparaturen" (konfliktbehaftete Aufgabe).
3. Versuchen Sie, Person 1 zuzuweisen (im Urlaub/krank).
4. **Erwartet:** Warnmeldung "Konflikt erkannt".

### Vordefinierte Konflikte

| Aufgabe                | Konfliktart          | Beschreibung                                                 |
| ---------------------- | -------------------- | ------------------------------------------------------------ |
| Dringende Reparaturen  | Abwesenheitskonflikt | Person 1 ist krank (heute bis +2 Tage)                       |
| Dringende Reparaturen  | Raumkonflikt         | Konferenzraum A bereits gebucht (Tag 1, 10:00-12:00)         |
| Marketing-Fotoshooting | Doppelbelegung       | Person 2 bereits bei "IT-Infrastruktur Migration" (ab Tag 4) |

---

## 6. Konfliktlösung-Vorschläge

**Anforderung:** Alternative Ressourcen oder Zeiträume vorschlagen.

### Testschritte

1. Versuchen Sie, einen Konflikt zuzuweisen (wie oben).
2. Das System sollte alternative Optionen anzeigen:
    - Andere verfügbare Ressourcen
    - Andere Zeitfenster
3. Wählen Sie eine Alternative und bestätigen Sie.

### Test-Tipp

Die Abwesenheitsdaten in ResourceAbsenceSeeder:

- Person 0: Urlaub (in 3-5 Tagen)
- Person 1: Krankheit (heute bis +2 Tage)
- Person 2: Fortbildung (in 7-9 Tagen)
- Gabelstapler #2: Wartung (in 2-3 Tagen)

---

## 7. Prioritätsbasierte Planung

**Anforderung:** Höher priorisierte Aufgaben bevorzugt planen.

### Testschritte

1. Erstellen Sie mehrere Aufgaben mit unterschiedlichen Prioritäten:
    - Dringend: "Notfall-Bugfix"
    - Hoch: "Wichtiges Feature"
    - Niedrig: "Dokumentation"
2. Nutzen Sie die automatische Zuweisung.
3. **Erwartet:** System berücksichtigt Prioritäten bei der Zuweisung.

### Seed-Daten - Prioritätsverteilung

| Priorität | Anzahl | Beispiele                                              |
| --------- | ------ | ------------------------------------------------------ |
| Dringend  | 3      | Produktversion 3.0, IT-Migration, Notfallübung         |
| Hoch      | 3      | Büroflügel Renovierung, Sicherheitsprüfung, Messestand |
| Mittel    | 4      | Kunden-Workshop, Lagerbestand, Einarbeitung, Wartung   |
| Niedrig   | 2      | Quartalsreview, Prozessoptimierung                     |

---

## 8. Visuelle Übersicht (Kalender/Gantt)

**Anforderung:** Alle Aufgaben und Ressourcen in Kalender- oder Gantt-Ansicht.

### Testschritte

1. Navigieren Sie zu **Zeitplan** (Schedule).
2. Sie sehen eine Übersicht aller Zuweisungen.
3. **Zeitraum ändern:** Nutzen Sie die Zeitraum-Auswahl (Woche, Monat, Quartal).
4. Navigieren Sie zwischen Zeiträumen mit den Pfeiltasten.
5. Filtern Sie nach Ressourcen oder Aufgaben.

### Test-Tipp

Die Zeitachsen der Aufgaben:

- Vergangen: Quartalsreview (-10 Tage), Notfallübung (-15 Tage)
- Aktuell: Büroflügel Renovierung (seit -3 Tagen)
- Zukunft: Alle anderen geplanten Aufgaben

---

## 9. Auslastungsansicht

**Anforderung:** Auslastung jeder Ressource über Zeitraum anzeigen.

### Testschritte

1. Navigieren Sie zu **Auslastung**.
2. Wählen Sie einen Zeitraum (z.B. diese Woche, nächste Woche).
3. **Erwartet:** Balkendiagramm pro Ressource mit Auslastung in %.
4. Identifizieren Sie überlastete (>100%) oder unterausgelastete Ressourcen.

### Seed-Daten -分配 ratios

Verschiedene Allokationsverhältnisse zum Testen:

- 0.25 (25%): Einarbeitung Tag 1
- 0.50 (50%): Mehrere Zuweisungen
- 0.75 (75%): Entwicklungssprint
- 1.00 (100%): Volle Zuweisung

---

## 10. Rollenverwaltung

**Anforderung:** Verschiedene Benutzerrollen mit Zugriffskontrolle.

### Testschritte

1. Melden Sie sich als Admin ab und als anderer Benutzer an.
2. Testen Sie die Berechtigungen für jede Rolle:

| Rolle       | Zugriff                                     |
| ----------- | ------------------------------------------- |
| Admin       | Alles: Vollständiger Zugriff                |
| Planner     | Zuweisung, Erstellung, Zeitplan, Auslastung |
| Manager     | Zeitplan, Auslastung, eigene Aufgaben       |
| Contributor | Eigene Aufgaben anzeigen und Status ändern  |
| Viewer      | Nur-lesen: Zeitplan, Auslastung             |

### Seed-Daten

Rollen mit Berechtigungen (siehe PermissionSeeder):

- Admin: Voller Zugriff auf alle Bereiche
- Planner: Lese-/Schreibzugriff auf Planung
- Manager: Eingeschränkter Zugriff
- Contributor: Eigene Aufgaben
- Viewer: Nur-lesen

---

## 11. Mitarbeiter-Feedback

**Anforderung:** Mitarbeiter können Status ihrer zugewiesenen Aufgaben ändern.

### Testschritte

1. Melden Sie sich als Contributor an (`user@user.com`).
2. Navigieren Sie zu **Meine Zuweisungen** (oder "/my-assignments").
3. Sie sehen nur Ihre zugewiesenen Aufgaben.
4. Klicken Sie auf eine Aufgabe und ändern Sie den Status:
    - Ausstehend → Angenommen
    - Angenommen → In Bearbeitung
    - In Bearbeitung → Erledigt
5. Speichern Sie die Änderung.

### Test-Tipp

Die User "user@user.com" hat die Rolle Contributor. Die zugehörige Person-Ressource wurde automatisch erstellt und hat Aufgaben zugewiesen bekommen (Person 5 in der Reihenfolge).

---

## Schnelltest-Checkliste

- [ ] Resource Management: Räume und Geräte erstellen/bearbeiten
- [ ] Task Creation: Neue Aufgabe mit Priorität erstellen
- [ ] Manual Assignment: Aufgabe einer Person zuweisen
- [ ] Auto Assignment: Automatische Zuweisung für unzugewiesene Aufgabe testen
- [ ] Conflict Warning: Konflikt bei "Dringende Reparaturen" auslösen
- [ ] Conflict Suggestions: Alternative Ressourcen angezeigt bekommen
- [ ] Visual Overview: Zeitplan mit variablem Zeitraum anzeigen
- [ ] Utilization View: Auslastungsdiagramm pro Ressource prüfen
- [ ] Role Management: Verschiedene Rollen testen
- [ ] Employee Feedback: Als Contributor Status ändern

---

## Probleme beheben

Falls die Seed-Daten nicht wie erwartet funktionieren:

```bash
# Datenbank neu aufsetzen
php artisan migrate:fresh --seed

# Oder nur Seeder neu ausführen
php artisan db:seed
```

Für spezifische Seeder:

```bash
php artisan db:seed --class=ResourceSeeder
php artisan db:seed --class=TaskSeeder
```
