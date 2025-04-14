<?php
	declare(strict_types=1);

	require_once "src/controllers/students_controller.php";
	require_once "src/error_handler.php";
	require_once "src/database/database.php";
	require_once "src/gateways/students_gateway.php";

	set_error_handler("ErrorHandler::handleError");
	set_exception_handler("ErrorHandler::handleException");

	header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
	header("Content-Type: application/json; charset=UTF-8");
	header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
	header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
	
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		http_response_code(200);
		exit();
	}

	$uriSegments = explode("/", $_SERVER["REQUEST_URI"]);

	switch ($uriSegments[1]) {
		case 'students':
			$id = $parts[2] ?? null;

			$database = new Database("cms.local", "cms_database", "postgres", "postgres");
			$database->getConnection();
		
			$gateway = new StudentGateway($database);
		
			$controller = new StudentController($gateway);
			$controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
			break;
		
		default:
			http_response_code(404);
			exit;
			break;
	}
?>
