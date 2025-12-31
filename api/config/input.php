<?php

function getInput(): array
{
    $data = [];

    // 1. JSON brut
    $raw = file_get_contents("php://input");
    if ($raw !== false && strlen(trim($raw)) > 0) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $data = array_merge($data, $json);
        } else {
            // JSON invalide → on peut logguer ou ignorer
            // error_log("JSON invalide reçu: " . json_last_error_msg());
        }
    }

    // 2. POST classique (form-data / x-www-form-urlencoded)
    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }

    // 3. GET (fallback ultime)
    if (!empty($_GET)) {
        $data = array_merge($data, $_GET);
    }

    // 4. Normalisation (trim toutes les chaînes)
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $data[$key] = trim($value);
        }
    }

    return $data;
}
