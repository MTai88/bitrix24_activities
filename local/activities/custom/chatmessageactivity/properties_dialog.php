<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right" width="40%"><?= GetMessage("MT_CHATMSG_CHAT") ?>:</td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("text", 'message_chat_id', $arCurrentValues['message_chat_id'], Array('rows'=> 1));?>
    </td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("MT_CHATMSG_FROM") ?>:</td>
	<td width="60%">
        <?php
		if ($user->isAdmin())
		{
			echo CBPDocument::ShowParameterField("user", 'message_user_from', $arCurrentValues['message_user_from'], Array('rows'=> 1));
		}
		else
		{
			echo $user->getFullName();
		}
		?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("MT_CHATMSG_TO") ?>:</td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("user", 'message_user_to', $arCurrentValues['message_user_to'], Array('rows'=> 2));?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("MT_CHATMSG_MESSAGE") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("text", 'message_text', $arCurrentValues['message_text'], Array('rows'=> 7))?>
	</td>
	<input type="hidden" name="message_format" value="<?=htmlspecialcharsbx($arCurrentValues['message_format'] ?? '')?>">
</tr>