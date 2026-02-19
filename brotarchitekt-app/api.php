<?php
/**
 * Brotarchitekt Standalone API
 * POST /api.php — berechnet ein Brotrezept
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['error' => 'Method not allowed']);
	exit;
}

require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/recipe-context.php';
require_once __DIR__ . '/includes/leaven-calculator.php';
require_once __DIR__ . '/includes/flour-calculator.php';
require_once __DIR__ . '/includes/ingredients-builder.php';
require_once __DIR__ . '/includes/baking-profile.php';
require_once __DIR__ . '/includes/timeline-builder.php';
require_once __DIR__ . '/includes/calculator.php';

Lang::load('de');

try {
	$body = file_get_contents('php://input');
	$input = json_decode($body, true);

	if (!is_array($input)) {
		http_response_code(400);
		echo json_encode(['error' => 'Invalid JSON body']);
		exit;
	}

	$input['mainFlours'] = is_array($input['mainFlours'] ?? null) ? $input['mainFlours'] : [];
	$input['sideFlours'] = is_array($input['sideFlours'] ?? null) ? $input['sideFlours'] : [];
	$input['extras']     = is_array($input['extras'] ?? null) ? $input['extras'] : [];
	if (empty($input['timeBudget'])) $input['timeBudget'] = 12;
	if (empty($input['backMethod'])) $input['backMethod'] = 'pot';

	$calc = new Calculator();
	$recipe = $calc->calculate($input);

	echo json_encode($recipe, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
