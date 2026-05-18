<?php
/**
 * Common Helper Functions
 *
 * Provides globally reusable utility functions for input sanitization and
 * standardized JSON API responses. Included by all API endpoints and controllers.
 *
 * @package    Taliq\Includes
 * @subpackage Helpers
 * @version    1.0.0
 */

/**
 * Sanitizes user input by trimming whitespace, removing backslashes,
 * and encoding HTML special characters to prevent XSS attacks.
 *
 * @param string $data The raw input string to sanitize.
 *
 * @return string The sanitized string.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sends a JSON response with the specified HTTP status code and terminates execution.
 *
 * @param array $data        The associative array to encode as JSON.
 * @param int   $status_code The HTTP status code (default 200).
 *
 * @return void
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
