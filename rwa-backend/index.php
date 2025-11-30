<?php
// Redirect all requests to public/index.php
if (!file_exists(__DIR__ . '/public/index.php')) {
    die('Laravel application not found. Please ensure all files are uploaded correctly.');
}

// Get the request URI and redirect to public folder
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

// Remove any existing /public/ from the URI to avoid double redirects
$clean_uri = str_replace('/public/', '/', $request_uri);

// Include the Laravel application
require_once __DIR__ . '/public/index.php';