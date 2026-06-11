<?php

/**
 * =======================================================================================
 * FUNCTIE-INDEX: movereg.php
 * =======================================================================================
 *   movereg_civicrm_config()          Implements hook_civicrm_config().
 *   movereg_civicrm_xmlMenu()         Implements hook_civicrm_xmlMenu().
 *   movereg_civicrm_links()           Implements hook_civicrm_links().
 *   movereg_civicrm_searchKitTasks()  Implements hook_civicrm_searchKitTasks().
 * =======================================================================================
 */

require_once 'movereg.civix.php';

/**
 * Implements hook_civicrm_config().
 * Dit vertelt CiviCRM om de mappen en templates van onze extensie te laden.
 */
function movereg_civicrm_config(&$config) {
	_movereg_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 * OPLOSSING: We wijzen CiviCRM direct naar ons XML bestand en omzeilen de ontbrekende civix hulpfunctie!
 */
function movereg_civicrm_xmlMenu(&$files) {
	$files[]			= __DIR__ . '/xml/Menu/movereg.xml';
}

/**
 * Implements hook_civicrm_links().
 * Voegt de 'Move Registration' actie toe aan het actiemenu van deelnemers.
 */
function movereg_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {

	$extdebug = 'movereg'; // Kanaal voor centrale debug-config; niveau wordt opgezocht in ozk.debug.config.php

	if ($objectName == 'Participant') {
		$toegestane_ops			= ['participant.selector.row', 'participant.contact.row'];
		
		if (in_array($op, $toegestane_ops)) {
			
//			wachthond($extdebug, 2, "########################################################################");
//			wachthond($extdebug, 2, "### INJECT MOVE REG LINK VIA HOOK_CIVICRM_LINKS", 	 	  "[CONTEXT-MENU]");
//			wachthond($extdebug, 2, "########################################################################");

			$transfer_link			= [
				'name'			=> ts('Move Registration'),
				'title'			=> ts('Move Registration'),
				'url'			=> 'civicrm/movereg',
				'qs'			=> "reset=1&id=%%participantId%%",
				'class'			=> 'action-item crm-hover-button medium-popup move-reg',
				'icon'			=> 'fa-exchange',
			];

			$links[]			= $transfer_link;
			$values['participantId']	= $objectId;
		}
	}
}

/**
 * Implements hook_civicrm_searchKitTasks().
 * Voegt een actie toe aan het bulk-actie menu voor SearchKit.
 */
function movereg_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userID) {

	$extdebug = 'movereg'; // Kanaal voor centrale debug-config; niveau wordt opgezocht in ozk.debug.config.php

//	wachthond($extdebug, 2, "########################################################################");
//	wachthond($extdebug, 2, "### INJECT BULK TASK IN SEARCHKIT", 						 "[SEACRHKIT]");
//	wachthond($extdebug, 2, "########################################################################");

	$tasks['Participant']['move_registration'] = [
		'title'			=> ts('Move Registration'),
		'icon'			=> 'fa-exchange',
		'url'			=> 'civicrm/movereg?reset=1&id=[id]', 
	];
}