{assign var="moduleName" value="OnAppElasticUsers"}
{assign var="selectedServer" value="`$productOptions.1`"}

<span class="oeu-container">

<link href="../modules/servers/{$moduleName}/includes/css/adminArea.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="../modules/servers/{$moduleName}/includes/js/adminArea.js"></script>

<link href="../modules/servers/{$moduleName}/includes/css/chosen/bootstrap-chosen.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.jquery.min.js"></script>

{if $error}
	<div class="errorbox">
		<strong>
			<span class="title">
				{$lang->ErrorTitle}
			</span>
		</strong><br>
		<span class="oeu-error">{$error}</span>
	</div>
{else}
	<table class="form oeu" width="100%" border="0" cellspacing="2" cellpadding="3">
		<!-- server -->
		<tr>
			<td class="fieldlabel" style="width: 150px;">
				{assign var="itemName" value="Server"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<select name="packageconfigoption[1]" required>
					<option value=""></option>
					{foreach from=$servers key=ID item=server}
						{if $productOptions.1 == $ID}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$server->Name}</option>
					{/foreach}
				</select>
				<button class="btn btn-default btn-sm pull-right1" id="oeu-reset-cache">
					{$lang->RefreshServerData}
				</button>
				<input type="hidden" name="reset-server-cache" class="oeu-reset-cache" value="">
				<input type="hidden" name="OnAppElasticUsers_Prev" value="{$productSettingsJSON}">
				<input type="hidden" name="OnAppElasticUsers_Skip" value="" id="OnAppElasticUsers_Skip">
				<input type="hidden" name="OnAppElasticUsers_Server" value="{$selectedServer}">
			</td>
		</tr>
	{if $selectedServer}
		<!-- billing plan default -->
		<tr>
			<td class="fieldlabel" rowspan="3">
				{assign var="itemName" value="BillingPlan"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[BillingPlanDefault]" required>
					<option value=""></option>
					{foreach from=$servers->$selectedServer->BillingPlans key=ID item=name}
						{if $productSettings->BillingPlanDefault == $ID}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription->Default}
				</span>
			</td>
		</tr>
		<!-- biling plan suspended -->
		<tr>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[BillingPlanSuspended]" required>
					<option value=""></option>
					{foreach from=$servers->$selectedServer->BillingPlans key=ID item=name}
						{if $productSettings->BillingPlanSuspended == $ID}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription->Suspended}
				</span>
			</td>
		</tr>
		<!-- biling plan trial -->
		<tr>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[BillingPlanTrial]" required>
					<option value=""></option>
					{foreach from=$servers->$selectedServer->BillingPlans key=ID item=name}
						{if $productSettings->BillingPlanTrial == $ID}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription->Trial}
				</span>
			</td>
		</tr>
		<!-- timezones -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="TimeZone"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[TimeZone]" required>
					<option value=""></option>
					{foreach from=$TimeZones key=ID item=name}
						{if $productSettings->TimeZone == $ID}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
		<!-- locale -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="Locale"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[Locale]" required>
					<option value=""></option>
					{foreach from=$servers->$selectedServer->Locales key=ID item=name}
						{if $productSettings->Locale == $ID}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
		<!-- roles -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="Roles"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[Roles][]" multiple required>
					{foreach from=$servers->$selectedServer->Roles key=ID item=name}
						{if in_array($ID, $productSettings->Roles)}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
		<!-- groups -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="UserGroups"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[UserGroups][]" multiple required1>
					{foreach from=$servers->$selectedServer->UserGroups key=ID item=name}
						{if in_array($ID, $productSettings->UserGroups)}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$ID}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="fieldarea">
				<span class="oeu-info">
					{$lang->CommonSettings}
				</span>
			</td>
		</tr>
		<!-- billing type -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="BillingType"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<select name="OnAppElasticUsers[BillingType]" required>
					<option value=""></option>
					{foreach from=$lang->BillingTypeVariants key=ID item=name}
						{assign var="counter" value="{$ID+1}"}
						{if $productOptions.2 != '' and $productOptions.2 == $counter}
							{assign var="selected" value="selected"}
						{else}
							{assign var="selected" value=""}
						{/if}
						<option value="{$counter}" {$selected}>{$name}</option>
					{/foreach}
				</select>
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
		<!-- due days -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="DueDays"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<input type="number" name="OnAppElasticUsers[DueDays]" min="0" value="{$productOptions.5|default:0}" class="form-control input-sm">
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
		<!-- suspend days -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="SuspendDays"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<input type="number" name="OnAppElasticUsers[SuspendDays]" min="0" value="{$productOptions.3|default:7}" class="form-control input-sm">
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
		<!-- trial days -->
		<tr>
			<td class="fieldlabel">
				{assign var="itemName" value="TrialDays"}
				{assign var="itemDescription" value="`$itemName`Description" }
				{$lang->$itemName}
			</td>
			<td class="fieldarea">
				<input type="number" name="OnAppElasticUsers[TrialDays]" min="0" value="{$productOptions.4|default:5}" class="form-control input-sm">
				<span class="oeu-info">
					{$lang->$itemDescription}
				</span>
			</td>
		</tr>
	{/if}
	</table>
{/if}
</table>
</span>