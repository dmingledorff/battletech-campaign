<?= $this->section('title') ?>Select Your Faction<?= $this->endSection() ?>

<div class="container py-5">
  <h2 class="text-center mb-4">Select Your Faction</h2>
  <p class="text-center text-secondary mb-5">
    Choose your allegiance within the Inner Sphere. Each faction offers unique strengths and philosophies.
  </p>

  <div class="row g-4 justify-content-center">
    <?php foreach ($factions as $faction): ?>
      <div class="col-12 col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100 border-secondary shadow-sm faction-hover"
             style="background-color: #1a1a1a; border: 2px solid <?= esc($faction['color']) ?>;">
          <img src="<?= base_url($faction['emblem_path']) ?>" class="card-img-top p-3"
               alt="<?= esc($faction['name']) ?>" style="height: 140px; object-fit: contain;">

          <div class="card-body text-center text-light">
            <h5 class="card-title mb-2" style="color: <?= esc($faction['color']) ?>;">
              <?= esc($faction['name']) ?>
            </h5>
            <p class="card-text small text-muted"><?= esc($faction['description']) ?></p>
          </div>

          <div class="card-footer text-center">
            <button type="button"
                    class="btn btn-outline-success faction-select-btn"
                    data-faction-id="<?= $faction['faction_id'] ?>"
                    data-name="<?= esc($faction['name']) ?>"
                    data-description="<?= esc($faction['description']) ?>"
                    data-emblem="<?= base_url($faction['emblem_path']) ?>"
                    data-color="<?= esc($faction['color']) ?>"
                    style="border-color: <?= esc($faction['color']) ?>; color: <?= esc($faction['color']) ?>;">
              Join <?= esc($faction['name']) ?>
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="factionConfirmModal" tabindex="-1" aria-labelledby="factionConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light shadow-lg" style="border: 2px solid var(--faction-color, #33ff33); box-shadow: 0 0 20px var(--faction-color, #33ff33);">
      <div class="modal-header">
        <h5 class="modal-title" id="factionConfirmLabel">Confirm Your Allegiance</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="confirmFactionEmblem" src="" alt="Faction Emblem" class="img-fluid mb-3" style="max-height: 120px;">
        <h4 id="confirmFactionName" class="fw-bold text-glow"></h4>
        <p id="confirmFactionDesc" class="small text-secondary"></p>
      </div>
      <div class="modal-footer justify-content-center">
        <form action="<?= base_url('/faction/save') ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="faction_id" id="confirmFactionId">
          <button type="submit" class="btn btn-success">Confirm Faction</button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  .faction-hover {
    transition: all 0.25s ease-in-out;
  }
  .faction-hover:hover {
    transform: scale(1.04);
    box-shadow: 0 0 18px rgba(255,255,255,0.15);
  }
  .text-glow {
    color: #b6ffb6;
    text-shadow: 0 0 8px #00ff00, 0 0 15px #00ff00;
  }
</style>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const factionButtons = document.querySelectorAll('.faction-select-btn');
    const modal = new bootstrap.Modal(document.getElementById('factionConfirmModal'));
    const nameElem = document.getElementById('confirmFactionName');
    const descElem = document.getElementById('confirmFactionDesc');
    const emblemElem = document.getElementById('confirmFactionEmblem');
    const modalContent = document.querySelector('#factionConfirmModal .modal-content');
    const hiddenId = document.getElementById('confirmFactionId');

    factionButtons.forEach(btn => {
      btn.addEventListener('click', e => {
        const name = btn.dataset.name;
        const desc = btn.dataset.description;
        const emblem = btn.dataset.emblem;
        const color = btn.dataset.color;
        const id = btn.dataset.factionId;

        nameElem.textContent = name;
        descElem.textContent = desc;
        emblemElem.src = emblem;
        hiddenId.value = id;
        modalContent.style.setProperty('--faction-color', color);

        modal.show();
      });
    });
  });
</script>
