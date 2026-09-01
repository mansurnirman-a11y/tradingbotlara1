<?php
$c = file_get_contents('https://oandaexchange.online/assets/index-CViVUeS6.js');

function searchAround($content, $needle, $radius = 300) {
    $pos = strpos($content, $needle);
    while ($pos !== false) {
        $start = max(0, $pos - $radius);
        $length = $radius * 2 + strlen($needle);
        echo "=== MATCH FOR {$needle} AT {$pos} ===\n";
        echo substr($content, $start, $length) . "\n\n";
        $pos = strpos($content, $needle, $pos + strlen($needle));
    }
}

searchAround($c, 'api/api-keys');
searchAround($c, 'api/trade');
