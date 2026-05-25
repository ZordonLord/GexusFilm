<?php

require_once __DIR__ . '/../src/bootstrap.php';

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();

$query = trim($_GET['q'] ?? '');
$movies = [];

if ($query) {
    $cacheKey = 'search:' . mb_strtolower($query);
    $data = $repository?->getCachedResponse($cacheKey);

    if (!$data) {
        $data = $client->search($query);

        if ($repository) {
            $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $repository->saveMovieSummaries($data['results'] ?? []);
        }
    }

    $movies = $data['results'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поиск фильмов</title>

    <style>
        body {
            background: #111;
            color: white;
            font-family: Arial;
        }

        form {
            padding: 20px;
        }

        input {
            padding: 10px;
            width: 300px;
            font-size: 16px;
        }

        button {
            padding: 10px;
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
            text-decoration: none;
            color: white;
        }

        img {
            width: 100%;
        }

        .content {
            padding: 10px;
        }
    </style>
</head>

<body>

<form method="GET">
    <input type="text" name="q" placeholder="Найти фильм..." value="<?= htmlspecialchars($query) ?>">
    <button>Поиск</button>
</form>

<div class="grid">

<?php foreach ($movies as $movie): ?>
    <a class="card" href="/movie.php?id=<?= $movie['id'] ?>">
        <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" />

        <div class="content">
            <strong><?= htmlspecialchars($movie['title']) ?></strong><br>
            ⭐ <?= $movie['vote_average'] ?>
        </div>
    </a>
<?php endforeach; ?>

</div>

</body>
</html>
