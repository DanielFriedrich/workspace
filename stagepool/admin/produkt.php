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
        db_exec('DELETE FROM #__product_stock WHERE product_id = ?', array($p['id']));
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
        foreach (db_all('SELECT owner_id, location_id, quantity, note, sort FROM #__product_stock WHERE product_id = ?', array($p['id'])) as $s) {
            $s['product_id'] = $newId;
            db_insert('product_stock', $s);
        }
        flash('success', 'Kopie angelegt (noch unsichtbar). Bitte anpassen und sichtbar schalten.');
        redirect('admin/produkt.php', array('id' => $newId));
    }

    if ($action === 'save') {
        $data = array(
            'name' => (string) input('name'),
            'category_id' => (int) input('category_id') ?: null,
            'short_desc' => (string) input('short_desc'),
            'description' => (string) input('description'),
            'specs' => (string) input('specs'),
            'price_internal' => parse_money(input('price_internal')),
            'price_auto' => input('price_auto') === '1' ? 1 : 0,
            'price_day' => parse_money(input('price_day')),
            'deposit' => parse_money(input('deposit')),
            'internal_note' => (string) input('internal_note'),
            'active' => input('active') === '1' ? 1 : 0,
            'featured' => input('featured') === '1' ? 1 : 0,
            'sort' => (int) input('sort'),
            'updated_at' => now(),
        );
        if ($data['price_auto'] && $data['price_internal'] > 0) {
            $data['price_day'] = markup_price($data['price_internal']);
        }
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
            $data['quantity'] = 0;
            $pid = db_insert('products', $data);
        }
        // Bestand je Eigentümer und Standort
        $stockRows = array();
        $sIds = isset($_POST['stock_id']) && is_array($_POST['stock_id']) ? $_POST['stock_id'] : array();
        foreach ($sIds as $i => $sid) {
            $stockRows[] = array(
                'id' => (int) $sid,
                'owner_id' => isset($_POST['stock_owner'][$i]) ? (int) $_POST['stock_owner'][$i] : 0,
                'location_id' => isset($_POST['stock_location'][$i]) ? (int) $_POST['stock_location'][$i] : 0,
                'quantity' => isset($_POST['stock_qty'][$i]) ? (int) $_POST['stock_qty'][$i] : 0,
                'note' => isset($_POST['stock_note'][$i]) ? (string) $_POST['stock_note'][$i] : '',
            );
        }
        foreach (stock_save($pid, $stockRows) as $note) {
            flash('error', $note);
        }
        if (!(int) db_value('SELECT quantity FROM #__products WHERE id = ?', array($pid))) {
            flash('error', 'Achtung: Das Gerät hat keinen Bestand (0 Stück) und kann nicht gebucht werden. Bitte unter „Bestand“ Stückzahl, Eigentümer und Standort eintragen.');
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
if ($p) {
    $locIds = array_keys(product_locations($p['id']));
    $blocks = db_all("SELECT * FROM #__blocks WHERE end_date >= ? AND ((scope = 'product' AND ref_id = ?) OR scope = 'all'" . ($locIds ? " OR (scope = 'location' AND ref_id IN (" . implode(',', array_map('intval', $locIds)) . "))" : '') . ') ORDER BY start_date', array(today(), $p['id']));
}

admin_header($p ? $p['name'] : 'Neues Gerät', 'produkte');
?>
<a class="link-btn back" href="<?= e(url('admin/produkte.php')) ?>"><?= icon('arrow-l') ?>Alle Geräte</a>
<div class="admin-cols">
  <form class="card-admin" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <div class="form-grid">
      <label class="field field-wide"><span>Name *</span><input type="text" name="name" value="<?= e($v('name')) ?>" required maxlength="160"></label>
      <label class="field"><span>Kategorie</span><select name="category_id"><option value="">–</option><?php foreach (categories_all() as $c): ?><?= opt($c['id'], $v('category_id'), $c['name']) ?><?php endforeach; ?></select></label>
      <label class="field"><span>Interner Teampreis pro Tag (€)</span><input type="text" name="price_internal" inputmode="decimal" value="<?= e(number_format((float) $v('price_internal', 0), 2, ',', '')) ?>" data-price-internal><small class="muted">Für Vermietungen innerhalb des Teams. 0 = aus Endkundenpreis zurückrechnen.</small></label>
      <label class="field"><span>Endkundenpreis pro Tag (€) *</span><input type="text" name="price_day" inputmode="decimal" value="<?= e(number_format((float) $v('price_day', 0), 2, ',', '')) ?>" required data-price-customer data-markup="<?= e(customer_markup()) ?>">
        <label class="check check-inline"><input type="checkbox" name="price_auto" value="1"<?= (int) $v('price_auto', $p ? 0 : 1) ? ' checked' : '' ?> data-price-auto><span>automatisch: intern + <?= e(str_replace('.', ',', (string) customer_markup())) ?> % (gerundet auf 0,50 €)</span></label></label>
      <label class="field"><span>Kaution pro Stück (€)</span><input type="text" name="deposit" inputmode="decimal" value="<?= e(number_format((float) $v('deposit', 0), 2, ',', '')) ?>"></label>
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

    <h3>Bestand: Wem gehört was, wo steht es?</h3>
    <p class="muted small">Gleiche Geräte von mehreren Personen oder an mehreren Standorten hier als eigene Zeilen eintragen. Kunden sehen ein Gerät mit der Gesamtzahl; bei jeder Buchung wird automatisch zugeteilt, wessen Exemplare rausgehen.</p>
    <?php
    $stocks = $p ? stock_rows($p['id']) : array();
    $users = users_all();
    $locsAll = locations_all(false);
    $blank = array('id' => 0, 'owner_id' => $p ? '' : current_user()['id'], 'location_id' => '', 'quantity' => '', 'note' => '');
    $stocks[] = $blank;
    if (count($stocks) < 3) {
        $blank['owner_id'] = '';
        $stocks[] = $blank;
    }
    $stockTotal = 0;
    ?>
    <div class="table-wrap table-wrap-flat">
    <table class="table stock-table">
      <thead><tr><th>Eigentümer</th><th>Standort</th><th class="num">Stück</th><th>Notiz</th></tr></thead>
      <tbody>
      <?php foreach ($stocks as $s): $stockTotal += (int) $s['quantity']; ?>
        <tr>
          <td><input type="hidden" name="stock_id[]" value="<?= (int) $s['id'] ?>"><select name="stock_owner[]"><option value="">Gemeinsamer Pool</option><?php foreach ($users as $u): ?><?= opt($u['id'], $s['owner_id'], $u['name']) ?><?php endforeach; ?></select></td>
          <td><select name="stock_location[]"><option value="">nach Absprache</option><?php foreach ($locsAll as $l): ?><?= opt($l['id'], $s['location_id'], $l['name']) ?><?php endforeach; ?></select></td>
          <td class="num"><input class="input-qty" type="number" name="stock_qty[]" min="0" max="999" value="<?= $s['id'] ? (int) $s['quantity'] : '' ?>" placeholder="<?= $s['id'] ? '' : 'neu' ?>"></td>
          <td><input type="text" name="stock_note[]" value="<?= e($s['note']) ?>" placeholder="z. B. Seriennr., Zustand"></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><th colspan="2">Gesamt im Pool</th><th class="num"><?= (int) $stockTotal ?></th><th class="muted small">Stück 0 = Zeile entfernen</th></tr></tfoot>
    </table>
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
