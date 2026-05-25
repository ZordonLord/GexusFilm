<?php

require_once __DIR__ . '/../src/bootstrap.php';

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();
$cacheKey = 'movies:popular';

$data = $repository?->getCachedResponse($cacheKey);

if (!$data) {
    $data = $client->getPopularMovies();

    if ($repository) {
        $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
        $repository->saveMovieSummaries($data['results'] ?? []);
    }
}

$movies = $data['results'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Фильмы</title>

    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .card {
            background: #1c1c1c;
            border-radius: 10px;
            overflow: hidden;
            transition: 0.2s;
            text-decoration: none;
            color: white;
        }

        .card:hover {
            transform: scale(1.03);
        }

        img {
            width: 100%;
        }

        .content {
            padding: 10px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
        }

        .rating {
            color: gold;
            margin-top: 5px;
        }
    </style>
</head>

<body>

<h1 style="padding:20px;">🎬 Популярные фильмы</h1>

<div class="grid">

<?php foreach ($movies as $movie): ?>
    <a class="card" href="/movie.php?id=<?= $movie['id'] ?>">
        <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" />

        <div class="content">
            <div class="title">
                <?= htmlspecialchars($movie['title']) ?>
            </div>

            <div class="rating">
                ⭐ <?= $movie['vote_average'] ?>
            </div>
        </div>
    </a>
<?php endforeach; ?>

</div>

</body>
</html>
