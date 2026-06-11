<?php

namespace Civi\Movereg;

use Civi\Test\EndToEndInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests voor nl.onvergetelijk.movereg.
 *
 * @group e2e
 *
 * movereg.php registreert XML-menu-items (routes), voegt links toe aan CiviCRM-lijsten
 * en registreert SearchKit-taken. Er zijn geen pure logica-functies zonder DB.
 *
 * Hier testen we:
 *   A: Hook-functies zijn geregistreerd na installatie
 *   B: movereg_civicrm_links() met irrelevant object → links ongewijzigd
 *   C: movereg_civicrm_searchKitTasks() voegt taken toe (of blijft leeg zonder crash)
 *   D: movereg_civicrm_links() met objectName='Participant' en correcte op → link toegevoegd
 *   E: movereg_civicrm_links() voor participant.selector.row → link heeft juiste velden
 *   F: movereg_civicrm_links() voor participant.contact.row → link toegevoegd
 *   G: searchKitTasks bevat Participant-taak met correcte sleutels
 *   H: movereg_civicrm_links() met onbekende op voor Participant → geen link toegevoegd
 */
class MoveregHooksTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
    if (!function_exists('movereg_civicrm_links')) {
      $this->markTestSkipped('movereg_civicrm_links() niet beschikbaar; is nl.onvergetelijk.movereg geïnstalleerd?');
    }
  }

  // ########################################################################
  // ### SCENARIO A: FUNCTIES BESTAAN
  // ########################################################################

  public function testFunctiesBestaanAllemaal() {
    $this->assertTrue(function_exists('movereg_civicrm_links'),          'movereg_civicrm_links() moet beschikbaar zijn.');
    $this->assertTrue(function_exists('movereg_civicrm_searchKitTasks'), 'movereg_civicrm_searchKitTasks() moet beschikbaar zijn.');
    $this->assertTrue(function_exists('movereg_civicrm_xmlMenu'),        'movereg_civicrm_xmlMenu() moet beschikbaar zijn.');
  }

  // ########################################################################
  // ### SCENARIO B: LINKS MET IRRELEVANT OBJECT → ONGEWIJZIGD
  // ########################################################################

  /**
   * movereg_civicrm_links() met objectName='Contact' → links ongewijzigd.
   * movereg voegt alleen links toe voor Participant-lijsten.
   */
  public function testLinksMetContactObjectBlijvenOngewijzigd() {
    $links  = ['bestaande_link' => ['name' => 'test']];
    $mask   = NULL;
    $values = [];
    movereg_civicrm_links('get', 'Contact', 1, $links, $mask, $values);
    $this->assertArrayHasKey('bestaande_link', $links, 'Bestaande links mogen niet verwijderd worden bij irrelevant object.');
    $this->assertCount(1, $links, 'Geen extra links bij objectName=Contact.');
  }

  // ########################################################################
  // ### SCENARIO C: SEARCHKITTASKS GEEN CRASH
  // ########################################################################

  /**
   * movereg_civicrm_searchKitTasks() retourneert zonder crash.
   */
  public function testSearchKitTasksGeeftGeenCrash() {
    $tasks = [];
    movereg_civicrm_searchKitTasks($tasks, FALSE, NULL);
    $this->assertIsArray($tasks, 'movereg_civicrm_searchKitTasks() moet de tasks-array intact laten.');
  }

  // ########################################################################
  // ### SCENARIO D: LINKS VOOR PARTICIPANT + CORRECTE OP → LINK TOEGEVOEGD
  // ########################################################################

  /**
   * movereg_civicrm_links() met objectName='Participant' en op='participant.selector.row'
   * moet de 'Move Registration' link toevoegen aan de links-array.
   *
   * Dit is de kern-logica van movereg: deelnemers moeten via de rij-actie kunnen worden
   * verplaatst naar een ander evenement.
   */
  public function testLinksVoorParticipantSelectorRowVoegtMoveRegistrationLinkToe() {
    $links      = [];
    $mask       = NULL;
    $values     = [];
    $object_id  = 12345; // Fictief participant ID

    movereg_civicrm_links('participant.selector.row', 'Participant', $object_id, $links, $mask, $values);

    $this->assertNotEmpty($links,
      "Links-array moet na de hook niet leeg zijn voor objectName='Participant' en op='participant.selector.row'."
    );

    // Zoek de Move Registration link in de array
    $move_link = NULL;
    foreach ($links as $link) {
      if (isset($link['name']) && $link['name'] === 'Move Registration') {
        $move_link = $link;
        break;
      }
    }

    $this->assertNotNull($move_link,
      "Er moet een link met name='Move Registration' zijn toegevoegd voor Participant."
    );
  }

  // ########################################################################
  // ### SCENARIO E: LINK VOOR PARTICIPANT.SELECTOR.ROW HEEFT JUISTE VELDEN
  // ########################################################################

  /**
   * De toegevoegde 'Move Registration' link heeft alle verwachte velden:
   * name, title, url, qs, class, icon.
   *
   * De URL moet 'civicrm/movereg' zijn en de qs moet 'participantId' bevatten.
   */
  public function testMoveRegistrationLinkHeeftJuisteVelden() {
    $links      = [];
    $mask       = NULL;
    $values     = [];
    $object_id  = 42;

    movereg_civicrm_links('participant.selector.row', 'Participant', $object_id, $links, $mask, $values);

    // Zoek de Move Registration link
    $move_link = NULL;
    foreach ($links as $link) {
      if (isset($link['name']) && $link['name'] === 'Move Registration') {
        $move_link = $link;
        break;
      }
    }

    $this->assertNotNull($move_link, "Move Registration link moet aanwezig zijn.");

    // Controleer de verplichte velden
    $this->assertArrayHasKey('url',   $move_link, "Link moet 'url' hebben.");
    $this->assertArrayHasKey('qs',    $move_link, "Link moet 'qs' hebben.");
    $this->assertArrayHasKey('title', $move_link, "Link moet 'title' hebben.");
    $this->assertArrayHasKey('icon',  $move_link, "Link moet 'icon' hebben.");

    $this->assertSame('civicrm/movereg', $move_link['url'],
      "URL van Move Registration moet 'civicrm/movereg' zijn."
    );
    $this->assertStringContainsString('participantId', $move_link['qs'],
      "De qs-string moet 'participantId' bevatten zodat het participant-ID doorgegeven wordt."
    );

    // Verifieer dat participantId gevuld is in de values-array
    $this->assertArrayHasKey('participantId', $values,
      "Values-array moet na de hook-aanroep 'participantId' bevatten."
    );
    $this->assertSame($object_id, $values['participantId'],
      "participantId in values moet gelijk zijn aan het meegegeven objectId."
    );
  }

  // ########################################################################
  // ### SCENARIO F: LINK OOK TOEGEVOEGD VOOR PARTICIPANT.CONTACT.ROW
  // ########################################################################

  /**
   * movereg_civicrm_links() met op='participant.contact.row' (contact-detailpagina)
   * voegt ook de Move Registration link toe — beide weergaven worden ondersteund.
   */
  public function testLinksVoorParticipantContactRowVoegtOokLinkToe() {
    $links      = [];
    $mask       = NULL;
    $values     = [];

    movereg_civicrm_links('participant.contact.row', 'Participant', 99, $links, $mask, $values);

    $move_link = NULL;
    foreach ($links as $link) {
      if (isset($link['name']) && $link['name'] === 'Move Registration') {
        $move_link = $link;
        break;
      }
    }

    $this->assertNotNull($move_link,
      "Move Registration link moet ook voor op='participant.contact.row' toegevoegd worden."
    );
  }

  // ########################################################################
  // ### SCENARIO G: SEARCHKITTASKS BEVAT PARTICIPANT-TAAK MET CORRECTE VELDEN
  // ########################################################################

  /**
   * movereg_civicrm_searchKitTasks() voegt een 'move_registration' taak toe voor
   * het Participant-entiteit. De taak heeft title, icon en url.
   *
   * Dit is de SearchKit bulk-actie die movereg registreert.
   */
  public function testSearchKitTasksBevatParticipantTaakMetCorrecteVelden() {
    $tasks        = [];
    $check_perms  = FALSE;
    $user_id      = NULL;

    movereg_civicrm_searchKitTasks($tasks, $check_perms, $user_id);

    // De taak moet onder tasks['Participant']['move_registration'] staan
    $this->assertArrayHasKey('Participant', $tasks,
      "Tasks-array moet na de hook een 'Participant' sleutel hebben."
    );
    $this->assertArrayHasKey('move_registration', $tasks['Participant'],
      "Participant-taken moeten 'move_registration' bevatten."
    );

    $taak = $tasks['Participant']['move_registration'];

    $this->assertArrayHasKey('title', $taak, "move_registration taak moet een 'title' hebben.");
    $this->assertArrayHasKey('icon',  $taak, "move_registration taak moet een 'icon' hebben.");
    $this->assertArrayHasKey('url',   $taak, "move_registration taak moet een 'url' hebben.");

    $this->assertNotEmpty($taak['title'],
      "Title van move_registration taak mag niet leeg zijn."
    );
    $this->assertStringContainsString('civicrm/movereg', $taak['url'],
      "URL van move_registration taak moet 'civicrm/movereg' bevatten."
    );
  }

  // ########################################################################
  // ### SCENARIO H: ONBEKENDE OP VOOR PARTICIPANT → GEEN LINK TOEGEVOEGD
  // ########################################################################

  /**
   * movereg_civicrm_links() met objectName='Participant' maar een onbekende op-waarde
   * mag geen link toevoegen. Alleen 'participant.selector.row' en 'participant.contact.row'
   * zijn de toegestane contexten.
   */
  public function testLinksMetOnbekendOpVoorParticipantVoegtGeenLinkToe() {
    $links  = ['bestaande_link' => ['name' => 'test']];
    $mask   = NULL;
    $values = [];

    // Een op-waarde die niet in de toegestane lijst staat
    movereg_civicrm_links('onbekende.op.waarde', 'Participant', 1, $links, $mask, $values);

    // De bestaande link moet er nog zijn
    $this->assertArrayHasKey('bestaande_link', $links,
      'Bestaande links mogen niet verwijderd worden bij een onbekende op-waarde.'
    );

    // Er mag geen Move Registration link bijgekomen zijn
    $move_link = NULL;
    foreach ($links as $link) {
      if (isset($link['name']) && $link['name'] === 'Move Registration') {
        $move_link = $link;
        break;
      }
    }
    $this->assertNull($move_link,
      "Bij een onbekende op-waarde mag geen 'Move Registration' link toegevoegd worden."
    );
  }

}
