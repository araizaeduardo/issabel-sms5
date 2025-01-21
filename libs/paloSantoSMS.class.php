<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                    |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                  |
  +----------------------------------------------------------------------+
*/

require_once __DIR__ . '/../vendor/autoload.php';

class paloSantoSMS {
    var $_DB;
    var $errMsg;
    private $telnyxClient;

    function __construct(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
            }
        }

        // Cargar variables de entorno
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        // Configurar cliente Telnyx
        \Telnyx\Telnyx::setApiKey($_ENV['TELNYX_API_KEY']);
    }

    function sendSMS($phone, $message)
    {
        if (!preg_match("/^\+?[0-9]{10,13}$/", $phone)) {
            $this->errMsg = "Invalid phone number format";
            return false;
        }

        if (strlen($message) > 160) {
            $this->errMsg = "Message too long (max 160 characters)";
            return false;
        }

        try {
            // Asegurarse que el número tenga formato internacional
            if (!str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }

            // Enviar SMS usando Telnyx
            $smsMessage = \Telnyx\Message::Create([
                'from' => $_ENV['TELNYX_FROM_NUMBER'],
                'to' => $phone,
                'text' => $message,
            ]);

            // Registrar en la base de datos
            $query = "INSERT INTO sms_log (phone, message, sent_date, message_id, status, direction) VALUES (?, ?, ?, ?, ?, ?)";
            $result = $this->_DB->genQuery($query, array(
                $phone,
                $message,
                date("Y-m-d H:i:s"),
                $smsMessage->id,
                $smsMessage->status,
                'outbound'
            ));

            if (!$result) {
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->errMsg = "Error sending SMS: " . $e->getMessage();
            return false;
        }
    }

    function createTables()
    {
        // Tabla para el registro de SMS
        $queries = array();
        $queries[] = "CREATE TABLE IF NOT EXISTS sms_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone VARCHAR(15),
            message TEXT,
            sent_date DATETIME,
            message_id VARCHAR(255),
            status VARCHAR(50),
            direction VARCHAR(10) DEFAULT 'outbound',
            read_status BOOLEAN DEFAULT 1
        )";
        
        // Tabla para la configuración de Telnyx
        $queries[] = "CREATE TABLE IF NOT EXISTS sms_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            api_key VARCHAR(255),
            phone_number VARCHAR(15),
            webhook_secret VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        foreach ($queries as $query) {
            if (!$this->_DB->genExec($query)) {
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
        }
        
        return true;
    }

    function getConfig() {
        $query = "SELECT * FROM sms_config ORDER BY created_at DESC LIMIT 1";
        $result = $this->_DB->getFirstRow($query);
        return $result;
    }

    function saveConfig($apiKey, $phoneNumber) {
        $query = "INSERT INTO sms_config (api_key, phone_number) VALUES (?, ?)";
        return $this->_DB->genQuery($query, array($apiKey, $phoneNumber));
    }

    function getMessages($direction = null, $limit = 50, $offset = 0) {
        $params = array();
        $whereClause = "";
        
        if ($direction) {
            $whereClause = "WHERE direction = ?";
            $params[] = $direction;
        }
        
        $query = "SELECT * FROM sms_log $whereClause ORDER BY sent_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->_DB->fetchTable($query, true, $params);
    }

    function markAsRead($messageId) {
        $query = "UPDATE sms_log SET read_status = 1 WHERE message_id = ?";
        return $this->_DB->genQuery($query, array($messageId));
    }

    function getUnreadCount() {
        $query = "SELECT COUNT(*) as count FROM sms_log WHERE direction = 'inbound' AND read_status = 0";
        $result = $this->_DB->getFirstRow($query);
        return $result['count'];
    }
}
?>
