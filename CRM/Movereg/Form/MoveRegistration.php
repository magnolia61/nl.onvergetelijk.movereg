<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class voor het verplaatsen van een registratie.
 * Bevat AJAX event-selectie, validatie en volledig geautomatiseerde source vervanging.
 */
class CRM_Movereg_Form_MoveRegistration extends CRM_Core_Form {

	public $_participantId;
	public $_contactId;
	public $_oudeEventId;
	public $_oudeEventTitel;
	public $_oudeSource;

	/**
	 * Controleer rechten en haal de huidige deelnemer op.
	 */
	public function preProcess() {
		if (!CRM_Core_Permission::checkActionPermission('CiviEvent', CRM_Core_Action::UPDATE)) {
			CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
		}
		
		$extdebug = 'movereg'; // Kanaal voor centrale debug-config; niveau wordt opgezocht in ozk.debug.config.php
		$this->_participantId		= CRM_Utils_Request::retrieve('id', 'Positive', $this);

		wachthond($extdebug, 2, "########################################################################");
		wachthond($extdebug, 1, "### HAAL BESTAANDE DEELNEMER INFORMATIE OP", "[ALLPART]");
		wachthond($extdebug, 2, "########################################################################");

		$params_participant = [
			'checkPermissions'	=> FALSE,
			'debug'			=> $extdebug,
			'select'		=> [
				'contact_id', 'event_id', 'event_id.title', 'source',
			],
			'where'			=> [
				['id',			'=',	$this->_participantId],
			],
		];
		wachthond($extdebug, 7, 'params_participant',					$params_participant);
		$result_participant		= civicrm_api4('Participant', 'get',	$params_participant);
		wachthond($extdebug, 9, 'result_participant',					$result_participant);

		$this->_contactId		= $result_participant[0]['contact_id']		?? NULL;
		$this->_oudeEventId		= $result_participant[0]['event_id']		?? NULL;
		$this->_oudeEventTitel		= $result_participant[0]['event_id.title']	?? NULL;
		$this->_oudeSource		= $result_participant[0]['source']		?? '';
		
		$this->assign('oudeEventTitel',	$this->_oudeEventTitel);
		$this->assign('oudeSource',	$this->_oudeSource);

		parent::preProcess();
	}

	/**
	 * Bouw het formulier op met uitsluitend het AJAX EntityRef veld.
	 */
	public function buildQuickForm() {
		$extdebug = 'movereg'; // Kanaal voor centrale debug-config; niveau wordt opgezocht in ozk.debug.config.php

		wachthond($extdebug, 2, "########################################################################");
		wachthond($extdebug, 1, "### OPBOUWEN FORMULIER VELDEN EN VALIDATIE", "[ALLPART]");
		wachthond($extdebug, 2, "########################################################################");

		$event_field_params = [
			'entity'		=> 'event',
			'select'		=> ['minimumInputLength' => 0],
			'api'			=> [
				'extra'			=> ['is_active' => 1],
			],
		];

		$this->addEntityRef('nieuw_event_id', ts('Selecteer Nieuw Event'), $event_field_params, TRUE);

		$this->add('hidden', 'participant_id', $this->_participantId);
		$this->assign('elementNames', $this->getRenderableElementNames());

		$this->addButtons([
			[
				'type'		=> 'submit',
				'name'		=> ts('Move Registration'),
				'isDefault'	=> TRUE,
			],
			[
				'type'		=> 'cancel',
				'name'		=> ts('Cancel'),
			]
		]);

		$this->addFormRule(['CRM_Movereg_Form_MoveRegistration', 'formRule'], $this);

		parent::buildQuickForm();
	}

	/**
	 * Validatieregel om te controleren of de deelnemer al in het nieuwe event zit.
	 */
	public static function formRule($fields, $files, $self) {
		$errors				= [];
		$extdebug = 'movereg'; // Kanaal voor centrale debug-config; niveau wordt opgezocht in ozk.debug.config.php

		if (!empty($fields['nieuw_event_id'])) {
			
			wachthond($extdebug, 2, "########################################################################");
			wachthond($extdebug, 1, "### CONTROLEER DUBBELE REGISTRATIE IN NIEUW EVENT", "[ALLPART]");
			wachthond($extdebug, 2, "########################################################################");

			if ($fields['nieuw_event_id'] == $self->_oudeEventId) {
				$errors['nieuw_event_id'] = ts("De deelnemer is momenteel al geregistreerd voor dit event.");
				return $errors;
			}

			$params_check = [
				'checkPermissions'	=> FALSE,
				'debug'			=> $extdebug,
				'select'		=> ['id'],
				'where'			=> [
					['contact_id',	'=',	$self->_contactId],
					['event_id',	'=',	$fields['nieuw_event_id']],
					['is_test',	'=',	FALSE],
				],
			];
			wachthond($extdebug, 7, 'params_check',							$params_check);
			$result_check			= civicrm_api4('Participant', 'get',	$params_check);
			wachthond($extdebug, 9, 'result_check',							$result_check);

			if (count($result_check) > 0) {
				$errors['nieuw_event_id'] = ts("Deze persoon heeft al een bestaande registratie voor dit gekozen event.");
			}
		}

		return empty($errors) ? TRUE : $errors;
	}

	/**
	 * Verwerk de verplaatsing in de database (inclusief automatische source-tekst vervanging en CORE trigger).
	 */
	public function postProcess() {
		$extdebug = 'movereg'; // Kanaal voor centrale debug-config; niveau wordt opgezocht in ozk.debug.config.php
		$waarden			= $this->exportValues();
		
		$participant_id			= $waarden['participant_id']		?? NULL;
		$nieuw_event_id			= $waarden['nieuw_event_id']		?? NULL;

		wachthond($extdebug, 2, "########################################################################");
		wachthond($extdebug, 1, "### HAAL TITEL VAN NIEUW EVENT OP VOOR SLIMME SOURCE VERVANGING", "[ALLPART]");
		wachthond($extdebug, 2, "########################################################################");

		$params_nieuw_event = [
			'checkPermissions'	=> FALSE,
			'debug'			=> $extdebug,
			'select'		=> ['title'],
			'where'			=> [
				['id',		'=',	$nieuw_event_id],
			],
		];
		wachthond($extdebug, 7, 'params_nieuw_event',			$params_nieuw_event);
		$result_nieuw_event		= civicrm_api4('Event', 'get',	$params_nieuw_event);
		wachthond($extdebug, 9, 'result_nieuw_event',			$result_nieuw_event);

		$nieuw_event_titel		= $result_nieuw_event[0]['title']	?? 'Onbekend Event';

		if (!empty($this->_oudeEventTitel)) {
			$slimme_source		= str_replace($this->_oudeEventTitel, $nieuw_event_titel, $this->_oudeSource);
		} else {
			$slimme_source		= $this->_oudeSource;
		}

		wachthond($extdebug, 2, "########################################################################");
		wachthond($extdebug, 1, "### UPDATE DE REGISTRATIE NAAR HET NIEUWE EVENT EN TRIGGER CORE", "[ALLPART]");
		wachthond($extdebug, 2, "########################################################################");

		$data_update = [
			'event_id'				=> $nieuw_event_id,
			'source'				=> $slimme_source,
			'PART_DEEL.trigger_deel'	=> date('Y-m-d H:i:s'),
		];
		$result_update = base_api_wrapper('Participant', (int)$participant_id, $data_update, "MOVEREG_UPDATE", $extdebug);

		if (!empty($result_update)) {
			
			$params_act_type = [
				'checkPermissions'	=> FALSE,
				'debug'			=> $extdebug,
				'select'		=> ['value'],
				'where'			=> [
					['option_group_id:name',	'=',	'activity_type'],
					['name',					'=',	'Event Registration'],
				],
			];
			wachthond($extdebug, 7, 'params_act_type',						$params_act_type);
			$result_act_type		= civicrm_api4('OptionValue', 'get',	$params_act_type);
			wachthond($extdebug, 9, 'result_act_type',						$result_act_type);
			
			$act_type_id			= $result_act_type[0]['value']		?? 5;
			
			$huidige_tijdstempel		= date('Ymd_His');
			$ingelogde_gebruiker		= CRM_Core_Session::singleton()->getLoggedInContactID() ?? $this->_contactId;
			
			$params_activity = [
				'checkPermissions'	=> FALSE,
				'debug'			=> $extdebug,
				'values'		=> [
					'activity_type_id'	=> $act_type_id,
					'source_contact_id'	=> $ingelogde_gebruiker,
					'target_contact_id'	=> [$this->_contactId],
					'source_record_id'	=> $participant_id,
					'subject'		=> "Registration verplaatst naar event {$nieuw_event_id} - [{$huidige_tijdstempel}]",
					'details'		=> "Registratie {$participant_id} is verplaatst. Oorspronkelijk event: {$this->_oudeEventTitel}. Nieuwe source: {$slimme_source}.",
					'status_id:name'	=> 'Completed',
				],
			];
			wachthond($extdebug, 7, 'params_activity',			$params_activity);
			$result_activity		= civicrm_api4('Activity', 'create',	$params_activity);
			wachthond($extdebug, 9, 'result_activity',			$result_activity);

			CRM_Core_Session::setStatus(ts('De registratie is succesvol verplaatst.'), ts('Succes'), 'success');
		} else {
			CRM_Core_Session::setStatus(ts('Fout bij het updaten van de registratie.'), ts('Fout'), 'error');
		}

		parent::postProcess();
	}

	/**
	 * Helper functie om alle renderable elementen te verzamelen voor Smarty.
	 */
	public function getRenderableElementNames() {
		$elementNames			= [];
		foreach ($this->_elements as $element) {
			$label			= $element->getLabel();
			if (!empty($label)) {
				$elementNames[]	= $element->getName();
			}
		}
		return $elementNames;
	}
}