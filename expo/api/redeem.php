<?php
/**
 * Server-to-server API for the Hala Card purchase system (after launch).
 *
 * POST /expo/api/redeem.php
 * Header: X-Api-Key: <redeem_api_key from config.php>
 * Body (JSON): { "code": "SX26-XXXXXX", "email": "buyer@example.com", "action": "check" | "redeem", "ref": "order-123" }
 *
 * "check" validates without using the code; "redeem" marks it used (once only).
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

require_method('POST');
$key = (string) cfg('redeem_api_key', '');
if ($key === '' || !hash_equals($key, (string) ($_SERVER['HTTP_X_API_KEY'] ?? ''))) fail(401, 'Unauthorised.');

$in = read_json();
$action = ($in['action'] ?? 'check') === 'redeem' ? 'redeem' : 'check';
$result = redeem_code((string) ($in['code'] ?? ''), isset($in['email']) ? (string) $in['email'] : null, (string) ($in['ref'] ?? 'api'), $action === 'redeem');

json_out($result['ok'] ? 200 : 409, [
    'ok'       => $result['ok'],
    'result'   => $result['reason'],
    'offer'    => $result['ok'] ? 'sierah-expo-gift' : null,
    'firstName'=> $result['registration']['first_name'] ?? null,
]);
