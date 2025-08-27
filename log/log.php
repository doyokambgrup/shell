<?php
// === KONFIG TELEGRAM ===
$botToken = "8361957270:AAE2ljibGf22DymnriV3be6e9qk_W0lydxs";
$chatId   = "7568714882";

// Domain asli yang diizinkan
$allowedDomains = ["domainasli.com", "www.domainasli.com"];

// === FUNGSI KIRIM TELEGRAM ===
function sendTelegram($botToken, $chatId, $msg) {
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        "chat_id"    => $chatId,
        "text"       => $msg,
        "parse_mode" => "HTML"
    ];
    $options = [
        "http" => [
            "header"  => "Content-Type: application/x-www-form-urlencoded\r\n",
            "method"  => "POST",
            "content" => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

// === ANTI-COPY LOGGER ===
$currentDomain = $_SERVER['HTTP_HOST'] ?? 'Unknown';
$ip            = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

if (!in_array($currentDomain, $allowedDomains)) {
    $uniqueKey = $currentDomain . '|' . $ip;
    $hashKey   = md5($uniqueKey);
    $logFile   = __DIR__ . "/.anti_copy_log";

    if (!file_exists($logFile)) {
        file_put_contents($logFile, "");
    }

    $logged = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($logged)) {
        $logged = [];
    }

    if (!in_array($hashKey, $logged)) {
        $uri   = $_SERVER['REQUEST_URI'] ?? '';
        $full  = "http://" . $currentDomain . $uri;
        $ua    = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $time  = date("Y-m-d H:i:s");

        $msg = "⚠️ <b>Script Dicuri / Dijalankan di Domain Asing</b>\n"
             . "🌐 Domain: $full\n"
             . "👤 IP: $ip\n"
             . "📱 User Agent: $ua\n"
             . "⏰ Time: $time";
        sendTelegram($botToken, $chatId, $msg);

        file_put_contents($logFile, $hashKey . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
?>
