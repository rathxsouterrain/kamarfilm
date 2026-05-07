<?php
require_once __DIR__ . '/config.php';

// Ambil parameter pencarian
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Tentukan endpoint berdasarkan pencarian atau trending
if (!empty($searchQuery)) {
    $apiEndpoint = '/search/movie';
    $apiParams = ['query' => $searchQuery, 'page' => $currentPage];
    $pageTitle = 'Pencarian: ' . htmlspecialchars($searchQuery) . ' - KamarFilm';
} else {
    $apiEndpoint = '/trending/movie/day';
    $apiParams = ['page' => $currentPage];
    $pageTitle = 'KamarFilm - Tonton Film Online';
}

// Fetch data dari TMDB
$tmdbData = fetchTmdbData($apiEndpoint, $apiParams);
$movies = $tmdbData['results'] ?? [];
$totalPages = $tmdbData['total_pages'] ?? 1;

// Fetch featured movie (top rated trending) untuk hero banner
$featuredMovie = !empty($movies) ? $movies[0] : null;
$featuredId = $featuredMovie['id'] ?? 0;
$featuredTitle = $featuredMovie['title'] ?? '';
$featuredOverview = $featuredMovie['overview'] ?? '';
$featuredBackdrop = getPosterUrl($featuredMovie['backdrop_path'] ?? null, 'original');
$featuredPoster = getPosterUrl($featuredMovie['poster_path'] ?? null, 'w500');
$featuredRating = formatRating($featuredMovie['vote_average'] ?? null);
$featuredYear = !empty($featuredMovie['release_date']) ? date('Y', strtotime($featuredMovie['release_date'])) : '';

// Fetch genres list untuk mapping
$genresData = fetchTmdbData('/genre/movie/list', []);
$genresMap = [];
if ($genresData && isset($genresData['genres'])) {
    foreach ($genresData['genres'] as $genreItem) {
        $genresMap[$genreItem['id']] = $genreItem['name'];
    }
}

// Fetch now playing untuk section tambahan
$nowPlayingData = fetchTmdbData('/movie/now_playing', ['page' => 1]);
$nowPlayingMovies = $nowPlayingData['results'] ?? [];

// Fetch top rated
$topRatedData = fetchTmdbData('/movie/top_rated', ['page' => 1]);
$topRatedMovies = $topRatedData['results'] ?? [];

// Fetch upcoming
$upcomingData = fetchTmdbData('/movie/upcoming', ['page' => 1]);
$upcomingMovies = $upcomingData['results'] ?? [];
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
        html { scroll-behavior: smooth; }
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
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            background: rgba(10,10,10,0.98);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
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
            position: relative;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--text); }
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0; right: 0;
            height: 2px;
            background: var(--text);
            border-radius: 2px;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .nav-search-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            transition: color 0.2s;
        }
        .nav-search-btn:hover { color: var(--text); }
        .nav-search-btn svg { width: 22px; height: 22px; }

        /* ===== SEARCH OVERLAY ===== */
        .search-overlay {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1001;
            background: rgba(10,10,10,0.98);
            backdrop-filter: blur(20px);
            padding: 20px 4%;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        .search-overlay.active { transform: translateY(0); }
        .search-form {
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 800px;
            margin: 0 auto;
        }
        .search-form input {
            flex: 1;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 20px;
            color: var(--text);
            font-size: 16px;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-form input:focus { border-color: var(--text-muted); }
        .search-form input::placeholder { color: var(--text-dim); }
        .search-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
        }
        .search-close:hover { color: var(--text); }
        .search-close svg { width: 24px; height: 24px; }

        /* ===== HERO BANNER ===== */
        .hero {
            position: relative;
            height: 85vh;
            min-height: 600px;
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
                linear-gradient(to top, var(--bg) 0%, transparent 60%),
                linear-gradient(to right, rgba(0,0,0,0.7) 0%, transparent 50%),
                linear-gradient(to top, rgba(0,0,0,0.4) 0%, transparent 30%);
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 0 4% 80px;
            max-width: 900px;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .hero-badge svg { width: 14px; height: 14px; fill: var(--star); }
        .hero-title {
            font-size: clamp(36px, 5vw, 64px);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 16px;
            letter-spacing: -1.5px;
            text-shadow: 0 4px 30px rgba(0,0,0,0.5);
        }
        .hero-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .hero-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .hero-rating {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 4px 12px;
            border-radius: 8px;
            font-weight: 700;
            color: var(--star);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .hero-rating svg { width: 14px; height: 14px; fill: var(--star); }
        .hero-overview {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-muted);
            max-width: 600px;
            margin-bottom: 28px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .hero-actions {
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

        /* ===== SECTION STYLES ===== */
        .section {
            padding: 40px 4%;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title svg { width: 24px; height: 24px; }
        .section-more {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .section-more:hover { color: var(--text); }
        .section-more svg { width: 16px; height: 16px; }

        /* ===== ROW CONTAINER & SCROLL BUTTONS ===== */
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
        
        @media (max-width: 768px) {
            .scroll-btn { display: none; } /* Hide arrows on touch devices */
        }

        /* ===== MOVIE ROW (Horizontal Scroll) ===== */
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

        /* ===== MOVIE GRID (Search Results) ===== */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            justify-content: center;
        }
        .movie-card-grid {
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
        }
        .movie-card-grid:hover { transform: translateY(-8px); }
        .movie-card-grid .movie-poster-wrap {
            margin-bottom: 10px;
        }

        /* ===== NO RESULTS ===== */
        .no-results {
            text-align: center;
            padding: 100px 24px;
        }
        .no-results svg {
            width: 80px;
            height: 80px;
            color: var(--text-dim);
            margin-bottom: 24px;
        }
        .no-results h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .no-results p { color: var(--text-muted); font-size: 16px; }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .pagination a {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text);
        }
        .pagination a:hover {
            background: var(--bg-hover);
            border-color: var(--text-dim);
        }
        .pagination .current {
            background: var(--text);
            color: var(--bg);
            font-weight: 700;
        }
        .pagination .disabled {
            color: var(--text-dim);
            cursor: not-allowed;
            background: var(--bg-card);
            border: 1px solid var(--border);
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
            .hero { height: 70vh; min-height: 500px; }
            .hero-content { padding: 0 16px 60px; }
            .hero-title { font-size: 32px; }
            .hero-overview { font-size: 14px; }
            .section { padding: 30px 16px; }
            .movie-row { gap: 12px; }
            .movie-card-row { width: 140px; }
            .movie-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }
        }
        @media (max-width: 480px) {
            .hero { height: 60vh; }
            .hero-title { font-size: 28px; }
            .hero-actions { flex-direction: column; }
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
            /* Memperbaiki tata letak (center) pada versi mobile */
            .movie-grid { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 12px; 
                justify-items: center; 
            }
            .movie-card-grid { 
                max-width: 160px; /* Menjaga ukuran tetap presisi agar layout rapi ke tengah */
            }
            .movie-card-row { width: 130px; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-left">
            <a href="/" class="nav-brand">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                KamarFilm
            </a>
            <ul class="nav-links">
                <li><a href="/" class="active">Beranda</a></li>
                <li><a href="/?q=action">Film</a></li>
                <li><a href="/?q=series">Serial TV</a></li>
                <li><a href="/?q=2024">Baru & Populer</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <button class="nav-search-btn" id="searchBtn" title="Pencarian">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </nav>

    <!-- Search Overlay -->
    <div class="search-overlay" id="searchOverlay">
        <form class="search-form" action="/" method="GET">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="q" placeholder="Cari film, serial TV..." value="<?php echo htmlspecialchars($searchQuery); ?>" autocomplete="off" autofocus>
            <button type="button" class="search-close" id="searchClose">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </form>
    </div>

    <?php if (empty($searchQuery)): ?>
    <!-- Hero Banner -->
    <section class="hero">
        <div class="hero-backdrop">
            <img src="<?php echo $featuredBackdrop; ?>" alt="<?php echo htmlspecialchars($featuredTitle); ?>">
        </div>
        <div class="hero-vignette"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Sedang Tren
            </div>
            <h1 class="hero-title"><?php echo htmlspecialchars($featuredTitle); ?></h1>
            <div class="hero-meta">
                <span class="hero-rating">
                    <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php echo $featuredRating; ?>
                </span>
                <?php if ($featuredYear): ?>
                <span class="hero-meta-item"><?php echo $featuredYear; ?></span>
                <?php endif; ?>
                <span class="hero-meta-item">HD</span>
                <span class="hero-meta-item">Film</span>
            </div>
            <p class="hero-overview"><?php echo htmlspecialchars(truncateText($featuredOverview, 200)); ?></p>
            <div class="hero-actions">
                <a href="/movie/<?php echo $featuredId; ?>" class="btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    Tonton Sekarang
                </a>
                <a href="/movie/<?php echo $featuredId; ?>" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    Info Lengkap
                </a>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- Search Header -->
    <div style="height: 70px;"></div>
    <section class="section">
        <div class="section-header">
            <h1 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Hasil Pencarian
            </h1>
            <span style="color: var(--text-muted); font-size: 14px;"><?php echo count($movies); ?> hasil untuk "<?php echo htmlspecialchars($searchQuery); ?>"</span>
        </div>
        <?php if (empty($movies)): ?>
        <div class="no-results">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <h3>Film tidak ditemukan</h3>
            <p>Coba kata kunci lain atau periksa kembali nanti untuk rilis terbaru.</p>
        </div>
        <?php else: ?>
        <div class="movie-grid">
            <?php foreach ($movies as $movie): 
                $movieId = $movie['id'] ?? 0;
                $movieTitle = $movie['title'] ?? 'Judul Tidak Diketahui';
                $moviePoster = getPosterUrl($movie['poster_path'] ?? null);
                $movieRating = formatRating($movie['vote_average'] ?? null);
                $movieYear = !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : 'N/A';
            ?>
            <a href="/movie/<?php echo $movieId; ?>" class="movie-card-grid">
                <div class="movie-poster-wrap">
                    <img src="<?php echo $moviePoster; ?>" alt="<?php echo htmlspecialchars($movieTitle); ?>" class="movie-poster" loading="lazy">
                    <div class="movie-rating-badge">
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <?php echo $movieRating; ?>
                    </div>
                </div>
                <div class="movie-title-row"><?php echo htmlspecialchars($movieTitle); ?></div>
                <div class="movie-meta-row"><?php echo $movieYear; ?> • Film</div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $currentPage - 1; ?>">← Sebelumnya</a>
            <?php else: ?>
                <span class="disabled">← Sebelumnya</span>
            <?php endif; ?>

            <?php 
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            for ($i = $startPage; $i <= $endPage; $i++): 
            ?>
                <?php if ($i == $currentPage): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $currentPage + 1; ?>">Selanjutnya →</a>
            <?php else: ?>
                <span class="disabled">Selanjutnya →</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if (empty($searchQuery)): ?>
    <!-- Trending Now -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                    <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
                Sedang Tren
            </h2>
            <a href="/" class="section-more">Lihat Semua <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></a>
        </div>
        <div class="row-container">
            <button class="scroll-btn left"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
            <div class="movie-row">
                <?php foreach (array_slice($movies, 0, 12) as $movie): 
                    $movieId = $movie['id'] ?? 0;
                    $movieTitle = $movie['title'] ?? 'Judul Tidak Diketahui';
                    $moviePoster = getPosterUrl($movie['poster_path'] ?? null);
                    $movieRating = formatRating($movie['vote_average'] ?? null);
                    $movieYear = !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : 'N/A';
                    $genreNames = [];
                    if (!empty($movie['genre_ids'])) {
                        foreach (array_slice($movie['genre_ids'], 0, 2) as $genreId) {
                            $genreNames[] = $genresMap[$genreId] ?? '';
                        }
                    }
                ?>
                <a href="/movie/<?php echo $movieId; ?>" class="movie-card-row">
                    <div class="movie-poster-wrap">
                        <img src="<?php echo $moviePoster; ?>" alt="<?php echo htmlspecialchars($movieTitle); ?>" class="movie-poster" loading="lazy">
                        <div class="movie-rating-badge">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo $movieRating; ?>
                        </div>
                    </div>
                    <div class="movie-title-row"><?php echo htmlspecialchars($movieTitle); ?></div>
                    <div class="movie-meta-row"><?php echo $movieYear; ?> • <?php echo implode(', ', array_filter($genreNames)) ?: 'Film'; ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn right"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
        </div>
    </section>

    <!-- Now Playing -->
    <?php if (!empty($nowPlayingMovies)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                Sedang Tayang
            </h2>
            <a href="/?q=now" class="section-more">Lihat Semua <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></a>
        </div>
        <div class="row-container">
            <button class="scroll-btn left"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
            <div class="movie-row">
                <?php foreach (array_slice($nowPlayingMovies, 0, 12) as $movie): 
                    $movieId = $movie['id'] ?? 0;
                    $movieTitle = $movie['title'] ?? 'Judul Tidak Diketahui';
                    $moviePoster = getPosterUrl($movie['poster_path'] ?? null);
                    $movieRating = formatRating($movie['vote_average'] ?? null);
                    $movieYear = !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : 'N/A';
                ?>
                <a href="/movie/<?php echo $movieId; ?>" class="movie-card-row">
                    <div class="movie-poster-wrap">
                        <img src="<?php echo $moviePoster; ?>" alt="<?php echo htmlspecialchars($movieTitle); ?>" class="movie-poster" loading="lazy">
                        <div class="movie-rating-badge">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo $movieRating; ?>
                        </div>
                    </div>
                    <div class="movie-title-row"><?php echo htmlspecialchars($movieTitle); ?></div>
                    <div class="movie-meta-row"><?php echo $movieYear; ?> • Film</div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn right"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Top Rated -->
    <?php if (!empty($topRatedMovies)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Rating Tertinggi
            </h2>
            <a href="/?q=top" class="section-more">Lihat Semua <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></a>
        </div>
        <div class="row-container">
            <button class="scroll-btn left"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
            <div class="movie-row">
                <?php foreach (array_slice($topRatedMovies, 0, 12) as $movie): 
                    $movieId = $movie['id'] ?? 0;
                    $movieTitle = $movie['title'] ?? 'Judul Tidak Diketahui';
                    $moviePoster = getPosterUrl($movie['poster_path'] ?? null);
                    $movieRating = formatRating($movie['vote_average'] ?? null);
                    $movieYear = !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : 'N/A';
                ?>
                <a href="/movie/<?php echo $movieId; ?>" class="movie-card-row">
                    <div class="movie-poster-wrap">
                        <img src="<?php echo $moviePoster; ?>" alt="<?php echo htmlspecialchars($movieTitle); ?>" class="movie-poster" loading="lazy">
                        <div class="movie-rating-badge">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo $movieRating; ?>
                        </div>
                    </div>
                    <div class="movie-title-row"><?php echo htmlspecialchars($movieTitle); ?></div>
                    <div class="movie-meta-row"><?php echo $movieYear; ?> • Film</div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn right"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Upcoming -->
    <?php if (!empty($upcomingMovies)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Segera Tayang
            </h2>
            <a href="/?q=upcoming" class="section-more">Lihat Semua <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></a>
        </div>
        <div class="row-container">
            <button class="scroll-btn left"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
            <div class="movie-row">
                <?php foreach (array_slice($upcomingMovies, 0, 12) as $movie): 
                    $movieId = $movie['id'] ?? 0;
                    $movieTitle = $movie['title'] ?? 'Judul Tidak Diketahui';
                    $moviePoster = getPosterUrl($movie['poster_path'] ?? null);
                    $movieRating = formatRating($movie['vote_average'] ?? null);
                    $movieYear = !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : 'N/A';
                ?>
                <a href="/movie/<?php echo $movieId; ?>" class="movie-card-row">
                    <div class="movie-poster-wrap">
                        <img src="<?php echo $moviePoster; ?>" alt="<?php echo htmlspecialchars($movieTitle); ?>" class="movie-poster" loading="lazy">
                        <div class="movie-rating-badge">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo $movieRating; ?>
                        </div>
                    </div>
                    <div class="movie-title-row"><?php echo htmlspecialchars($movieTitle); ?></div>
                    <div class="movie-meta-row"><?php echo $movieYear; ?> • Film</div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn right"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
        </div>
    </section>
    <?php endif; ?>
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
            <a href="/?q=new">Baru & Populer</a>
        </div>
        <p>KamarFilm &copy; <?php echo date('Y'); ?> — Ditenagai oleh TMDB API. Hak cipta dilindungi.</p>
    </footer>

    <!-- Scripts -->
    <script>
        // Logika Navbar
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Logika Pencarian
        const searchBtn = document.getElementById('searchBtn');
        const searchOverlay = document.getElementById('searchOverlay');
        const searchClose = document.getElementById('searchClose');

        searchBtn.addEventListener('click', () => {
            searchOverlay.classList.add('active');
            searchOverlay.querySelector('input').focus();
        });

        searchClose.addEventListener('click', () => {
            searchOverlay.classList.remove('active');
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                searchOverlay.classList.remove('active');
            }
        });

        // Logika Scroll Panah Kiri Kanan
        document.querySelectorAll('.row-container').forEach(container => {
            const row = container.querySelector('.movie-row');
            const leftBtn = container.querySelector('.scroll-btn.left');
            const rightBtn = container.querySelector('.scroll-btn.right');
            
            if(leftBtn && rightBtn && row) {
                leftBtn.addEventListener('click', () => {
                    row.scrollBy({ left: -300, behavior: 'smooth' });
                });
                rightBtn.addEventListener('click', () => {
                    row.scrollBy({ left: 300, behavior: 'smooth' });
                });
            }
        });
    </script>
</body>
</html>
