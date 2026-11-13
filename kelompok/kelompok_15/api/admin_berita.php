<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// FITUR CMS BERITA - ORANG 5
require_editor_or_admin();$adminTitle='Manajemen Berita';$activeAdmin='admin/berita.html';
$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$status = $_GET['status'] ?? '';
$category = max(0, (int) ($_GET['kategori'] ?? 0));
$where = ['1=1'];
$params = [];
if(!is_admin()){$where[]='a.faculty_id=?';$params[]=(int)current_user()['faculty_id'];}
if($q!==''){$where[]='a.title LIKE ?';$params[]='%'.$q.'%';}
if(in_array($status,['draft','published','archived'],true)){$where[]='a.status=?';$params[]=$status;}
if($category){$where[]='a.category_id=?';$params[]=$category;}
$whereSql = implode(' AND ', $where);
$st = db()->prepare(
    "SELECT a.*,f.name faculty_name,c.name category_name,u.name author_name "
    . "FROM articles a LEFT JOIN faculties f ON f.id=a.faculty_id "
    . "JOIN categories c ON c.id=a.category_id JOIN users u ON u.id=a.author_id "
    . "WHERE $whereSql ORDER BY a.updated_at DESC"
);
$st->execute($params);
$articles = $st->fetchAll();
$categories = get_categories(false);


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
$view['q'] = e($q);
$view['categories'] = [];
foreach($categories as $c) {
    $item1 = [];
    $item1['int_c_id'] = (int)$c['id'];
    $item1['category_int_c_id_selected'] = $category==(int)$c['id']?'selected':'';
    $item1['c_name'] = e($c['name']);
    $view['categories'][] = $item1;
}
$view['draft_published_archived'] = [];
foreach(['draft','published','archived'] as $s) {
    $item1 = [];
    $item1['s'] = $s;
    $item1['status_s_selected'] = $status===$s?'selected':'';
    $item1['ucfirst_s'] = ucfirst($s);
    $view['draft_published_archived'][] = $item1;
}
$view['articles'] = [];
foreach($articles as $a) {
    $item1 = [];
    $item1['article_image_a_featured_image'] = e(article_image($a['featured_image']));
    $item1['url_admin_berita_form_html_id_a_id'] = e(url('admin/berita-form.html?id='.$a['id']));
    $item1['a_title'] = e($a['title']);
    $item1['a_author_name'] = e($a['author_name']);
    $item1['a_faculty_name_Universitas'] = e($a['faculty_name']?:'Universitas');
    $item1['a_category_name'] = e($a['category_name']);
    $item1['a_status'] = e($a['status']);
    $item1['ucfirst_a_status'] = e(ucfirst($a['status']));
    $item1['date_id_a_updated_at'] = e(date_id($a['updated_at']));
    $item1['url_admin_berita_form_html_id_a_id2'] = e(url('admin/berita-form.html?id='.$a['id']));
    $item1['csrf_token'] = csrf_token();
    $item1['int_a_id'] = (int)$a['id'];
    $view['articles'][] = $item1;
}
$view['articles2'] = [];
if(!$articles) {
    $item1 = [];
    $view['articles2'][] = $item1;
}

json_response([
    'content' => $view,
    'title' => $adminTitle . ' | Pena Kampus CMS',
    'layout' => admin_layout($adminTitle, $activeAdmin),
]);
