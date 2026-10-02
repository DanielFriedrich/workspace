<?php if (!defined('SP_APP')) { exit; } ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <p class="footer-name"><?= e(setting('site_name')) ?></p>
      <p><?= e(setting('site_tagline')) ?></p>
    </div>
    <div>
      <h3>Verleih</h3>
      <ul>
        <li><a href="<?= e(url('')) ?>">Equipment entdecken</a></li>
        <li><a href="<?= e(url('kalender.php')) ?>">Belegungskalender</a></li>
        <li><a href="<?= e(url('warenkorb.php')) ?>">Anfragekorb</a></li>
        <li><a href="<?= e(url('info.php')) ?>#bedingungen">Mietbedingungen</a></li>
      </ul>
    </div>
    <div>
      <h3>Kontakt</h3>
      <ul>
        <?php if (setting('contact_email')): ?><li><a href="mailto:<?= e(setting('contact_email')) ?>"><?= icon('mail') ?><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
        <?php if (setting('contact_phone')): ?><li><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= icon('phone') ?><?= e(setting('contact_phone')) ?></a></li><?php endif; ?>
        <li><a href="<?= e(url('info.php')) ?>#abholung"><?= icon('pin') ?>Abholung &amp; Standorte</a></li>
      </ul>
    </div>
    <div>
      <h3>Rechtliches</h3>
      <ul>
        <li><a href="<?= e(url('impressum.php')) ?>">Impressum</a></li>
        <li><a href="<?= e(url('datenschutz.php')) ?>">Datenschutz</a></li>
        <li><a href="<?= e(url('admin/index.php')) ?>"><?= icon('lock') ?>Team-Login</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <span>© <?= date('Y') ?> <?= e(setting('site_name')) ?></span>
    <span>Mini-Verleih für kleine Events · keine Großveranstaltungen</span>
  </div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
