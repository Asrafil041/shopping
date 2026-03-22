<?php
header('Content-Type: application/json');
http_response_code(200);

echo json_encode([
    'status' => 'up',
    'service' => 'shopping',
    'timestamp' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
