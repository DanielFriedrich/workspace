<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$id = (int) input('id');
$p = $id ? db_one('SELECT * FROM #__products WHERE id = ?', array($id)) : null;
if ($id && !$p) {
    flash('error', 'Gerät nicht gefunden.');
    redirect('admin/produkte.php');
}

if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'delete' && $p) {
        $used = (int) db_value('SELECT COUNT(*) FROM #__booking_items WHERE product_id = ?', array($p['id']));
        if ($used) {
            db_update('products', array('active' => 0, 'updated_at' => now()), 'id = ?', array($p['id']));
            flash('success', 'Das Gerät hat Buchungen und wurde deshalb nur ausgeblendet (Verlauf bleibt erhalten).');
            redirect('admin/produkt.php', array('id' => $p['id']));
        }
        delete_upload($p['image']);
        db_exec("DELETE FROM #__blocks WHERE scope = 'product' AND ref_id = ?", array($p['id']));
        db_exec('DELETE FROM #__products WHERE id = ?', array($p['id']));
        flash('success', 'Gerät gelöscht.');
        redirect('admin/produkte.php');
    }

    if ($action === 'duplicate' && $p) {
        $copy = $p;
        unset($copy['id']);
        $copy['name'] = $p['name'] . ' (Kopie)';
        $copy['image'] = '';
        $copy['active'] = 0;
        $copy['created_at'] = now();
        $copy['updated_at'] = now();
        $newId = db_insert('products', $copy);
        flash('success', 'Kopie angelegt (noch unsichtbar). Bitte anpassen und sichtbar schalten.');
        redirect('admin/produkt.php', array('id' => $newId));
    }

    if ($action === 'save') {
        $data = array(
            'name' => (string) input('name'),
            'category_id' => (int) input('category_id') ?: null,
            'location_id' => (int) input('location_id') ?: null,
            'owner_id' => (int) input('owner_id') ?: null,
            'short_desc' => (string) input('short_desc'),
            'description' => (string) input('description'),
            'specs' => (string) input('specs'),
            'price_day' => parse_money(input('price_day')),
            'deposit' => parse_money(input('deposit')),
            'quantity' => max(1, (int) input('quantity', 1)),
            'internal_note' => (string) input('internal_note'),
            'active' => input('active') === '1' ? 1 : 0,
            'featured' => input('featured') === '1' ? 1 : 0,
            'sort' => (int) input('sort'),
            'updated_at' => now(),
        );
        if ($data['name'] === '') {
            flash('error', 'Bitte einen Namen angeben.');
            $_SESSION['sp_product_form'] = $data;
            redirect('admin/produkt.php', $p ? array('id' => $p['id']) : array());
        }
        if ($p) {
            db_update('products', $data, 'id = ?', array($p['id']));
            $pid = (int) $p['id'];
        } else {
            $data['created_at'] = now();
            $data['image'] = '';
            $pid = db_insert('products', $data);
        }
        $old = $p ? $p['image'] : '';
        if (input('remove_image') === '1' && $old) {
            delete_upload($old);
            db_update('products', array('image' => ''), 'id = ?', array($pid));
            $old = '';
        }
        $file = handle_image_upload('image', $pid);
        if ($file) {
            delete_upload($old);
            db_update('products', array('image' => $file), 'id = ?', array($pid));
        }
        if ($file !== false) {
            flash('success', 'Gerät gespeichert.');
        }
        redirect('admin/produkt.php', array('id' => $pid));
    }
}

$saved = isset($_SESSION['sp_product_form']) ? $_SESSION['sp_product_form'] : null;
unset($_SESSION['sp_product_form']);
$v = function ($k, $d = '') use ($p, $saved) {
    if ($saved && array_key_exists($k, $saved)) {
        return $saved[$k];
    }
    return $p && isset($p[$k]) ? $p[$k] : $d;
};
$full = $p ? product_find($p['id'], false) : null;

// Belegung der nächsten 6 Wochen
$bookings = $p ? db_all(
    "SELECT b.*, bi.qty FROM #__booking_items bi JOIN #__bookings b ON b.id = bi.booking_id
      WHERE bi.product_id = ? AND b.end_date >= ? AND b.status IN ('requested', 'offered', 'confirmed', 'picked_up') ORDER BY b.start_date LIMIT 20",
    array($p['id'], today())
) : array();
$blocks = $p ? db_all("SELECT * FROM #__blocks WHERE end_date >= ? AND ((scope = 'product' AND ref_id = ?) OR (scope = 'location' AND ref_id = ?) OR scope = 'all') ORDER BY start_date",
    array(today(), $p['id'], (int) $p['location_id'])) : array();

admin_header($p ? $p['name'] : 'Neues Gerät', 'produkte');
?>
<a class="link-btn back" href="<?= e(url('admin/produkte.php')) ?>"><?= icon('arrow-l') ?>Alle Geräte</a>
<div class="admin-cols">
  <form class="card-admin" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <div class="form-grid">
      <label class="field field-wide"><span>Name *</span><input type="text" name="name" value="<?= e($v('name')) ?>" required maxlength="160"></label>
      <label class="field"><span>Kategorie</span><select name="category_id"><option value="">–</option><?php foreach (categories_all() as $c): ?><?= opt($c['id'], $v('category_id'), $c['name']) ?><?php endforeach; ?></select></label>
      <label class="field"><span>Standort</span><select name="location_id"><option value="">nach Absprache</option><?php foreach (locations_all(false) as $l): ?><?= opt($l['id'], $v('location_id'), $l['name']) ?><?php endforeach; ?></select></label>
      <label class="field"><span>Preis pro Tag (€) *</span><input type="text" name="price_day" inputmode="decimal" value="<?= e(number_format((float) $v('price_day', 0), 2, ',', '')) ?>" required></label>
      <label class="field"><span>Kaution pro Stück (€)</span><input type="text" name="deposit" inputmode="decimal" value="<?= e(number_format((float) $v('deposit', 0), 2, ',', '')) ?>"></label>
      <label class="field"><span>Anzahl im Pool</span><input type="number" name="quantity" min="1" max="999" value="<?= (int) $v('quantity', 1) ?>"></label>
      <label class="field"><span>Besitzer / Verantwortlich</span><select name="owner_id"><option value="">–</option><?php foreach (users_all() as $u): ?><?= opt($u['id'], $v('owner_id', $p ? '' : current_user()['id']), $u['name']) ?><?php endforeach; ?></select></label>
      <label class="field field-wide"><span>Kurzbeschreibung (Katalogkarte)</span><input type="text" name="short_desc" value="<?= e($v('short_desc')) ?>" maxlength="255"></label>
      <label class="field field-wide"><span>Beschreibung</span><textarea name="description" rows="6"><?= e($v('description')) ?></textarea><small class="muted">Absätze mit Leerzeile, Listen mit „- “, **fett**.</small></label>
      <label class="field field-wide"><span>Technische Daten</span><textarea name="specs" rows="5" placeholder="Leistung: 1500 W&#10;Gewicht: 6 kg"><?= e($v('specs')) ?></textarea><small class="muted">Eine Angabe pro Zeile, Format „Bezeichnung: Wert“.</small></label>
      <label class="field field-wide"><span>Interne Notiz</span><textarea name="internal_note" rows="2" placeholder="Nur fürs Team: Zubehör, Macken, Zustand …"><?= e($v('internal_note')) ?></textarea></label>
      <label class="field"><span>Sortierung</span><input type="number" name="sort" value="<?= (int) $v('sort', 0) ?>"></label>
    </div>
    <div class="check-row">
      <label class="check"><input type="checkbox" name="active" value="1"<?= (int) $v('active', 1) ? ' checked' : '' ?>><span>Auf der Website sichtbar</span></label>
      <label class="check"><input type="checkbox" name="featured" value="1"<?= (int) $v('featured', 0) ? ' checked' : '' ?>><span>Als Highlight oben zeigen</span></label>
    </div>

    <h3>Bild</h3>
    <div class="image-field">
      <div class="image-preview" style="--accent:<?= e(accent($full ? $full : array())) ?>"><?= $full ? product_media($full) : '<div class="media media-placeholder">' . icon('box', 'ph-icon') . '</div>' ?></div>
      <div>
        <label class="field"><span>Neues Bild hochladen (JPG, PNG, WebP – max. 10 MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
        <?php if ($p && $p['image']): ?><label class="check"><input type="checkbox" name="remove_image" value="1"><span>Bild entfernen</span></label><?php endif; ?>
        <p class="muted small">Ohne Bild zeigt die Website ein Icon der Kategorie. Querformat (4:3) sieht am besten aus.</p>
      </div>
    </div>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?>Speichern</button></div>
  </form>

  <?php if ($p): ?>
  <aside class="admin-aside">
    <section class="card-admin">
      <h2><?= icon('calendar') ?>Anstehende Buchungen</h2>
      <?php if (!$bookings): ?><p class="muted">Keine.</p><?php else: ?>
        <ul class="task-list">
          <?php foreach ($bookings as $bk): ?><li><a href="<?= e(url('admin/buchung.php', array('id' => $bk['id']))) ?>"><span class="code"><?= e($bk['code']) ?></span><span class="task-name"><?= (int) $bk['qty'] ?>× · <?= e($bk['customer_name']) ?></span><span class="task-when"><?= e(period_de($bk['start_date'], $bk['end_date'])) ?></span><?= status_badge($bk['status']) ?></a></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
    <section class="card-admin">
      <h2><?= icon('ban') ?>Sperrzeiten</h2>
      <?php if (!$blocks): ?><p class="muted">Keine.</p><?php else: ?>
        <ul class="task-list"><?php foreach ($blocks as $bl): ?><li><span class="task-name"><?= e($bl['reason'] ?: 'Gesperrt') ?> <small class="muted">(<?= $bl['scope'] === 'product' ? 'Gerät' : ($bl['scope'] === 'location' ? 'Standort' : 'alle') ?>)</small></span><span class="task-when"><?= e(period_de($bl['start_date'], $bl['end_date'])) ?></span></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/sperrzeiten.php', array('produkt' => $p['id']))) ?>#neu"><?= icon('plus') ?>Gerät sperren</a>
    </section>
    <section class="card-admin">
      <h2><?= icon('settings') ?>Aktionen</h2>
      <p class="contact-links">
        <a class="btn btn-ghost btn-sm" href="<?= e(url('produkt.php', array('id' => $p['id']))) ?>" target="_blank"><?= icon('eye') ?>Auf Website ansehen</a>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/buchung.php', array('neu' => 1))) ?>"><?= icon('plus') ?>Buchung anlegen</a>
      </p>
      <form method="post" class="inline-forms">
        <?= csrf_field() ?>
        <button class="btn btn-ghost btn-sm" type="submit" name="action" value="duplicate"><?= icon('copy') ?>Duplizieren</button>
      </form>
      <form method="post" data-confirm="Gerät wirklich löschen? Geräte mit Buchungen werden nur ausgeblendet.">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete">
        <button class="btn btn-danger btn-sm" type="submit"><?= icon('trash') ?>Löschen</button>
      </form>
    </section>
  </aside>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
