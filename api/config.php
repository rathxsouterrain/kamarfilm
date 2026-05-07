<?php
// TMDB API Configuration
if (!defined('TMDB_API_KEY')) {
    define('TMDB_API_KEY', 'c42054550bd91e77d5f8ef421403a235');
}
if (!defined('TMDB_BASE_URL')) {
    define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
}
if (!defined('TMDB_IMG_URL')) {
    define('TMDB_IMG_URL', 'https://image.tmdb.org/t/p');
}

// Shopee Monetization Link (Terbaru sesuai permintaan)
$shopeeLink = 'https://s.shopee.co.id/60O8toFDwU';

// Fungsi helper untuk fetch data dari TMDB API
function fetchTmdbData(string $endpoint, array $params = []): ?array {
    // Wajib bahasa Indonesia
    $params = array_merge(['api_key' => TMDB_API_KEY, 'language' => 'id-ID'], $params);
    $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
    return null;
}

function getPosterUrl(?string $path, string $size = 'w500'): string {
    if (empty($path)) {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9Ijc1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMTExIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIyNCIgZmlsbD0iIzQ0NCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
    }
    return TMDB_IMG_URL . '/' . $size . $path;
}

function formatRating(?float $rating): string {
    return $rating ? number_format($rating, 1) : 'N/A';
}

function formatDate(?string $date): string {
    if (empty($date)) return 'Tidak Diketahui';
    $timestamp = strtotime($date);
    return $timestamp ? date('d M Y', $timestamp) : 'Tidak Diketahui';
}

function truncateText(string $text, int $length = 150): string {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function formatRuntime(?int $minutes): string {
    if (empty($minutes) || $minutes <= 0) return 'N/A';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours > 0 ? $hours . 'j ' . $mins . 'm' : $mins . 'm';
}

function formatMoney(?int $amount): string {
    if (empty($amount) || $amount <= 0) return 'N/A';
    if ($amount >= 1000000000) return '$' . number_format($amount / 1000000000, 2) . ' Miliar';
    if ($amount >= 1000000) return '$' . number_format($amount / 1000000, 1) . ' Juta';
    return '$' . number_format($amount);
}
?>
