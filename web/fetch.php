<?php
require_once dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/../src/SpoolReader.php';
require_once dirname(__FILE__) . '/../config/config.php';

function encodeJson($payload)
{
    $options = 0;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $options = $options | JSON_INVALID_UTF8_SUBSTITUTE;
    }

    $json = json_encode($payload, $options);
    if ($json === false) {
        throw new Exception('Unable to encode JSON response: ' . json_last_error_msg());
    }

    return $json;
}

header('Content-type: application/json');

try {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 500) {
        $limit = 500;
    }

    $spoolReader = new SpoolReader(SPOOL_DIR);
    if (isset($_GET['clear']) && $_GET['clear'] == 1) {
        $spoolReader->clear();
    }

    echo encodeJson($spoolReader->run($limit));
} catch (Exception $e) {
    http_response_code(500);

    try {
        echo encodeJson(array(
            'error' => true,
            'message' => $e->getMessage(),
            'spoolDir' => SPOOL_DIR,
        ));
    } catch (Exception $encodingException) {
        echo '{"error":true,"message":"Unable to generate error response"}';
    }
}
