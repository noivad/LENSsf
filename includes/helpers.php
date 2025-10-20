<?php

declare(strict_types=1);

/**
 * Escape output for HTML contexts.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format a Y-m-d date string for display.
 */
function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('F j, Y', $timestamp) : e($date);
}

/**
 * Format an H:i time string for display.
 */
function format_time(?string $time): string
{
    if (!$time) {
        return '—';
    }

    $timestamp = strtotime($time);

    return $timestamp ? date('g:i A', $timestamp) : e($time);
}

/**
 * Format an ISO datetime string for display.
 */
function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }

    $timestamp = strtotime($datetime);

    return $timestamp ? date('F j, Y \a\t g:i A', $timestamp) : e($datetime);
}

/**
 * Normalize a comma-separated list of values into an array of trimmed strings.
 */
function normalize_list_input(?string $value): array
{
    if (!$value) {
        return [];
    }

    $items = array_filter(array_map('trim', explode(',', $value)));

    return array_values(array_unique($items));
}

/**
 * Simple redirect helper using PRG pattern.
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Flash message helpers
 */
function set_flash(string $message, string $type = 'success'): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

/**
 * Generate a unique identifier with a prefix.
 */
function generate_id(string $prefix): string
{
    return $prefix . bin2hex(random_bytes(5));
}

/**
 * Sanitize uploaded file names.
 */
function sanitize_filename(string $name): string
{
    $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $name);

    return strtolower(trim($name, '-')) ?: 'file';
}

/**
 * Determine if the given user is a site admin.
 * Reads from ADMIN_USERS (constant or env) as a comma-separated list of names.
 */
function is_admin(?string $user): bool
{
    $user = trim((string) $user);
    if ($user === '') {
        return false;
    }
    $list = [];
    if (defined('ADMIN_USERS')) {
        $val = ADMIN_USERS;
        if (is_array($val)) {
            $list = $val;
        } else {
            $list = explode(',', (string) $val);
        }
    } else {
        $env = getenv('ADMIN_USERS') ?: '';
        if ($env !== '') {
            $list = explode(',', $env);
        }
    }
    $list = array_filter(array_map('trim', $list));
    foreach ($list as $name) {
        if (strcasecmp($name, $user) === 0) {
            return true;
        }
    }
    return false;
}
