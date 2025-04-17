<?php
class AuthController
{
    public function __construct(private AuthGateway $gateway) {}

    public function processRequest(string $method, string $action): void
    {
        switch ($action) {
            case 'login':
                $this->processLoginRequest($method);
                break;
            case 'logout':
                $this->processLogoutRequest($method);
                break;
            default:
                http_response_code(404);
                echo json_encode(["message" => "Action not found"]);
                break;
        }
    }

    private function processLoginRequest(string $method): void
    {
        if ($method !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            return;
        }

        $data = (array) json_decode(file_get_contents("php://input"), true);

        $errors = $this->validateLoginData($data);

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(["errors" => $errors]);
            return;
        }

        $user = $this->gateway->authenticate($data["email"], $data["password"]);

        if (!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid credentials"]);
            return;
        }

        // Generate a simple token (in production, use JWT or similar)
        $token = bin2hex(random_bytes(16));
        $this->gateway->storeSession($user["id"], $token);

        http_response_code(200);
        echo json_encode([
            "message" => "Login successful",
            "token" => $token,
            "user" => [
                "id" => $user["id"],
                "email" => $user["email"],
                "first_name" => $user["first_name"],
                "last_name" => $user["last_name"]
            ]
        ]);
    }

    private function processLogoutRequest(string $method): void
    {
        if ($method !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            return;
        }

        $headers = apache_request_headers();
        $token = isset($headers["Authorization"]) ? str_replace("Bearer ", "", $headers["Authorization"]) : null;

        if (!$token) {
            http_response_code(401);
            echo json_encode(["message" => "No token provided"]);
            return;
        }

        $this->gateway->clearSession($token);

        http_response_code(200);
        echo json_encode(["message" => "Logout successful"]);
    }

    private function validateLoginData(array $data): array
    {
        $errors = [];

        if (empty($data["email"])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($data["password"])) {
            $errors[] = "Password is required";
        }

        return $errors;
    }
}