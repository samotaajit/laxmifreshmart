<?php

declare(strict_types=1);

define('SITE_NAME', 'Laxmi Fresh Mart');

// WhatsApp business number in international format, without + or spaces.
define('WHATSAPP_NUMBER', '919694877257');

define('BASE_URL', 'https://laxmifreshmartjpr.netlify.app/');

define('ADMIN_URL', BASE_URL . 'admin/');

define('UPLOAD_URL', BASE_URL . 'uploads/');

define('PRODUCT_UPLOAD_URL', UPLOAD_URL . 'products/');

define('CATEGORY_UPLOAD_URL', UPLOAD_URL . 'categories/');

date_default_timezone_set('Asia/Kolkata');