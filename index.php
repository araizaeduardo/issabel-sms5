<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                    |
  | http://www.issabel.org                                                 |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                  |
  +----------------------------------------------------------------------+
*/

include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";

function _moduleContent(&$smarty, $module_name) {
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoSMS.class.php";

    $base_dir = dirname($_SERVER["SCRIPT_FILENAME"]);
    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf, $arrConfModule);

    $templates_dir = (isset($arrConf["templates_dir"])) ? $arrConf["templates_dir"] : "themes";
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir."/".$arrConf["theme"];

    $pDB = new paloDB($arrConfModule["dsn_conn_database"]);
    $pSMS = new paloSantoSMS($pDB);
    
    // Ensure tables exist
    $pSMS->createTables();

    $smarty->assign("SAVE_CONFIG", _tr("Save Configuration"));
    $smarty->assign("CONFIG_TITLE", _tr("SMS Configuration"));
    $smarty->assign("API_KEY_LABEL", _tr("Telnyx API Key"));
    $smarty->assign("PHONE_NUMBER_LABEL", _tr("Phone Number"));
    $smarty->assign("SEND_MESSAGE_TITLE", _tr("Send New Message"));
    $smarty->assign("TO_NUMBER_LABEL", _tr("To Number"));
    $smarty->assign("MESSAGE_LABEL", _tr("Message"));
    $smarty->assign("SEND_BUTTON", _tr("Send"));
    $smarty->assign("MESSAGES_TITLE", _tr("Messages"));
    $smarty->assign("ALL_MESSAGES", _tr("All"));
    $smarty->assign("RECEIVED_MESSAGES", _tr("Received"));
    $smarty->assign("SENT_MESSAGES", _tr("Sent"));
    $smarty->assign("PREVIOUS", _tr("Previous"));
    $smarty->assign("NEXT", _tr("Next"));

    $action = getAction();
    $content = "";
    
    switch($action) {
        case "save_config":
            $content = saveConfig($smarty, $module_name, $local_templates_dir, $pSMS);
            break;
        case "send":
            $content = sendMessage($smarty, $module_name, $local_templates_dir, $pSMS);
            break;
        case "mark_read":
            $content = markMessageAsRead($smarty, $module_name, $local_templates_dir, $pSMS);
            break;
        default:
            $content = showDashboard($smarty, $module_name, $local_templates_dir, $pSMS);
            break;
    }

    return $content;
}

function showDashboard($smarty, $module_name, $local_templates_dir, &$pSMS) {
    $config = $pSMS->getConfig();
    $smarty->assign("SHOW_CONFIG", empty($config));
    $smarty->assign("CURRENT_API_KEY", isset($config['api_key']) ? str_repeat('*', 20) : '');
    $smarty->assign("CURRENT_PHONE_NUMBER", isset($config['phone_number']) ? $config['phone_number'] : '');

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Tab handling
    $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
    $direction = null;
    if ($currentTab === 'received') $direction = 'inbound';
    if ($currentTab === 'sent') $direction = 'outbound';

    $messages = $pSMS->getMessages($direction, $limit, $offset);
    $smarty->assign("MESSAGES", $messages);
    $smarty->assign("CURRENT_TAB", $currentTab);
    
    // Calculate pagination
    $totalMessages = count($pSMS->getMessages($direction));
    $totalPages = ceil($totalMessages / $limit);
    $smarty->assign("CURRENT_PAGE", $page);
    $smarty->assign("TOTAL_PAGES", $totalPages);

    return $smarty->fetch("$local_templates_dir/dashboard.tpl");
}

function saveConfig($smarty, $module_name, $local_templates_dir, &$pSMS) {
    $apiKey = getParameter("api_key");
    $phoneNumber = getParameter("phone_number");
    
    if ($pSMS->saveConfig($apiKey, $phoneNumber)) {
        $smarty->assign("mb_title", "Success");
        $smarty->assign("mb_message", _tr("Configuration saved successfully"));
    } else {
        $smarty->assign("mb_title", "Error");
        $smarty->assign("mb_message", $pSMS->errMsg);
    }
    
    return showDashboard($smarty, $module_name, $local_templates_dir, $pSMS);
}

function sendMessage($smarty, $module_name, $local_templates_dir, &$pSMS) {
    $phone = getParameter("phone");
    $message = getParameter("message");
    
    if ($pSMS->sendSMS($phone, $message)) {
        $smarty->assign("mb_title", "Success");
        $smarty->assign("mb_message", _tr("Message sent successfully"));
    } else {
        $smarty->assign("mb_title", "Error");
        $smarty->assign("mb_message", $pSMS->errMsg);
    }
    
    return showDashboard($smarty, $module_name, $local_templates_dir, $pSMS);
}

function markMessageAsRead($smarty, $module_name, $local_templates_dir, &$pSMS) {
    $messageId = getParameter("message_id");
    $pSMS->markAsRead($messageId);
    
    // Return empty for AJAX calls
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        exit;
    }
    
    return showDashboard($smarty, $module_name, $local_templates_dir, $pSMS);
}
