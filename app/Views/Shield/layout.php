<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $this->renderSection('title') ?> - Battletech Campaign</title>

  <!-- Bootstrap & App Styles -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/css/app.css" rel="stylesheet">

  <?= $this->renderSection('pageStyles') ?>
</head>
<body class="bg-dark text-light">

  <!-- Public Header -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary mb-3">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="<?= base_url('/') ?>">
        <img src="<?= base_url('images/logo.png') ?>" alt="Unit Logo" style="height:40px; width:auto;">
      </a>
    </div>
  </nav>

  <!-- Main Content -->
  <main role="main" class="container py-5">
    <?= $this->renderSection('main') ?>
  </main>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <?= $this->renderSection('pageScripts') ?>
</body>
</html>
