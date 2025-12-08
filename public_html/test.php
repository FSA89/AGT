<?php

$proxy = "http://2Ef2TS0wt1dlfeQibJd8v2Bm:AlR30EB1FbaPJsGiiFVFnLgQ@80.78.26.205:32223";

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.ipify.org?format=json", // покажет IP, под которым ты выходишь
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_PROXY => $proxy,
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Ошибка прокси: $error";
} else {
    echo "✅ Прокси работает! Ответ: $response";
}
