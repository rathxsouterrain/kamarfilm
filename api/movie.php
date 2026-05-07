<?php
require_once __DIR__ . '/config.php';

// Ambil ID movie dari URL
$movieId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($movieId <= 0) {
    header('Location: /');
    exit;
}

// Fetch detail movie
$movieData = fetchTmdbData('/movie/' . $movieId, ['append_to_response' => 'credits,videos,similar,recommendations']);

if (!$movieData) {
    header('Location: /');
    exit;
}

// Extract data
$title = $movieData['title'] ?? 'Judul Tidak Diketahui';
$originalTitle = $movieData['original_title'] ?? $title;
$overview = $movieData['overview'] ?? 'Sinopsis tidak tersedia.';
$tagline = $movieData['tagline'] ?? '';
$posterUrl = getPosterUrl($movieData['poster_path'] ?? null, 'w500');
$backdropUrl = getPosterUrl($movieData['backdrop_path'] ?? null, 'original');
$rating = formatRating($movieData['vote_average'] ?? null);
$voteCount = $movieData['vote_count'] ?? 0;
$releaseDate = formatDate($movieData['release_date'] ?? null);
$runtime = $movieData['runtime'] ?? 0;
$budget = $movieData['budget'] ?? 0;
$revenue = $movieData['revenue'] ?? 0;
$status = $movieData['status'] ?? '';
$homepage = $movieData['homepage'] ?? '';
$genres = $movieData['genres'] ?? [];
$cast = array_slice($movieData['credits']['cast'] ?? [], 0, 12);
$crew = $movieData['credits']['crew'] ?? [];
$videos = $movieData['videos']['results'] ?? [];
$similarMovies = array_slice($movieData['similar']['results'] ?? [], 0, 12);
$recommendations = array_slice($movieData['recommendations']['results'] ?? [], 0, 12);

// Cari director
$director = '';
foreach ($crew as $crewMember) {
    if ($crewMember['job'] === 'Director') {
        $director = $crewMember['name'];
        break;
    }
}

// Cari trailer YouTube
$trailer = null;
$teaser = null;
foreach ($videos as $video) {
    if ($video['site'] === 'YouTube') {
        if ($video['type'] === 'Trailer' && !$trailer) {
            $trailer = $video;
        } elseif ($video['type'] === 'Teaser' && !$teaser) {
            $teaser = $video;
        }
    }
}
$videoToPlay = $trailer ?? $teaser;
$videoKey = $videoToPlay['key'] ?? '';

// Get IMDB ID for embed sources
$imdbId = $movieData['imdb_id'] ?? '';

// ============================================
// SHOPEE LINK
// ============================================
$shopeeLink = 'https://s.shopee.co.id/60O8toFDwU';

// ============================================
// SERVER LIST - 35+ SERVER LENGKAP (HIDDEN)
// Server tidak ditampilkan ke UI user
// Sistem akan mencoba otomatis dari urutan teratas
// ============================================
$servers = [];

// Helper untuk tambah server
function addServer(&$servers, $name, $urlTemplate, $imdbId, $movieId, $type = 'iframe') {
    $url = '';
    if (strpos($urlTemplate, '{imdb}') !== false) {
        if (!empty($imdbId)) {
            $url = str_replace('{imdb}', $imdbId, $urlTemplate);
        }
    }
    if (strpos($urlTemplate, '{tmdb}') !== false) {
        if ($movieId > 0) {
            $url = str_replace('{tmdb}', $movieId, $urlTemplate);
        }
    }
    if (!empty($url)) {
        $servers[] = ['name' => $name, 'url' => $url, 'type' => $type];
    }
}

// ===== SERVER UTAMA: api.codespecters.com (NexStream) =====
// NexStream menggunakan TMDB ID dan parameter "apikey"
addServer($servers, 'NexStream HD', 'https://api.codespecters.com/embed/movie/{tmdb}?apikey=nx_0e614ede5c5cefcf40c76b9351c0f727', $imdbId, $movieId);
addServer($servers, 'NexStream Pro', 'https://api.codespecters.com/embed/movie/{tmdb}?apikey=nx_0e614ede5c5cefcf40c76b9351c0f727&quality=1080p', $imdbId, $movieId);
addServer($servers, 'NexStream Stream', 'https://api.codespecters.com/embed/movie/{tmdb}?apikey=nx_0e614ede5c5cefcf40c76b9351c0f727&autoplay=true', $imdbId, $movieId);
addServer($servers, 'NexStream Backup', 'https://api.codespecters.com/embed/movie/{tmdb}?apikey=nx_0e614ede5c5cefcf40c76b9351c0f727&fallback=true', $imdbId, $movieId);
addServer($servers, 'NexStream Alt', 'https://api.codespecters.com/embed/movie/{tmdb}?apikey=nx_0e614ede5c5cefcf40c76b9351c0f727&source=alt', $imdbId, $movieId);

// ===== SERVER ALTERNATIF (Fallback) =====
addServer($servers, 'VidSrc HD', 'https://vidsrc-embed.ru/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'VidSrc Pro', 'https://vidsrc.me/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'Embed.su', 'https://embed.su/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, '2Embed', 'https://www.2embed.cc/embed/{imdb}', $imdbId, $movieId);
addServer($servers, '111Movies', 'https://111movies.net/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'MultiEmbed', 'https://multiembed.mov/directstream.php?video_id={imdb}', $imdbId, $movieId);
addServer($servers, 'AutoEmbed', 'https://autoembed.cc/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'Smashy Stream', 'https://embed.smashystream.com/playere.php?tmdb={tmdb}', $imdbId, $movieId);
addServer($servers, 'MovieAPI', 'https://moviesapi.club/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'Play2Watch', 'https://play2watch.net/embed/{imdb}', $imdbId, $movieId);
addServer($servers, 'StreamFlix', 'https://streamflix.one/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'CineHD', 'https://cinehd.xyz/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'FilmPlay', 'https://filmplay.stream/embed/{imdb}', $imdbId, $movieId);
addServer($servers, 'MovieStream', 'https://moviestream.one/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'WatchFree', 'https://watchfree.tv/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'StreamTape', 'https://streamtape.com/e/{imdb}', $imdbId, $movieId);
addServer($servers, 'DoodStream', 'https://dood.to/e/{imdb}', $imdbId, $movieId);
addServer($servers, 'VidCloud', 'https://vidcloud.pro/embed/{imdb}', $imdbId, $movieId);
addServer($servers, 'MixDrop', 'https://mixdrop.co/e/{imdb}', $imdbId, $movieId);
addServer($servers, 'FlixHQ', 'https://flixhq.to/ajax/movie/episodes/{imdb}', $imdbId, $movieId);
addServer($servers, 'SFlix', 'https://sflix.to/ajax/movie/episodes/{imdb}', $imdbId, $movieId);
addServer($servers, 'FMovies', 'https://fmovies.to/ajax/movie/embed/{imdb}', $imdbId, $movieId);
addServer($servers, 'GoMovies', 'https://gomovies.sx/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'YesMovies', 'https://yesmovies.ag/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'Putlocker', 'https://putlocker.vip/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'Solarmovie', 'https://solarmovie.pe/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, 'Primewire', 'https://www.primewire.tf/embed/movie/{imdb}', $imdbId, $movieId);
addServer($servers, '123Movies', 'https://123moviesfree.net/embed/{imdb}', $imdbId, $movieId);
addServer($servers, 'Bmovies', 'https://bmovies.co/embed/movie/{imdb}', $imdbId, $movieId);

// ===== FALLBACK: YouTube Trailer =====
if (!empty($videoKey)) {
    $servers[] = [
        'name' => 'YouTube Trailer',
        'url' => 'https://www.youtube-nocookie.com/embed/' . $videoKey . '?autoplay=1&rel=0&modestbranding=1&iv_load_policy=3',
        'type' => 'iframe'
    ];
}

// Format runtime
$formattedRuntime = formatRuntime($runtime);

// Page title
$pageTitle = htmlspecialchars($title) . ' - KamarFilm';

// Encode servers untuk JS
$serversJson = json_encode($servers);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0a0a0a;
            --bg-secondary: #111111;
            --bg-card: #1a1a1a;
            --bg-hover: #222222;
            --text: #ffffff;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --border: #2a2a2a;
            --accent: #e5e5e5;
            --accent-hover: #ffffff;
            --star: #fbbf24;
            --danger: #ef4444;
            --success: #22c55e;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-dim); }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: linear-gradient(to bottom, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.8) 60%, transparent 100%);
            padding: 0 4%;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }
        .nav-brand {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -1px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-brand svg { width: 32px; height: 32px; fill: var(--text); }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
        }
        .nav-links a {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--text); }
        .nav-back {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            transition: all 0.3s ease;
        }
        .nav-back:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        .nav-back svg { width: 18px; height: 18px; }

        /* ===== HERO SECTION ===== */
        .hero {
            position: relative;
            min-height: 85vh;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
        }
        .hero-backdrop {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
        }
        .hero-backdrop img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
        }
        .hero-vignette {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                linear-gradient(to top, var(--bg) 0%, transparent 50%),
                linear-gradient(to right, rgba(0,0,0,0.8) 0%, transparent 50%),
                linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 30%);
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 0 4% 80px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 50px;
            align-items: end;
        }

        /* ===== POSTER ===== */
        .detail-poster {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 30px 60px rgba(0,0,0,0.8);
            aspect-ratio: 2/3;
            position: relative;
        }
        .detail-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .poster-play-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }
        .detail-poster:hover .poster-play-overlay { opacity: 1; }
        .poster-play-overlay svg {
            width: 60px;
            height: 60px;
            fill: var(--text);
            filter: drop-shadow(0 4px 20px rgba(0,0,0,0.5));
            transition: transform 0.3s ease;
        }
        .detail-poster:hover .poster-play-overlay svg { transform: scale(1.1); }

        /* ===== MOVIE INFO ===== */
        .movie-detail-info { padding-bottom: 10px; }
        .movie-tagline {
            font-size: 16px;
            font-style: italic;
            color: var(--text-muted);
            margin-bottom: 12px;
        }
        .movie-detail-title {
            font-size: clamp(32px, 4vw, 56px);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 16px;
            letter-spacing: -1.5px;
        }
        .movie-detail-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .meta-item.rating {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.15);
            font-weight: 700;
            color: var(--star);
        }
        .meta-item.rating svg { width: 16px; height: 16px; fill: var(--star); }
        .meta-badge {
            background: rgba(255,255,255,0.1);
            padding: 4px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.15);
            font-size: 12px;
            font-weight: 500;
        }
        .genres-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .genre-tag {
            padding: 6px 16px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            transition: all 0.3s ease;
        }
        .genre-tag:hover { background: rgba(255,255,255,0.2); }
        .movie-detail-overview {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-muted);
            max-width: 700px;
            margin-bottom: 28px;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .movie-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: var(--text);
            color: var(--bg);
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: scale(1.05);
            background: var(--accent-hover);
            box-shadow: 0 10px 40px rgba(255,255,255,0.15);
        }
        .btn-primary svg { width: 20px; height: 20px; fill: currentColor; }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            color: var(--text);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        .btn-secondary svg { width: 20px; height: 20px; }

        /* ===== PLAYER SECTION ===== */
        .player-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 4%;
            transition: all 0.5s ease;
        }
        .section-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }

        /* ===== THEATER MODE - AUTO EXPAND ===== */
        .player-wrapper {
            position: relative;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .player-wrapper.theater-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            padding: 0;
            background: #000;
            max-width: none;
            margin: 0;
        }
        .player-wrapper.theater-mode .player-container {
            border-radius: 0;
            border: none;
            height: 100%;
            aspect-ratio: auto;
        }
        .player-wrapper.theater-mode .player-container iframe {
            border-radius: 0;
        }

        /* Tombol Close Theater Mode */
        .theater-close {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10000;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            font-size: 20px;
        }
        .player-wrapper.theater-mode .theater-close {
            opacity: 1;
            visibility: visible;
        }
        .theater-close:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        /* Status indicator untuk server switching */
        .server-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding: 10px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            color: var(--text-muted);
            min-height: 42px;
        }
        .server-status.hidden { display: none; }
        .server-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 1.5s infinite;
            flex-shrink: 0;
        }
        .server-status-dot.error { background: var(--danger); animation: none; }
        .server-status-dot.searching { background: var(--star); }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .server-status-name {
            font-weight: 600;
            color: var(--text);
        }

        .player-container {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .player-container iframe {
            width: 100%;
            height: 100%;
            border: none;
            position: relative;
            z-index: 1;
        }
        .player-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            color: var(--text-dim);
            cursor: pointer;
            transition: background 0.3s;
            position: relative;
        }
        .player-placeholder:hover { background: var(--bg-hover); }
        .player-placeholder svg { width: 64px; height: 64px; fill: var(--text); }
        .player-placeholder p { font-size: 16px; color: var(--text-muted); }

        /* Lapisan Shopee */
        .shopee-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 10;
            cursor: pointer;
            background: transparent;
        }

        /* ===== INFO GRID ===== */
        .info-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 4% 40px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .info-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }

        /* ===== ROW CONTAINER & ARROWS ===== */
        .row-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .scroll-btn {
            position: absolute;
            z-index: 10;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(0,0,0,0.8);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }
        .row-container:hover .scroll-btn { opacity: 1; }
        .scroll-btn:hover { background: var(--text); color: var(--bg); transform: scale(1.1); }
        .scroll-btn.left { left: -22px; }
        .scroll-btn.right { right: -22px; }

        /* ===== CAST SECTION ===== */
        .cast-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 4%;
            border-top: 1px solid var(--border);
        }
        .section-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title svg { width: 24px; height: 24px; }
        .cast-row {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            padding-bottom: 16px;
            scrollbar-width: none;
            width: 100%;
        }
        .cast-row::-webkit-scrollbar { display: none; }
        .cast-card {
            flex: 0 0 auto;
            width: 100px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease;
            scroll-snap-align: start;
        }
        .cast-card:hover { transform: translateY(-4px); }
        .cast-avatar {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--border);
            margin: 0 auto 10px;
            background: var(--bg-card);
            transition: border-color 0.3s ease;
        }
        .cast-card:hover .cast-avatar { border-color: var(--text-muted); }
        .cast-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .cast-card:hover .cast-avatar img { transform: scale(1.1); }
        .cast-name {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text);
        }
        .cast-role {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ===== MOVIE ROW ===== */
        .movie-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 4%;
            border-top: 1px solid var(--border);
        }
        .movie-row {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            padding-bottom: 16px;
            scrollbar-width: none;
            width: 100%;
        }
        .movie-row::-webkit-scrollbar { display: none; }
        .movie-card-row {
            flex: 0 0 auto;
            width: 180px;
            scroll-snap-align: start;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .movie-card-row:hover { transform: translateY(-8px); }
        .movie-poster-wrap {
            position: relative;
            aspect-ratio: 2/3;
            border-radius: 12px;
            overflow: hidden;
            background: var(--bg-card);
            margin-bottom: 10px;
        }
        .movie-poster-wrap::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 40%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .movie-card-row:hover .movie-poster-wrap::after { opacity: 1; }
        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .movie-card-row:hover .movie-poster { transform: scale(1.08); }
        .movie-rating-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 2;
        }
        .movie-rating-badge svg { width: 12px; height: 12px; fill: var(--star); }
        .movie-title-row {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .movie-meta-row {
            font-size: 12px;
            color: var(--text-dim);
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding: 60px 24px;
            border-top: 1px solid var(--border);
            color: var(--text-dim);
            font-size: 14px;
        }
        .footer-brand {
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .footer-links a {
            color: var(--text-muted);
            font-size: 14px;
            transition: color 0.2s;
        }
        .footer-links a:hover { color: var(--text); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .navbar { height: 60px; padding: 0 16px; }
            .nav-brand { font-size: 22px; }
            .nav-links { display: none; }
            .hero { min-height: auto; padding-top: 60px; }
            .hero-content { grid-template-columns: 1fr; gap: 24px; padding: 40px 16px; }
            .detail-poster { max-width: 200px; margin: 0 auto; }
            .movie-detail-title { font-size: 32px; }
            .movie-detail-overview { font-size: 14px; }
            .movie-actions { flex-direction: column; }
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
            .player-section { padding: 30px 16px; }
            .info-section { padding: 0 16px 30px; }
            .cast-section { padding: 30px 16px; }
            .cast-row { gap: 14px; }
            .cast-card { width: 85px; }
            .cast-avatar { width: 70px; height: 70px; }
            .movie-section { padding: 30px 16px; }
            .movie-card-row { width: 140px; }
            .scroll-btn { display: none; }
            .player-wrapper.theater-mode { border-radius: 0; }
        }
        @media (max-width: 480px) {
            .movie-detail-title { font-size: 28px; }
            .movie-detail-meta { gap: 10px; }
            .cast-card { width: 80px; }
            .cast-avatar { width: 65px; height: 65px; }
            .movie-card-row { width: 130px; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-left">
            <a href="/" class="nav-brand">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                KamarFilm
            </a>
            <ul class="nav-links">
                <li><a href="/">Beranda</a></li>
                <li><a href="/?q=action">Film</a></li>
                <li><a href="/?q=series">Serial TV</a></li>
                <li><a href="/?q=2024">Baru &amp; Populer</a></li>
            </ul>
        </div>
        <a href="/" class="nav-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Kembali
        </a>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-backdrop">
            <img src="<?php echo $backdropUrl; ?>" alt="<?php echo htmlspecialchars($title); ?> backdrop">
        </div>
        <div class="hero-vignette"></div>
        <div class="hero-content">
            <div class="detail-poster">
                <img src="<?php echo $posterUrl; ?>" alt="<?php echo htmlspecialchars($title); ?> poster">
                <div class="poster-play-overlay" id="posterPlayBtn">
                    <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>
            <div class="movie-detail-info">
                <?php if ($tagline): ?>
                <p class="movie-tagline">"<?php echo htmlspecialchars($tagline); ?>"</p>
                <?php endif; ?>
                <h1 class="movie-detail-title"><?php echo htmlspecialchars($title); ?></h1>
                <div class="movie-detail-meta">
                    <span class="meta-item rating">
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <?php echo $rating; ?>
                    </span>
                    <span class="meta-item"><?php echo $releaseDate; ?></span>
                    <span class="meta-item"><?php echo $formattedRuntime; ?></span>
                    <?php if ($status): ?>
                    <span class="meta-badge"><?php echo $status === 'Released' ? 'Rilis' : $status; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($movieData['original_language'])): ?>
                    <span class="meta-badge"><?php echo strtoupper($movieData['original_language']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="genres-list">
                    <?php foreach ($genres as $genre): ?>
                    <span class="genre-tag"><?php echo htmlspecialchars($genre['name']); ?></span>
                    <?php endforeach; ?>
                </div>
                <p class="movie-detail-overview"><?php echo htmlspecialchars($overview); ?></p>
                <div class="movie-actions">
                    <button class="btn-primary" id="watchBtn">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        Tonton Sekarang
                    </button>
                    <button class="btn-secondary" id="trailerBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        Trailer
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Info Grid -->
    <section class="info-section">
        <div class="info-grid">
            <?php if ($director): ?>
            <div class="info-card">
                <div class="info-label">Sutradara</div>
                <div class="info-value"><?php echo htmlspecialchars($director); ?></div>
            </div>
            <?php endif; ?>
            <?php if ($status): ?>
            <div class="info-card">
                <div class="info-label">Status</div>
                <div class="info-value"><?php echo htmlspecialchars($status === 'Released' ? 'Rilis' : $status); ?></div>
            </div>
            <?php endif; ?>
            <?php if ($budget > 0): ?>
            <div class="info-card">
                <div class="info-label">Anggaran</div>
                <div class="info-value"><?php echo formatMoney($budget); ?></div>
            </div>
            <?php endif; ?>
            <?php if ($revenue > 0): ?>
            <div class="info-card">
                <div class="info-label">Pendapatan</div>
                <div class="info-value"><?php echo formatMoney($revenue); ?></div>
            </div>
            <?php endif; ?>
            <div class="info-card">
                <div class="info-label">Jumlah Penilaian</div>
                <div class="info-value"><?php echo number_format($voteCount); ?></div>
            </div>
        </div>
    </section>    <!-- Player Section -->
    <section class="player-section" id="playerSection">
        <div class="section-label">Tonton Film</div>

        <!-- Server Status Indicator (hidden by default, shows when switching) -->
        <div class="server-status hidden" id="serverStatus">
            <span class="server-status-dot" id="statusDot"></span>
            <span id="statusText">Memuat server...</span>
            <span class="server-status-name" id="statusServerName"></span>
        </div>

        <!-- Player Wrapper -->
        <div class="player-wrapper" id="playerWrapper">
            <!-- Tombol Close Theater Mode -->
            <button class="theater-close" id="theaterClose" title="Tutup layar penuh">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="player-container" id="playerContainer">
                <!-- IFRAME - Langsung load dengan poster preview asli dari server embed -->
                <?php if (!empty($servers)): ?>
                <iframe
                    id="videoPlayer"
                    src="<?php echo htmlspecialchars($servers[0]['url']); ?>"
                    title="<?php echo htmlspecialchars($title); ?>"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
                <?php else: ?>
                <div class="player-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                        <line x1="7" y1="2" x2="7" y2="22"></line>
                        <line x1="17" y1="2" x2="17" y2="22"></line>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                    </svg>
                    <p>Video tidak tersedia untuk film ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Cast Section -->
    <?php if (!empty($cast)): ?>
    <section class="cast-section">
        <h2 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Pemeran Utama
        </h2>
        <div class="row-container">
            <button class="scroll-btn left"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
            <div class="cast-row">
                <?php foreach ($cast as $actor):
                    $actorName = $actor['name'] ?? 'Unknown';
                    $actorCharacter = $actor['character'] ?? '';
                    $actorPhoto = getPosterUrl($actor['profile_path'] ?? null, 'w185');
                ?>
                <div class="cast-card">
                    <div class="cast-avatar">
                        <img src="<?php echo $actorPhoto; ?>" alt="<?php echo htmlspecialchars($actorName); ?>" loading="lazy">
                    </div>
                    <div class="cast-name"><?php echo htmlspecialchars($actorName); ?></div>
                    <div class="cast-role"><?php echo htmlspecialchars($actorCharacter); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn right"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Similar Movies -->
    <?php if (!empty($similarMovies)): ?>
    <section class="movie-section">
        <h2 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Film Serupa
        </h2>
        <div class="row-container">
            <button class="scroll-btn left"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
            <div class="movie-row">
                <?php foreach ($similarMovies as $similar):
                    $similarId = $similar['id'] ?? 0;
                    $similarTitle = $similar['title'] ?? 'Judul Tidak Diketahui';
                    $similarPoster = getPosterUrl($similar['poster_path'] ?? null);
                    $similarRating = formatRating($similar['vote_average'] ?? null);
                    $similarYear = !empty($similar['release_date']) ? date('Y', strtotime($similar['release_date'])) : 'N/A';
                ?>
                <a href="/movie/<?php echo $similarId; ?>" class="movie-card-row">
                    <div class="movie-poster-wrap">
                        <img src="<?php echo $similarPoster; ?>" alt="<?php echo htmlspecialchars($similarTitle); ?>" class="movie-poster" loading="lazy">
                        <div class="movie-rating-badge">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo $similarRating; ?>
                        </div>
                    </div>
                    <div class="movie-title-row"><?php echo htmlspecialchars($similarTitle); ?></div>
                    <div class="movie-meta-row"><?php echo $similarYear; ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn right"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Recommendations -->
    <?php if (!empty($recommendations)): ?>
    <section class="movie-section">
        <h2 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            Rekomendasi Untuk Anda
        </h2>
        <div class="row-container">
            <button class="scroll-btn left"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
            <div class="movie-row">
                <?php foreach ($recommendations as $rec):
                    $recId = $rec['id'] ?? 0;
                    $recTitle = $rec['title'] ?? 'Judul Tidak Diketahui';
                    $recPoster = getPosterUrl($rec['poster_path'] ?? null);
                    $recRating = formatRating($rec['vote_average'] ?? null);
                    $recYear = !empty($rec['release_date']) ? date('Y', strtotime($rec['release_date'])) : 'N/A';
                ?>
                <a href="/movie/<?php echo $recId; ?>" class="movie-card-row">
                    <div class="movie-poster-wrap">
                        <img src="<?php echo $recPoster; ?>" alt="<?php echo htmlspecialchars($recTitle); ?>" class="movie-poster" loading="lazy">
                        <div class="movie-rating-badge">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo $recRating; ?>
                        </div>
                    </div>
                    <div class="movie-title-row"><?php echo htmlspecialchars($recTitle); ?></div>
                    <div class="movie-meta-row"><?php echo $recYear; ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn right"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-brand">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M8 5v14l11-7z"/></svg>
            KamarFilm
        </div>
        <div class="footer-links">
            <a href="/">Beranda</a>
            <a href="/?q=movies">Film</a>
            <a href="/?q=tv">Serial TV</a>
            <a href="/?q=new">Baru &amp; Populer</a>
        </div>
        <p>KamarFilm &copy; <?php echo date('Y'); ?> — Ditenagai oleh TMDB API. Hak cipta dilindungi.</p>
    </footer>    <!-- Scripts -->
    <script>
    (function() {
        const shopeeLink = <?php echo json_encode($shopeeLink); ?>;
        const servers = <?php echo $serversJson; ?>;
        const movieTitle = <?php echo json_encode($title); ?>;
        const movieImdbId = <?php echo json_encode($imdbId); ?>;

        // Elements
        const videoPlayer = document.getElementById('videoPlayer');
        const watchBtn = document.getElementById('watchBtn');
        const trailerBtn = document.getElementById('trailerBtn');
        const posterPlayBtn = document.getElementById('posterPlayBtn');
        const playerSection = document.getElementById('playerSection');
        const playerWrapper = document.getElementById('playerWrapper');
        const theaterClose = document.getElementById('theaterClose');
        const serverStatus = document.getElementById('serverStatus');
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');
        const statusServerName = document.getElementById('statusServerName');

        let currentServerIndex = 0;
        let fallbackAttempts = 0;
        let isPlaying = false;
        let fallbackTimer = null;
        let serverCheckTimer = null;
        let subtitleLang = 'id'; // default Indonesia

        // ============================================
        // SUBTITLE URL BUILDER - Menggunakan VidSrc subs
        // ============================================
        function getSubtitleUrl(imdbId, lang) {
            // Format VTT subtitle dari VidSrc
            return `https://vidsrc.me/subs/${imdbId}_${lang}.vtt`;
        }

        // ============================================
        // 1. REAL FULLSCREEN API (Native Browser)
        // ============================================
        function enterFullscreen() {
            if (playerWrapper.requestFullscreen) {
                playerWrapper.requestFullscreen();
            } else if (playerWrapper.webkitRequestFullscreen) {
                playerWrapper.webkitRequestFullscreen();
            } else if (playerWrapper.msRequestFullscreen) {
                playerWrapper.msRequestFullscreen();
            }
        }

        function exitFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }

        // Tombol close theater mode
        if (theaterClose) {
            theaterClose.addEventListener('click', function(e) {
                e.stopPropagation();
                exitFullscreen();
            });
        }

        // Tekan ESC untuk keluar fullscreen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                exitFullscreen();
            }
        });

        // ============================================
        // 2. STATUS INDICATOR HELPERS
        // ============================================
        function showStatus(message, type, serverName) {
            if (!serverStatus) return;
            serverStatus.classList.remove('hidden');
            statusText.textContent = message;
            statusServerName.textContent = serverName || '';

            statusDot.classList.remove('error', 'searching');
            if (type === 'error') {
                statusDot.classList.add('error');
            } else if (type === 'searching') {
                statusDot.classList.add('searching');
            }
        }

        function hideStatus() {
            if (!serverStatus) return;
            serverStatus.classList.add('hidden');
        }

        function showSuccess(serverName) {
            showStatus('Video dimuat dari', 'success', serverName);
            setTimeout(hideStatus, 4000);
        }

        // ============================================
        // 3. LOAD SERVER DENGAN SUBTITLE
        // ============================================
        function loadServer(index, autoplay = false) {
            if (!videoPlayer || !servers[index]) return;

            let url = servers[index].url;

            // Tambah subtitle parameter jika ada IMDB ID dan bukan YouTube
            if (movieImdbId && !url.includes('youtube') && !url.includes('sub.info')) {
                const subUrl = getSubtitleUrl(movieImdbId, subtitleLang);
                const sep = url.includes('?') ? '&' : '?';
                url += sep + 'sub.info=' + encodeURIComponent(subUrl);
            }

            // Tambah autoplay parameter jika diminta
            if (autoplay && !url.includes('autoplay') && !url.includes('autoPlay')) {
                const sep = url.includes('?') ? '&' : '?';
                url += sep + 'autoplay=1';
            }

            videoPlayer.src = url;
            console.log('Loading server:', servers[index].name, url);
        }

        // ============================================
        // 4. SHOPEE + PLAY + FULLSCREEN
        // ============================================
        function startPlayback() {
            // 1. Buka Shopee di tab baru
            window.open(shopeeLink, '_blank', 'noopener,noreferrer');

            // 2. Load video dengan autoplay dan subtitle
            isPlaying = true;
            loadServer(currentServerIndex, true);

            // 3. Masuk REAL fullscreen
            setTimeout(() => {
                enterFullscreen();
            }, 500);
        }

        // Klik pada iframe (gunakan overlay untuk tangkap klik pertama)
        if (videoPlayer) {
            // Buat overlay sementara untuk tangkap klik pertama
            const clickOverlay = document.createElement('div');
            clickOverlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;z-index:100;cursor:pointer;background:transparent;';
            clickOverlay.id = 'firstClickOverlay';
            clickOverlay.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                startPlayback();
                // Hilangkan overlay setelah klik pertama
                this.remove();
            });

            // Tambahkan overlay ke playerContainer
            const playerContainer = document.getElementById('playerContainer');
            if (playerContainer) {
                playerContainer.style.position = 'relative';
                playerContainer.appendChild(clickOverlay);
            }
        }

        // ============================================
        // 5. WATCH NOW + FULLSCREEN
        // ============================================
        function watchNow() {
            // Scroll ke player
            if (playerSection) {
                playerSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // Mulai playback setelah scroll
            setTimeout(() => {
                startPlayback();
            }, 800);
        }

        if (watchBtn) watchBtn.addEventListener('click', watchNow);
        if (posterPlayBtn) posterPlayBtn.addEventListener('click', watchNow);

        if (trailerBtn) {
            trailerBtn.addEventListener('click', function() {
                let trailerIndex = servers.findIndex(s => s.name.includes('Trailer') || s.name.includes('YouTube'));
                if (trailerIndex >= 0) {
                    currentServerIndex = trailerIndex;
                    fallbackAttempts = 0;
                    if (videoPlayer) {
                        videoPlayer.src = servers[trailerIndex].url;
                    }
                }
                if (playerSection) {
                    playerSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                setTimeout(() => enterFullscreen(), 800);
            });
        }

        // ============================================
        // 6. SMART AUTO-FALLBACK SYSTEM
        // ============================================
        function setupAutoFallback() {
            if (!videoPlayer || servers.length <= 1) {
                if (servers.length === 1) {
                    showSuccess(servers[0].name);
                }
                return;
            }

            // Reset state
            fallbackAttempts = 0;
            currentServerIndex = 0;

            // Tampilkan status awal
            showStatus('Menghubungkan ke server...', 'searching', servers[0].name);

            // Setiap kali iframe load, cek apakah berhasil
            videoPlayer.onload = function() {
                clearTimeout(serverCheckTimer);

                // Tunggu beberapa detik lalu cek apakah konten valid
                serverCheckTimer = setTimeout(() => {
                    checkServerHealth();
                }, 6000); // Cek setelah 6 detik
            };

            // Initial check untuk server pertama
            serverCheckTimer = setTimeout(() => {
                checkServerHealth();
            }, 8000); // 8 detik timeout untuk server pertama
        }

        function checkServerHealth() {
            if (!videoPlayer) return;

            try {
                // Coba akses contentDocument - jika cross-origin, akan throw error
                // yang berarti server aktif (iframe berhasil load)
                const iframeDoc = videoPlayer.contentDocument || videoPlayer.contentWindow?.document;

                // Jika bisa diakses dan body kosong/sangat pendek, server error
                if (iframeDoc && iframeDoc.body && iframeDoc.body.innerHTML.length < 80) {
                    // Server mengembalikan halaman kosong
                    console.log('Server ' + servers[currentServerIndex].name + ' mengembalikan konten kosong.');
                    tryNextServer();
                } else if (iframeDoc && iframeDoc.body) {
                    // Bisa diakses dan ada konten - cek apakah ada pesan error
                    const bodyText = iframeDoc.body.innerText || iframeDoc.body.textContent || '';
                    if (bodyText.length < 10 || /error|not found|404|tidak tersedia|unavailable/i.test(bodyText)) {
                        console.log('Server ' + servers[currentServerIndex].name + ' error: ' + bodyText.substring(0, 100));
                        tryNextServer();
                    } else {
                        // Server berhasil!
                        showSuccess(servers[currentServerIndex].name);
                    }
                }
            } catch (e) {
                // Cross-origin error = server aktif dan berjalan normal
                // Ini adalah hasil terbaik yang bisa kita harapkan dari iframe eksternal
                console.log('Server ' + servers[currentServerIndex].name + ' aktif (cross-origin load berhasil).');
                showSuccess(servers[currentServerIndex].name);
            }
        }

        function tryNextServer() {
            if (fallbackAttempts >= servers.length - 1) {
                // Sudah mencoba semua server
                showStatus('Semua server tidak tersedia. Menampilkan trailer.', 'error', '');
                setTimeout(hideStatus, 6000);
                return;
            }

            fallbackAttempts++;
            const nextIndex = (currentServerIndex + 1) % servers.length;

            // Update status
            showStatus('Server sebelumnya tidak merespons. Mencoba server lain...', 'searching', servers[nextIndex].name);

            currentServerIndex = nextIndex;
            loadServer(nextIndex, isPlaying);

            console.log('Beralih ke server: ' + servers[nextIndex].name + ' (' + (fallbackAttempts + 1) + '/' + servers.length + ')');

            // Set timer untuk cek server berikutnya
            clearTimeout(serverCheckTimer);
            serverCheckTimer = setTimeout(() => {
                checkServerHealth();
            }, 10000); // 10 detik timeout untuk server berikutnya
        }

        // ============================================
        // 7. PANAH KIRI KANAN PADA BARIS FILM
        // ============================================
        document.querySelectorAll('.row-container').forEach(container => {
            const row = container.querySelector('.movie-row, .cast-row');
            const leftBtn = container.querySelector('.scroll-btn.left');
            const rightBtn = container.querySelector('.scroll-btn.right');

            if (leftBtn && rightBtn && row) {
                leftBtn.addEventListener('click', () => {
                    row.scrollBy({ left: -300, behavior: 'smooth' });
                });
                rightBtn.addEventListener('click', () => {
                    row.scrollBy({ left: 300, behavior: 'smooth' });
                });
            }
        });

        // ============================================
        // 8. INIT - Jalankan sistem saat halaman load
        // ============================================
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupAutoFallback);
        } else {
            setupAutoFallback();
        }

    })();
    </script>
</body>
</html>
