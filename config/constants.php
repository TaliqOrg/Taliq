<?php
/**
 * Application Constants and Session Bootstrap
 *
 * Initializes the PHP session if one has not already been started. This file
 * should be included at the top of any script that requires session access.
 *
 * @package    Taliq\Config
 * @subpackage Session
 * @version    1.0.0
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
