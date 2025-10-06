<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Battletech Campaign</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="/css/app.css" rel="stylesheet">
  <!-- Standard favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <!-- PNG favicons for better quality -->
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
  <!-- Apple Touch Icon -->
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon-180x180.png">
  <!-- Android Chrome icons -->
  <link rel="icon" type="image/png" sizes="192x192" href="/android-chrome-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="/android-chrome-512x512.png">
</head>
<body class="bg-dark text-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary mb-3">
  <div class="container-fluid">
    <!-- Brand -->
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
        <li class="nav-item">
          <a class="nav-link <?= (service('uri')->getSegment(1) === 'starsystems' ? 'active' : '') ?>"
             href="<?= base_url('/starsystems') ?>">Star Systems</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarPlanets" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Planets
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarPlanets">
            <?php if (!empty($allPlanets)): ?>
              <?php foreach ($allPlanets as $planet): ?>
                <li>
                  <a class="dropdown-item" href="<?= base_url('/planets/show/'.$planet['planet_id']) ?>">
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

      <!-- Right section: date + faction + user info -->
      <ul class="navbar-nav ms-auto align-items-center">

        <!-- Game Date -->
        <li class="nav-item me-3">
          <span class="text-light small"><strong>Date:</strong> <?= esc($gameDate) ?></span>
        </li>

        <!-- Divider -->
        <li class="nav-item">
          <div class="vr text-secondary mx-2"></div>
        </li>

        <!-- Faction Info -->
        <?php if (!empty($currentFaction)): ?>
          <li class="nav-item me-3 d-flex align-items-center">
            <img src="<?= $currentFaction['emblem_path'] ?>"
                 alt="<?= esc($currentFaction['name']) ?>"
                 style="height: 24px; margin-right: 8px;">
            <span class="text-light small"><strong><?= esc($currentFaction['name']) ?></strong></span>
          </li>

          <!-- Divider -->
          <li class="nav-item">
            <div class="vr text-secondary mx-2"></div>
          </li>
        <?php endif; ?>

        <!-- User Dropdown -->
        <?php if (auth()->loggedIn()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= esc(auth()->user()->username ?? auth()->user()->email) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item text-danger" href="<?= base_url('/logout') ?>">Logout</a></li>
            </ul>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<div class="container">
