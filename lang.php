<?php
/**
 * VMDestek - Language Helper
 * lang/ klasöründen dil dosyalarını yükler
 */

function loadLang($code = 'tr')
{
    $allowed = ['tr', 'en', 'ar'];
    if (!in_array($code, $allowed)) {
        $code = 'tr';
    }

    $file = __DIR__ . '/lang/' . $code . '.json';
    if (!file_exists($file)) {
        $file = __DIR__ . '/lang/tr.json';
    }

    $content = file_get_contents($file);
    $data = json_decode($content, true);

    return $data ?: [];
}

function getSystemLanguage()
{
    try {
        $setting = db()->fetch("SELECT setting_value FROM settings WHERE setting_key = 'language'");
        return $setting ? $setting['setting_value'] : 'tr';
    } catch (Exception $e) {
        return 'tr';
    }
}
