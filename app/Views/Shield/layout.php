<?= view('layout/header', ['gameDate' => $gameDate ?? '']) ?>

<main role="main" class="container py-5">
  <?= $this->renderSection('main') ?>
</main>

<?= view('layout/footer') ?>