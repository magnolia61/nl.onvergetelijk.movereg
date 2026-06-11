# nl.onvergetelijk.movereg

## Functionele beschrijving

De `movereg`-extensie biedt een "Move Registration"-functionaliteit waarmee beheerders een deelnemersinschrijving kunnen verplaatsen van het ene evenement naar het andere. Dit is handig als een deelnemer van kamp wisselt, of als een inschrijving per abuis onder het verkeerde evenement is aangemaakt.

De functionaliteit is beschikbaar via een "Move Registration"-knop in het actiemenu van deelnemers (context-menu), en als bulk-taak in CiviCRM SearchKit.

## Afhankelijkheden

- `nl.onvergetelijk.base`

---

## Technische documentatie

### Bestandsstructuur

| Bestand | Inhoud |
|---|---|
| `movereg.php` | Hooks: menu, links, SearchKit tasks |
| `CRM/Movereg/Form/MoveRegistration.php` | CiviCRM Form-klasse voor de verplaats-wizard |
| `xml/Menu/movereg.xml` | URL-registratie voor de formulierpagina |
| `templates/` | Smarty-template voor het formulier |

### Kernfuncties

- `movereg_civicrm_links($op, $objectName, $objectId, &$links, ...)` — voegt de "Move Registration"-actie toe aan het actiemenu van elke deelnemerij (participant selector row en participant contact row)
- `movereg_civicrm_searchKitTasks($tasks, ...)` — registreert "Move Registration" als bulk-taak in SearchKit, zodat meerdere inschrijvingen tegelijk verplaatst kunnen worden
- `movereg_civicrm_xmlMenu($files)` — registreert het menu-XML-bestand zodat de formulierpagina via CiviCRM wordt gerouteerd
- `CRM_Movereg_Form_MoveRegistration` — de formulierklasse die de wizard afhandelt: kies het doelevenement en bevestig de verplaatsing

### Hooks geïmplementeerd
- `civicrm_config`
- `civicrm_xmlMenu`
- `civicrm_links`
- `civicrm_searchKitTasks`

---

*Beheerd door Stichting Onvergetelijke Zomerkampen.*
