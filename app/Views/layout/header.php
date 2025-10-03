<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Battletech Campaign</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary mb-3">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= base_url('/') ?>">
      <img src="<?= base_url('images/logo.png') ?>" alt="Unit Logo" style="height:40px; width:auto;">
    </a>

    <!-- Navbar toggle for mobile -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar links -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">

        <!-- Example: Star Systems link -->
        <li class="nav-item">
          <a class="nav-link <?= (service('uri')->getSegment(1) === 'starsystems' ? 'active' : '') ?>"
             href="<?= '/starsystems' ?>">Star Systems</a>
        </li>

        <!-- Example dropdown with planets -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarPlanets" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Planets
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarPlanets">
            <?php if (!empty($allPlanets)): ?>
              <?php foreach ($allPlanets as $planet): ?>
                <li>
                  <a class="dropdown-item" href="<?= '/planets/show/'.$planet['planet_id'] ?>">
                    <?= esc($planet['name']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><span class="dropdown-item text-muted">No planets</span></li>
            <?php endif; ?>
          </ul>
        </li>

      </ul>
    </div>
  </div>

  <!-- Right-aligned date -->
  <div class="ml-auto text-light" style="white-space: nowrap; padding-right: 1rem;">
    <strong>Date:</strong> <?= esc($gameDate) ?>
  </div>
</nav>

<div class="container">
