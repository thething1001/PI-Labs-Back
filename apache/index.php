<?php
	declare(strict_types=1);

	require_once "src/database/database.php";
	require_once "src/error_handler.php";
	require_once "src/controllers/students_controller.php";
	require_once "src/gateways/students_gateway.php";
	require_once "src/controllers/auth_controller.php";
	require_once "src/gateways/auth_gateway.php";
	require_once "src/controllers/auth.php";
	require_once "src/controllers/jwt_controller.php";

	set_error_handler("ErrorHandler::handleError");
	set_exception_handler("ErrorHandler::handleException");

	header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
	header("Content-Type: application/json; charset=UTF-8");
	header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE");
	header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
	
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		http_response_code(200);
		exit();
	}

	$uriSegments = explode("/", $_SERVER["REQUEST_URI"]);
	$jwtController = new JwtController();

	$database = new Database("cms.local", "cms_database", "postgres", "postgres");
	$database->getConnection();

	$AuthGateway = new AuthGateway($database);

	switch ($uriSegments[1]) {
		case 'students':
			$auth = new Auth($jwtController, $AuthGateway);
			if (!$auth->authenticate()) {
				exit();
			}

			$id = $uriSegments[2] ?? null;

			$gateway = new StudentGateway($database);
		
			$controller = new StudentController($gateway);
			$controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
			break;
		
		case 'auth':
			$action = $uriSegments[2] ?? null;
		
					
			$controller = new AuthController($AuthGateway, $jwtController);
			$controller->processRequest($_SERVER["REQUEST_METHOD"], $action);
			break;
			
		default:
			http_response_code(404);
			exit();
			break;
	}
?>