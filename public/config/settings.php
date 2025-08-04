<?php
/**
 * API settings and rate limits for Web Chat Bridge
 */

// API Configuration
define('API_VERSION', '1.0.0');
define('API_NAME', 'Web Chat Bridge API');

// Rate Limiting Configuration
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds
define('RATE_LIMIT_MAX_REQUESTS', 1000); // Max requests per window per IP
define('RATE_LIMIT_ENDPOINT_MAX', 100); // Max requests per window per endpoint per IP

// Endpoint-specific rate limits
$ENDPOINT_RATE_LIMITS = [
    '/api/messages' => 50,      // 50 messages per hour per IP
    '/api/responses' => 200,     // 200 response checks per hour per IP
    '/api/inbox' => 120,         // 120 inbox checks per hour (for plugin)
    '/api/outbox' => 200,        // 200 outbox posts per hour (for plugin)
    '/api/sessions' => 20        // 20 session list requests per hour (admin)
];

// Authentication
define('API_KEY_HEADER', 'Authorization');
define('API_KEY_PREFIX', 'Bearer ');

// Get API key from environment or config
function get_api_key() {
    return getenv('WEB_CHAT_API_KEY') ?: 'api_h8hcbfg4uiqfz6sjy1h6ri';
}

// Get admin key from environment or config
function get_admin_key() {
    return getenv('WEB_CHAT_ADMIN_KEY') ?: 'free0ps';
}

// CORS Configuration
function set_cors_headers() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Content Type Configuration
function set_json_headers() {
    header('Content-Type: application/json; charset=utf-8');
}

// Error Codes
define('ERROR_CODES', [
    'INVALID_REQUEST' => 400,
    'UNAUTHORIZED' => 401,
    'FORBIDDEN' => 403,
    'NOT_FOUND' => 404,
    'RATE_LIMITED' => 429,
    'INTERNAL_ERROR' => 500,
    'SERVICE_UNAVAILABLE' => 503
]);

// Message validation
define('MAX_MESSAGE_LENGTH', 10000); // 10KB max message size
define('MAX_SESSION_ID_LENGTH', 64);
define('MIN_MESSAGE_LENGTH', 1);

// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('MAX_SESSIONS_PER_IP', 10); // Max concurrent sessions per IP

// Logging configuration
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', __DIR__ . '/../logs/api.log');

// Security settings
define('ENABLE_HTTPS_ONLY', true); // Force HTTPS in production
define('SANITIZE_INPUT', true); // Sanitize all input
define('VALIDATE_SESSION_IDS', true); // Validate session ID format

// Performance settings
define('DB_QUERY_TIMEOUT', 10); // Database query timeout in seconds
define('API_RESPONSE_TIMEOUT', 30); // API response timeout in seconds
define('MAX_CONCURRENT_REQUESTS', 100); // Max concurrent requests

// Debug mode (set to false in production)
define('DEBUG_MODE', getenv('WEB_CHAT_DEBUG') === 'true' ? true : false);

// Logging function
function log_message($level, $message, $context = []) {
    if (!is_dir(dirname(LOG_FILE))) {
        mkdir(dirname(LOG_FILE), 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = sprintf(
        "[%s] [%s] %s %s\n",
        $timestamp,
        strtoupper($level),
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    
    if (DEBUG_MODE) {
        error_log($log_entry);
    }
} 