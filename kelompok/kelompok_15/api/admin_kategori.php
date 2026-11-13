<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// FITUR MANAJEMEN KATEGORI - ORANG 2
require_admin();$adminTitle='Manajemen Kategori';$activeAdmin='admin/kategori.html';
$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $st = db()->prepare('SELECT * FROM categories WHERE id=?');
    $st->execute([$editId]);
    $editing = $st->fetch();
}
$rows = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM articles a WHERE a.category_id=c.id) AS article_count '
        . 'FROM categories c ORDER BY c.name'
)->fetchAll();


$view = [];
$view['msg_flash_success'] = [];
if($msg=flash('success')) {
    $item1 = [];
    $item1['msg'] = e($msg);
    $view['msg_flash_success'][] = $item1;
}
$view['msg_flash_error'] = [];
if($msg=flash('error')) {
    $item1 = [];
    $item1['msg2'] = e($msg);
    $view['msg_flash_error'][] = $item1;
}
$view['count_rows'] = count($rows);
$view['rows'] = [];
foreach($rows as $c) {
    $item1 = [];
    $item1['c_name'] = e($c['name']);
    $item1['c_description'] = e($c['description']);
    $item1['c_slug'] = e($c['slug']);
    $item1['int_c_article_count'] = (int)$c['article_count'];
    $item1['statusClass'] = $c['is_active']?'status-active':'status-inactive';
    $item1['c_is_active_Aktif_Nonaktif'] = $c['is_active']?'Aktif':'Nonaktif';
    $item1['int_c_id'] = (int)$c['id'];
    $item1['confirmToggle'] = $c['is_active'] ? 'Nonaktifkan kategori ini?' : 'Aktifkan kembali kategori ini?';
    $item1['csrf_token'] = csrf_token();
    $item1['int_c_id2'] = (int)$c['id'];
    $item1['c_is_active_0_1'] = $c['is_active'] ? 0 : 1;
    $item1['toggleClass'] = $c['is_active'] ? 'text-red-700' : 'text-green-700';
    $item1['c_is_active_Nonaktifkan_Aktifkan'] = $c['is_active'] ? 'Nonaktifkan' : 'Aktifkan';
    $view['rows'][] = $item1;
}
$view['editing_Edit_Kategori_Tambah_Kategori'] = $editing?'Edit Kategori':'Tambah Kategori';
$view['csrf_token2'] = csrf_token();
$view['int_editing_id_0'] = (int)($editing['id']??0);
$view['editing_name'] = e($editing['name']??'');
$view['editing_slug'] = e($editing['slug']??'');
$view['editing_description'] = e($editing['description']??'');
$view['editing'] = [];
if($editing) {
    $item1 = [];
    $view['editing'][] = $item1;
}

json_response([
    'content' => $view,
    'title' => $adminTitle . ' | Pena Kampus CMS',
    'layout' => admin_layout($adminTitle, $activeAdmin),
]);
