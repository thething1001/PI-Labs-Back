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
      echo json_encode(["message" => "student not found"]);
      return;
    }

    echo $method;
    switch ($method) {
      case "GET":
        echo json_encode($student);
        break;

      case "PUT":
        $data = (array) json_decode(file_get_contents("php://input"), true);

        $errors = $this->getValidationError($data, false);

        if (!empty($errors)) {
          http_response_code(422);
          echo json_encode(["errors" => $errors]);
          break;
        }

        $rows = $this->gateway->update($id, $data);
        echo json_encode([
          "message" => "student $id updated",
          "rows" => $rows
        ]);
        break;

      case "DELETE":
        $rows = $this->gateway->deleteByID($id);
        echo json_encode([
          "message" => "student $id deleted",
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
          "message" => "student created",
          "id" => $id
        ]);
        break;

      case "DELETE":
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['ids'])) {
          $ids = implode(",", array_map('intval', $data['ids']));
          $rows = $this->gateway->deleteSeveral($ids);
          echo json_encode([
            "message" => "students deleted",
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

  private function getValidationError(array $data, bool $is_new = true): array
  {
      $errors = [];
  
      // Required fields for new students
      if ($is_new) {
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
      }
  
      // Validate first_name if provided
      if (array_key_exists("first_name", $data)) {
          if (!empty($data["first_name"]) && !preg_match("/^[A-Za-z]{2,50}$/", $data["first_name"])) {
              $errors[] = "First name must be 2-50 letters only";
          }
      }
  
      // Validate last_name if provided
      if (array_key_exists("last_name", $data)) {
          if (!empty($data["last_name"]) && !preg_match("/^[A-Za-z]{2,50}$/", $data["last_name"])) {
              $errors[] = "Last name must be 2-50 letters only";
          }
      }
  
      // Validate birthday if provided
      if (array_key_exists("birthday", $data)) {
          if (!empty($data["birthday"])) {
              try {
                  $birthDate = new DateTime($data["birthday"]);
                  $today = new DateTime();
                  $age = $today->diff($birthDate)->y;
  
                  if ($age < 16 || $age > 100) {
                      $errors[] = "Age must be between 16 and 100 years";
                  }
              } catch (Exception $e) {
                  $errors[] = "Invalid birthday format";
              }
          }
      }
      
      return $errors;
  }
}