<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('MT_CHATMSG_NAME'),
	'DESCRIPTION' => Loc::getMessage('MT_CHATMSG_DESCR'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'ChatMessageActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'interaction',
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'TITLE' => Loc::getMessage('MT_CHATMSG_ROBOT_TITLE'),
		'RESPONSIBLE_PROPERTY' => 'MessageUserTo',
		'GROUP' => ['informingEmployee'],
		'SORT' => 700,
	],
];