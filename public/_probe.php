<?php
header('content-type: application/json; charset=utf-8');
$doc = __DIR__;
$vendor = realpath($doc.'/../vendor/autoload.php');
$bootstrap = realpath($doc.'/../bootstrap/app.php');
$base = $bootstrap ? realpath($doc.'/..') : null;
echo json_encode([
  'docroot' => $doc,
  'vendor_autoload' => $vendor,
  'bootstrap_app' => $bootstrap,
  'base_path' => $base,
], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
