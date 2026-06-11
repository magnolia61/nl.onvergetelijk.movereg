{* +--------------------------------------------------------------------+
 | MoveRegistration Template (Ultra-compacte lay-out)                   |
 +--------------------------------------------------------------------+ *}

<div class="crm-block crm-form-block crm-movereg-form-block">

	{* HEADER *}
	<div class="help">
		{ts}Selecteer het event waarnaar je deze registratie wilt verplaatsen.{/ts}
	</div>

	{if $oudeEventTitel}
		<div class="messages status no-popup">
			<div class="icon inform-icon"></div>
			{ts}Huidig Event:{/ts} <strong>{$oudeEventTitel}</strong>
		</div>
	{/if}

	{* COMPACTE TABEL LAY-OUT *}
	<table class="form-layout-compressed">
		
		{* VELD 1: EVENT SELECTIE *}
		<tr class="crm-movereg-form-block-event">
			<td class="label">{$form.nieuw_event_id.label}</td>
			<td>{$form.nieuw_event_id.html}</td>
		</tr>

	</table>

	{* FOOTER MET KNOPPEN *}
	<div class="crm-submit-buttons">
		{include file="CRM/common/formButtons.tpl" location="bottom"}
	</div>

</div>