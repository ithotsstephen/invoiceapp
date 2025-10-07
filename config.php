<?php
// ==== App URL/Base ====
// Set to your hosted URL (no trailing slash), e.g. https://webdesigner.com.in/invoice
if (!defined('BASE_URL')) define('BASE_URL', 'https://webdesigner.com.in/invoice');
if (!defined('BASE_PATH')) define('BASE_PATH', __DIR__);

// ==== Database Config ====
// Create a MySQL database in GoDaddy cPanel and put credentials below.
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'u232365723_invoice';
$DB_USER = getenv('DB_USER') ?: 'u232365723_invoice';
$DB_PASS = getenv('DB_PASS') ?: 'Ithots*123!@#';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ==== Company Profile (edit to your company) ====
$COMPANY = [
    // You can use a remote URL or a path under the app root. Using the provided logo URL here.
    'logo_path' => 'https://ithots.com/wp-content/uploads/2025/08/iThots-Logo.png', // remote logo URL
    'name'      => 'iThots Technology Solutions Pvt. Ltd.',
    'address'   => "3rd Floor, ABC Towers, Chennai - 600 000\nTamil Nadu, India",
    'email'     => 'billing@ithots.com',
    'phone'     => '+91-90000 00000',
    'country'   => 'India',
    'prefix'    => 'ITS' // invoice prefix
];
?>
