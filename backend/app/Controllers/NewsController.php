<?php

class NewsController
{
    public function index()
    {
        $limit = min(max((int) Request::query('limit', 24), 1), 100);
        $category = Validator::clean(Request::query('category', ''));
        $search = Validator::clean(Request::query('search', ''));
        $sql = "SELECT n.id, n.slug, n.title, n.excerpt, n.content, n.image_url AS image, n.status, n.featured, n.published_at, n.reading_time AS readingTime, c.name AS category FROM news n LEFT JOIN news_categories c ON c.id = n.category_id WHERE n.status = 'published'";
        $params = array();

        if ($category !== '') {
            $sql .= ' AND c.slug = ?';
            $params[] = $category;
        }
        if ($search !== '') {
            $sql .= ' AND (n.title LIKE ? OR n.excerpt LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY n.featured DESC, n.published_at DESC LIMIT ' . $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['featured'] = (bool) $row['featured'];
            $row['publishedAt'] = $row['published_at'];
            unset($row['published_at']);
        }
        Response::success($rows);
    }

    public function show($params)
    {
        $stmt = Database::connection()->prepare("SELECT n.id, n.slug, n.title, n.excerpt, n.content, n.image_url AS image, n.published_at AS publishedAt, n.reading_time AS readingTime, c.name AS category FROM news n LEFT JOIN news_categories c ON c.id = n.category_id WHERE n.slug = ? AND n.status = 'published' LIMIT 1");
        $stmt->execute(array($params['slug']));
        $row = $stmt->fetch();
        if (!$row) {
            Response::error('خبر پیدا نشد.', 404);
        }
        $row['id'] = (int) $row['id'];
        Response::success($row);
    }

    public function adminIndex()
    {
        $stmt = Database::connection()->query("SELECT n.id, n.slug, n.title, n.excerpt, n.content, n.image_url AS image, n.status, n.featured, n.published_at AS publishedAt, n.reading_time AS readingTime, c.name AS category FROM news n LEFT JOIN news_categories c ON c.id = n.category_id ORDER BY n.created_at DESC");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['featured'] = (bool) $row['featured'];
        }
        Response::success($rows);
    }

    public function store()
    {
        $data = Request::json();
        $errors = Validator::required($data, array('title', 'slug', 'excerpt', 'content'));
        if (isset($data['slug']) && !Validator::slug($data['slug'])) {
            $errors['slug'] = 'اسلاگ باید انگلیسی، کوچک و با خط تیره باشد.';
        }
        if (!empty($errors)) {
            Response::error('اطلاعات خبر معتبر نیست.', 422, $errors);
        }

        $pdo = Database::connection();
        $categoryId = $this->categoryId(isset($data['category']) ? $data['category'] : 'نجوم');
        $stmt = $pdo->prepare('INSERT INTO news (category_id, author_id, slug, title, excerpt, content, image_url, status, featured, reading_time, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())');
        try {
            $stmt->execute(array(
                $categoryId,
                Auth::user()['id'],
                strtolower(Validator::clean($data['slug'])),
                Validator::clean($data['title']),
                Validator::clean($data['excerpt']),
                trim((string) $data['content']),
                isset($data['image']) ? Validator::clean($data['image']) : '/media/galaxy.jpg',
                isset($data['status']) && $data['status'] === 'draft' ? 'draft' : 'published',
                !empty($data['featured']) ? 1 : 0,
                isset($data['readingTime']) ? Validator::clean($data['readingTime']) : '۵ دقیقه'
            ));
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                Response::error('این اسلاگ قبلاً استفاده شده است.', 409);
            }
            throw $e;
        }

        $id = (int) $pdo->lastInsertId();
        Response::success($this->findAdmin($id), 'خبر ایجاد شد.', 201);
    }

    public function update($params)
    {
        $data = Request::json();
        $id = (int) $params['id'];
        $current = $this->findAdmin($id);
        if (!$current) {
            Response::error('خبر پیدا نشد.', 404);
        }

        $title = isset($data['title']) ? Validator::clean($data['title']) : $current['title'];
        $slug = isset($data['slug']) ? strtolower(Validator::clean($data['slug'])) : $current['slug'];
        if (!Validator::slug($slug)) {
            Response::error('اسلاگ معتبر نیست.', 422);
        }
        $categoryId = $this->categoryId(isset($data['category']) ? $data['category'] : $current['category']);
        $stmt = Database::connection()->prepare('UPDATE news SET category_id = ?, slug = ?, title = ?, excerpt = ?, content = ?, image_url = ?, status = ?, featured = ?, reading_time = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute(array(
            $categoryId,
            $slug,
            $title,
            isset($data['excerpt']) ? Validator::clean($data['excerpt']) : $current['excerpt'],
            isset($data['content']) ? trim((string) $data['content']) : $current['content'],
            isset($data['image']) ? Validator::clean($data['image']) : $current['image'],
            isset($data['status']) ? $data['status'] : $current['status'],
            isset($data['featured']) ? (!empty($data['featured']) ? 1 : 0) : ($current['featured'] ? 1 : 0),
            isset($data['readingTime']) ? Validator::clean($data['readingTime']) : $current['readingTime'],
            $id
        ));
        Response::success($this->findAdmin($id), 'خبر ویرایش شد.');
    }

    public function destroy($params)
    {
        $stmt = Database::connection()->prepare('DELETE FROM news WHERE id = ?');
        $stmt->execute(array((int) $params['id']));
        if ($stmt->rowCount() === 0) {
            Response::error('خبر پیدا نشد.', 404);
        }
        Response::success(null, 'خبر حذف شد.');
    }

    private function categoryId($name)
    {
        $name = Validator::clean($name);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        if ($slug === '' || $slug === '-') {
            $slug = 'category-' . substr(md5($name), 0, 8);
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM news_categories WHERE name = ? LIMIT 1');
        $stmt->execute(array($name));
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $insert = $pdo->prepare('INSERT INTO news_categories (name, slug, created_at) VALUES (?, ?, NOW())');
        $insert->execute(array($name, $slug));
        return (int) $pdo->lastInsertId();
    }

    private function findAdmin($id)
    {
        $stmt = Database::connection()->prepare("SELECT n.id, n.slug, n.title, n.excerpt, n.content, n.image_url AS image, n.status, n.featured, n.published_at AS publishedAt, n.reading_time AS readingTime, c.name AS category FROM news n LEFT JOIN news_categories c ON c.id = n.category_id WHERE n.id = ? LIMIT 1");
        $stmt->execute(array($id));
        $row = $stmt->fetch();
        if ($row) {
            $row['id'] = (int) $row['id'];
            $row['featured'] = (bool) $row['featured'];
        }
        return $row ?: null;
    }
}
