<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Auth;
use App\Comments;
use App\Post;

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

if ($requestPath !== '/' && is_file(BASE_PATH . '/public' . $requestPath)) {
    return false;
}

Auth::startSession();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'];
$basePath = '/';

if (str_ends_with($requestUri, '/') && $requestUri !== '/') {
    $requestUri = rtrim($requestUri, '/');
}

$user = Auth::user();

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function view(string $template, array $data = []): void
{
    $data = array_merge([
        'appName' => 'Berbagi.or.id',
        'user' => null,
        'flash' => null,
        'q' => '',
        'total' => 0,
        'totalPages' => 1,
        'currentPage' => 1,
        'categoryFilter' => 0,
    ], $data);

    if ($template === 'layout') {
        $data = array_merge($data, ['content' => $data['content'] ?? '']);
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/templates/layout.php';
        return;
    }

    ob_start();
    extract($data, EXTR_SKIP);
    require BASE_PATH . '/templates/' . $template . '.php';
    $content = ob_get_clean();

    view('layout', array_merge($data, ['content' => $content]));
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

$csrfToken = $_SESSION['csrf_token'] ?? null;
if ($csrfToken === null) {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrfToken;
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function csrfCheck(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        flash('error', 'Sesi keamanan tidak valid. Silakan coba lagi.');
        redirect('/');
    }
}

if ($requestUri === '/login' && $method === 'GET') {
    view('auth/login', ['flash' => $flash]);
    exit;
}

if ($requestUri === '/login' && $method === 'POST') {
    csrfCheck();
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = Auth::login($identifier, $password);
    if (isset($result['error'])) {
        flash('error', $result['error']);
        redirect('/login');
    }

    flash('success', 'Selamat datang kembali!');
    redirect('/');
}

if ($requestUri === '/register' && $method === 'GET') {
    view('auth/register', ['flash' => $flash]);
    exit;
}

if ($requestUri === '/register' && $method === 'POST') {
    csrfCheck();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $displayName = trim($_POST['display_name'] ?? '');

    $result = Auth::register($username, $email, $password, $displayName);
    if (isset($result['error'])) {
        flash('error', $result['error']);
        redirect('/register');
    }

    flash('success', 'Akun berhasil dibuat. Selamat berbagi!');
    redirect('/');
}

if ($requestUri === '/logout') {
    Auth::logout();
    flash('success', 'Anda telah keluar.');
    redirect('/');
}

if ($requestUri === '/create' || $requestUri === '/create/') {
    if ($method === 'GET') {
        if (!Auth::check()) {
            flash('error', 'Silakan masuk terlebih dahulu.');
            redirect('/login');
        }
        view('posts/create', [
            'user' => $user,
            'flash' => $flash,
            'categories' => Post::categories(),
            'post' => null,
        ]);
        exit;
    }

    if ($method === 'POST') {
        csrfCheck();
        if (!Auth::check()) {
            flash('error', 'Silakan masuk terlebih dahulu.');
            redirect('/login');
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => Post::slugify($_POST['title'] ?? ''),
            'category_id' => $_POST['category_id'] !== '' ? $_POST['category_id'] : null,
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'body' => trim($_POST['body'] ?? ''),
            'status' => $_POST['status'] === 'published' ? 'published' : 'draft',
        ];

        if ($data['title'] === '' || $data['body'] === '') {
            flash('error', 'Judul dan isi konten wajib diisi.');
            redirect('/create');
        }

        $id = Post::create((int) Auth::id(), $data);
        flash('success', 'Konten berhasil ' . ($data['status'] === 'published' ? 'dipublikasikan' : 'disimpan sebagai draf') . '.');
        redirect('/post/' . $id);
    }
}

if (preg_match('#^/post/(\d+)/edit$#', $requestUri, $m)) {
    $post = Post::find((int) $m[1]);

    if (!$post || !Auth::check() || (int) $post['user_id'] !== (int) Auth::id()) {
        http_response_code(403);
        view('errors/403', ['flash' => $flash]);
        exit;
    }

    if ($method === 'GET') {
        view('posts/create', [
            'user' => $user,
            'flash' => $flash,
            'categories' => Post::categories(),
            'post' => $post,
        ]);
        exit;
    }

    if ($method === 'POST') {
        csrfCheck();
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'category_id' => $_POST['category_id'] !== '' ? $_POST['category_id'] : null,
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'body' => trim($_POST['body'] ?? ''),
            'status' => $_POST['status'] === 'published' ? 'published' : 'draft',
        ];

        if ($data['title'] === '' || $data['body'] === '') {
            flash('error', 'Judul dan isi konten wajib diisi.');
            redirect('/post/' . $post['id'] . '/edit');
        }

        Post::update((int) $post['id'], (int) Auth::id(), $data);
        flash('success', 'Konten berhasil diperbarui.');
        redirect('/post/' . $post['id']);
    }
}

if (preg_match('#^/post/(\d+)$#', $requestUri, $m)) {
    if ($method === 'POST') {
        csrfCheck();
        if (!Auth::check()) {
            flash('error', 'Silakan masuk terlebih dahulu.');
            redirect('/login');
        }

        $postId = (int) $m[1];
        $post = Post::find($postId);
        if (!$post) {
            http_response_code(404);
            view('errors/404', ['flash' => $flash]);
            exit;
        }

        Post::delete($postId, (int) Auth::id());
        flash('success', 'Konten telah dihapus.');
        redirect('/');
    }

    $post = Post::find((int) $m[1]);

    if (!$post) {
        http_response_code(404);
        view('errors/404', ['flash' => $flash]);
        exit;
    }

    if ($post['status'] !== 'published' && (!$user || (int) $user['id'] !== (int) $post['user_id'])) {
        http_response_code(403);
        view('errors/403', ['flash' => $flash]);
        exit;
    }

    if ($post['status'] === 'published') {
        Post::incrementViews((int) $post['id']);
        $post['views']++;
    }

    $comments = (new Comments())->forPost((int) $post['id']);

    view('posts/show', [
        'user' => $user,
        'flash' => $flash,
        'post' => $post,
        'comments' => $comments,
    ]);
    exit;
}

if (preg_match('#^/comment/(\d+)$#', $requestUri, $m) && $method === 'POST') {
    csrfCheck();
    $post = Post::find((int) $m[1]);

    if (!$post || $post['status'] !== 'published') {
        http_response_code(404);
        view('errors/404', ['flash' => $flash]);
        exit;
    }

    $body = trim($_POST['body'] ?? '');
    if ($body === '') {
        flash('error', 'Komentar tidak boleh kosong.');
        redirect('/post/' . $post['id']);
    }

    $comments = new Comments();
    $comments->add((int) $post['id'], Auth::id(), $body);
    flash('success', 'Komentar berhasil dikirim.');
    redirect('/post/' . $post['id']);
}

if ($requestUri === '/category' || str_starts_with($requestUri, '/category/')) {
    $slug = trim($requestUri, '/') === 'category' ? '' : substr($requestUri, strlen('/category/'));

    $categories = Post::categories();
    $selected = null;
    foreach ($categories as $category) {
        if ($category['slug'] === $slug) {
            $selected = $category;
            break;
        }
    }

    $posts = Post::published($selected ? ['category' => $selected['id'], 'limit' => 24] : ['limit' => 24]);

    view('home', [
        'user' => $user,
        'flash' => $flash,
        'posts' => $posts,
        'categories' => $categories,
        'selectedCategory' => $selected,
        'popular' => Post::popular(),
    ]);
    exit;
}

if ($requestUri === '/my-posts') {
    if (!Auth::check()) {
        flash('error', 'Silakan masuk terlebih dahulu.');
        redirect('/login');
    }

    view('posts/mine', [
        'user' => $user,
        'flash' => $flash,
        'posts' => Post::mine((int) Auth::id()),
    ]);
    exit;
}

$q = trim($_GET['q'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$filters = ['limit' => $perPage, 'offset' => ($page - 1) * $perPage];
if ($q !== '') {
    $filters['q'] = $q;
}
if ($category > 0) {
    $filters['category'] = $category;
}

$posts = Post::published($filters);
$total = Post::countPublished($filters);
$totalPages = max(1, (int) ceil($total / $perPage));

view('home', [
    'user' => $user,
    'flash' => $flash,
    'posts' => $posts,
    'categories' => Post::categories(),
    'selectedCategory' => null,
    'popular' => Post::popular(),
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'total' => $total,
    'q' => $q,
    'categoryFilter' => $category,
]);
