<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../backend/controllers/TagController.php';

$controller = new TagController();
$controller->handleRequest();
