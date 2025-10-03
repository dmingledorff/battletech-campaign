<h2>Planets</h2>
<ul>
  <?php foreach ($planets as $planet): ?>
    <li>
      <a href="<?= '/planets/show/'.$planet['planet_id'] ?>">
        <?= esc($planet['name']) ?> (<?= esc($planet['system_name']) ?>)
      </a>
    </li>
  <?php endforeach; ?>
</ul>
