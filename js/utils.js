/**
 * @file utils.js
 * @description Common utility functions for string manipulation and XSS prevention.
 * @version 1.0.0
 */

/**
 * Escapes HTML special characters to prevent XSS injection.
 * Replaces &, <, >, ", and ' with their HTML entity equivalents.
 *
 * @param {string} str - The raw string to escape.
 * @returns {string} The HTML-safe escaped string.
 */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Capitalizes the first character of a string.
 *
 * @param {string} str - The string to capitalize.
 * @returns {string} The string with its first character uppercased.
 */
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
