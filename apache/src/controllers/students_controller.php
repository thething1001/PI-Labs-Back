<?php
class StudentController
{
    public function __construct(private StudentGateway $gateway) {}

    public function processRequest(string $method, ?string $id): void
    {
        if ($id) {
            $this->processResourceRequest($method, $id);
        } else {
            $this->processCollectionRequest($method);
        }
    }

    private function processResourceRequest(string $method, string $id): void
    {
        $student = $this->gateway->get($id);

        if (!$student) {
            http_response_code(404);
            echo json_encode(["message" => "Student not found"]);
            return;
        }

        switch ($method) {
            case "GET":
                echo json_encode($student);
                break;

            case "PUT":
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationError($data, false, $id);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($id, $data);
                echo json_encode([
                    "message" => "Student $id updated",
                    "rows" => $rows
                ]);
                break;

            case "DELETE":
                $rows = $this->gateway->deleteByID($id);
                echo json_encode([
                    "message" => "Student $id deleted",
                    "rows" => $rows
                ]);
                break;

            default:
                http_response_code(405);
                header("Allow: GET, PUT, DELETE");
        }
    }

    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;

            case "POST":
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationError($data);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $id = $this->gateway->create($data);
                http_response_code(201);
                echo json_encode([
                    "message" => "Student created",
                    "id" => $id
                ]);
                break;

            case "DELETE":
                $data = json_decode(file_get_contents("php://input"), true);
                if (!empty($data['ids'])) {
                    $ids = implode(",", array_map('intval', $data['ids']));
                    $rows = $this->gateway->deleteSeveral($ids);
                    echo json_encode([
                        "message" => "Students deleted",
                        "rows" => $rows
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "No IDs provided"]);
                }
                break;

            default:
                http_response_code(405);
                header("Allow: GET, POST, DELETE");
                break;
        }
    }

    private function getValidationError(array $data, bool $is_new = true, ?string $current_id = null): array
    {
        $errors = [];

        // Validate required fields for new students
        if ($is_new) {
            $errors = array_merge($errors, $this->validateRequiredFields($data));
        }

        // Validate individual fields if provided
        $errors = array_merge($errors, $this->validateFirstName($data));
        $errors = array_merge($errors, $this->validateLastName($data));
        $errors = array_merge($errors, $this->validateBirthday($data));
        $errors = array_merge($errors, $this->validateGender($data));

        if(empty($errors)) $errors = array_merge($errors, $this->validateDuplicates($data, $current_id));

        return $errors;
    }

    private function validateRequiredFields(array $data): array
    {
        $errors = [];

        if (empty($data["first_name"])) {
            $errors[] = "First name is required";
        }
        if (empty($data["last_name"])) {
            $errors[] = "Last name is required";
        }
        if (empty($data["birthday"])) {
            $errors[] = "Birthday is required";
        }
        if (empty($data["gender"])) {
            $errors[] = "Gender is required";
        }
        if (empty($data["group_name"])) {
            $errors[] = "Group is required";
        }

        return $errors;
    }

    private function validateFirstName(array $data): array
    {
        $errors = [];

        if (array_key_exists("first_name", $data) && !empty($data["first_name"])) {
            if (!preg_match("/^[A-Za-z]{2,50}$/", $data["first_name"])) {
                $errors[] = "First name must be 2-50 letters only";
            }
        }

        return $errors;
    }

    private function validateLastName(array $data): array
    {
        $errors = [];

        if (array_key_exists("last_name", $data) && !empty($data["last_name"])) {
            if (!preg_match("/^[A-Za-z]{2,50}$/", $data["last_name"])) {
                $errors[] = "Last name must be 2-50 letters only";
            }
        }

        return $errors;
    }

    private function validateBirthday(array $data): array
    {
        $errors = [];
        
        if (array_key_exists("birthday", $data)) {
            if (empty($data["birthday"])) {
                $errors[] = "Birthday is required";
            } else {
                if (!preg_match("/^(\d{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/", $data["birthday"])) {
                    $errors[] = "Birthday must be in YYYY-MM-DD format";
                } else {
                    try {
                        $birthDate = new DateTime($data["birthday"]);
                        $today = new DateTime();
                        $age = $today->diff($birthDate)->y;

                        if ($age < 16 || $age > 100) {
                            $errors[] = "Age must be between 16 and 100 years";
                        }
                    } catch (Exception $e) {
                        $errors[] = "Invalid date (e.g., check for valid day or leap year)";
                    }
                }
            }
        }

        return $errors;
    }

    private function validateGender(array $data): array
    {
        $errors = [];

        if (array_key_exists("gender", $data) && !empty($data["gender"])) {
            $validGenders = ["Male", "Female", "Other"];
            if (!in_array($data["gender"], $validGenders, true)) {
                $errors[] = "Gender must be one of: Male, Female, Other";
            }
        }

        return $errors;
    }

    private function validateDuplicates(array $data, ?string $current_id): array
    {
        $errors = [];

        if (
            array_key_exists("first_name", $data) &&
            array_key_exists("last_name", $data) &&
            array_key_exists("birthday", $data) &&
            array_key_exists("gender", $data) &&
            array_key_exists("group_name", $data) &&
            !empty($data["first_name"]) &&
            !empty($data["last_name"]) &&
            !empty($data["birthday"]) &&
            !empty($data["gender"]) &&
            !empty($data["group_name"])
        ) {
            $existingStudent = $this->gateway->findByUniqueFields(
                $data["first_name"],
                $data["last_name"],
                $data["birthday"],
                $data["gender"],
                $data["group_name"]
            );

            if ($existingStudent && ($current_id === null || $existingStudent["id"] != $current_id)) {
                $errors[] = "A student with the same first name, last name, birthday, gender, and group already exists";
            }
        }

        return $errors;
    }
}
?>