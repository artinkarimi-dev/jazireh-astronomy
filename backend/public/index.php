<?php

require_once dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
$auth = new AuthController();
$news = new NewsController();
$content = new ContentController();
$adminContent = new AdminContentController();
$sync = new SyncController();

$router->get('/api/health', array($content, 'health'));
$router->get('/api/home', array($content, 'home'));
$router->get('/api/news', array($news, 'index'));
$router->get('/api/news/{slug}', array($news, 'show'));
$router->get('/api/apod', array($content, 'apod'));
$router->get('/api/apod/today', array($content, 'apodToday'));
$router->get('/api/videos', array($content, 'videos'));
$router->get('/api/sky/today', array($content, 'skyToday'));
$router->get('/api/objects', array($content, 'objects'));
$router->get('/api/objects/{slug}', array($content, 'object'));
$router->post('/api/newsletter', array($content, 'newsletter'));
$router->post('/api/auth/login', array($auth, 'login'));
$router->get('/api/auth/me', array($auth, 'me'), true);

$router->get('/api/admin/news', array($news, 'adminIndex'), true);
$router->post('/api/admin/news', array($news, 'store'), true);
$router->put('/api/admin/news/{id}', array($news, 'update'), true);
$router->delete('/api/admin/news/{id}', array($news, 'destroy'), true);
$router->post('/api/admin/apod', array($adminContent, 'storeApod'), true);
$router->put('/api/admin/apod/{id}', array($adminContent, 'updateApod'), true);
$router->delete('/api/admin/apod/{id}', array($adminContent, 'deleteApod'), true);
$router->post('/api/admin/videos', array($adminContent, 'storeVideo'), true);
$router->put('/api/admin/videos/{id}', array($adminContent, 'updateVideo'), true);
$router->delete('/api/admin/videos/{id}', array($adminContent, 'deleteVideo'), true);
$router->post('/api/admin/sky', array($adminContent, 'updateSky'), true);
$router->post('/api/admin/sync/apod', array($sync, 'apod'), true);
$router->post('/api/admin/sync/weather', array($sync, 'weather'), true);

$router->dispatch();
