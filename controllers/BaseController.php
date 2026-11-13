<?php
/**
 * Base controller — every controller extends this.
 * Provides JSON response helpers and safe $_POST/$_GET accessors.
 *
 * All AJAX responses use JSON: { "success": true, "data": ... } or { "success": false, "error": "..." }
 * This replaces the legacy "%%%%%%" delimiter protocol.
 */
class BaseController
{
    /** Send a success JSON response and exit. */
    protected function json(array $data = []): never
    {
        $data['success'] = true;
        $this->sendJson($data);
    }

    /** Send an error JSON response and exit. */
    protected function jsonError(string $msg, int $code = 400): never
    {
        http_response_code($code);
        $this->sendJson(['success' => false, 'error' => $msg]);
    }

    /** Send raw JSON and exit. */
    protected function sendJson(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode($data));
    }

    /** Get a $_POST value with optional default. */
    protected function post(string $key, $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /** Get all $_POST values. */
    protected function allPost(): array
    {
        return $_POST;
    }

    /**
     * Règle métier : la date d'un voyage ne peut pas être dans le futur pour
     * les utilisateurs non superadmin (les administrateurs gardent la main
     * pour les saisies anticipées). Comparaison « Y-m-d » sur le fuseau de Douala.
     */
    protected function requireDateVoyageNotFuture(string $date): void
    {
        if ($_SESSION['usr-con']['is-superadmin'] ?? false) {
            return;
        }
        if ($date > todayDouala()) {
            $this->jsonError("La date du voyage ne peut pas être dans le futur.");
        }
    }

    /** Get a $_GET value with optional default. */
    protected function get(string $key, $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Verify CSRF token, die with 403 if invalid. */
    protected function requireCsrf(): void
    {
        $token = $this->post('csrf_token', $_POST['csrf_token'] ?? null)
              ?? (json_decode(file_get_contents('php://input'), true)['csrf_token'] ?? null);

        if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
            http_response_code(403);
            $this->sendJson(['success' => false, 'error' => 'CSRF validation failed']);
        }
    }
}
