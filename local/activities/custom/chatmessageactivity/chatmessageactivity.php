<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

class CBPChatMessageActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"MessageChatId" => "",
			"Title" => "",
			"MessageUserFrom" => "",
			"MessageUserTo" => "",
			"MessageText" => "",
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("im"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo(['MessageText' => $this->MessageText]));
		}

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$arMessageUserFrom = CBPHelper::ExtractUsers($this->MessageUserFrom, $documentId, true);
		$arMessageUserTo = CBPHelper::ExtractUsers($this->MessageUserTo, $documentId, false);
		$messageText = $this->getMessageText();

		$ar = array();
		foreach ($arMessageUserTo as $userTo)
		{
			if (in_array($userTo, $ar))
			{
				continue;
			}

			$ar[] = $userTo;
            CIMMessenger::Add([
                'MESSAGE_TYPE' => IM_MESSAGE_PRIVATE,
                'FROM_USER_ID' => $arMessageUserFrom,
                'TO_USER_ID' => $userTo,
                'MESSAGE' => $messageText
            ]);
		}

        if(!empty($this->MessageChatId)) {
            CIMChat::AddMessage([
                "TO_CHAT_ID" => $this->MessageChatId,
                "FROM_USER_ID" => $arMessageUserFrom,
                "MESSAGE" => $messageText,
                "SYSTEM" => "N"
            ]);
        }

		return CBPActivityExecutionStatus::Closed;
	}

	private function getMessageText()
	{
		$messageText = $this->MessageText;
		if (is_array($messageText))
		{
			$messageText = implode(', ', CBPHelper::MakeArrayFlat($messageText));
		}

		$messageText = (string) $messageText;

		if ($messageText)
		{
			$messageText = strip_tags($messageText);
			$messageText = CBPHelper::convertBBtoText($messageText);
		}

		return $messageText;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($arTestProperties["MessageUserTo"]) && empty($arTestProperties["MessageChatId"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageUserTo", "message" => Loc::getMessage("MT_CHATMSG_EMPTY_TO"));
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageChatId", "message" => Loc::getMessage("MT_CHATMSG_EMPTY_TO"));
		}
		if (!array_key_exists("MessageText", $arTestProperties) || $arTestProperties["MessageText"] == '')
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => Loc::getMessage("MT_CHATMSG_EMPTY_MESSAGE"));
		}

		$from = array_key_exists("MessageUserFrom", $arTestProperties) ? $arTestProperties["MessageUserFrom"] : null;
		if ($user && $from !== $user->getBizprocId() && !$user->isAdmin())
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageUserFrom", "message" => Loc::getMessage("MT_CHATMSG_EMPTY_FROM"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues
		));

		$user = new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);
		$fromDefault = $user->isAdmin() ? null : $user->getBizprocId();

		$dialog->setMap(static::getPropertiesMap($documentType, ['fromDefault' => $fromDefault]));

		$dialog->setRuntimeData(array(
			'user' => $user
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$arMap = array(
			"message_chat_id" => "MessageChatId",
			"message_user_from" => "MessageUserFrom",
			"message_user_to" => "MessageUserTo",
			"message_text" => "MessageText",
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
		{
			if ($key == "message_user_from" || $key == "message_user_to")
				continue;
			$arProperties[$value] = (string)$arCurrentValues[$key];
		}

		$user = new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);
		if ($user->isAdmin())
		{
			if (empty($arCurrentValues["message_user_from"]))
				$arProperties["MessageUserFrom"] = null;
			else
			{
				$arProperties["MessageUserFrom"] = CBPHelper::UsersStringToArray($arCurrentValues["message_user_from"], $documentType, $arErrors);
				if (count($arErrors) > 0)
					return false;
			}
		}
		else
		{
			$arProperties["MessageUserFrom"] = $user->getBizprocId();
		}

		$arProperties["MessageUserTo"] = CBPHelper::UsersStringToArray($arCurrentValues["message_user_to"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$arErrors = self::ValidateProperties($arProperties, $user);
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$fromDefault = $context['fromDefault'] ?? null;

		return [
            'MessageChatId' => [
                'Name' => Loc::getMessage('MT_CHATMSG_CHAT'),
                'Description' => Loc::getMessage('MT_CHATMSG_CHAT'),
                'FieldName' => 'message_chat_id',
                'Type' => 'string'
            ],
			'MessageUserFrom' => [
				'Name' => Loc::getMessage('MT_CHATMSG_FROM'),
				'FieldName' => 'message_user_from',
				'Type' => 'user',
				'Default' => $fromDefault
			],
			'MessageUserTo' => [
				'Name' => Loc::getMessage('MT_CHATMSG_TO'),
				'FieldName' => 'message_user_to',
				'Type' => 'user',
				'Multiple' => true,
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType)
			],
			'MessageText' => [
				'Name' => Loc::getMessage('MT_CHATMSG_MESSAGE'),
				'Description' => Loc::getMessage('MT_CHATMSG_MESSAGE'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true
			]
		];
	}
}