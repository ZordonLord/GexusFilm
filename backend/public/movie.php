<?php

require_once __DIR__ . '/../src/bootstrap.php';

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    die('Movie ID required');
}

$movie = $repository?->getMovieDetails($id);

if (!$movie) {
    $movie = $client->getMovie($id);

    if ($repository) {
        $repository->saveMovieDetails($movie);
    }
}

$poster = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
$backdrop = 'https://image.tmdb.org/t/p/original' . $movie['backdrop_path'];

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($movie['title']) ?></title>

    <style>
        body {
            margin: 0;
            font-family: Arial;
            background: #111;
            color: white;
        }

        .hero {
            background-image:
                linear-gradient(to top, #111 10%, transparent 80%),
                url('<?= $backdrop ?>');

            background-size: cover;
            background-position: center;

            padding: 80px 40px;
        }

        .container {
            display: flex;
            gap: 30px;
            max-width: 1200px;
            margin: auto;
        }

        .poster {
            width: 300px;
            border-radius: 15px;
        }

        .info {
            max-width: 700px;
        }

        h1 {
            font-size: 42px;
            margin-top: 0;
        }

        .meta {
            margin: 20px 0;
            color: #ccc;
        }

        .overview {
            line-height: 1.6;
            font-size: 18px;
        }

        .rating {
            color: gold;
            font-size: 22px;
            margin-top: 20px;
        }

        .genres {
            margin-top: 20px;
        }

        .genre {
            display: inline-block;
            background: #222;
            padding: 8px 12px;
            border-radius: 20px;
            margin-right: 10px;
        }

        a.back {
            color: white;
            text-decoration: none;
            display: inline-block;
            margin: 20px;
        }
    </style>
</head>

<body>

<a class="back" href="/movies.php">
    ← Назад
</a>

<div class="hero">
    <div class="container">

        <img class="poster" src="<?= $poster ?>">

        <div class="info">

            <h1>
                <?= htmlspecialchars($movie['title']) ?>
            </h1>

            <div class="meta">
                <?= substr($movie['release_date'], 0, 4) ?>
                •
                <?= $movie['runtime'] ?> мин
            </div>

            <div class="rating">
                ⭐ <?= $movie['vote_average'] ?>
            </div>

            <div class="genres">
                <?php foreach ($movie['genres'] as $genre): ?>
                    <span class="genre">
                        <?= htmlspecialchars($genre['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <p class="overview">
                <?= htmlspecialchars($movie['overview']) ?>
            </p>

        </div>

    </div>
</div>

</body>
</html>
