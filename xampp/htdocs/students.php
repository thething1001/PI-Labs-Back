<?php
// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
  header("HTTP/1.1 200 OK");
  exit();
}

// Set CORS headers for actual requests
header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

$conn = new mysqli("localhost", "root", "", "cms_app");

if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["message" => "Database connection failed: " . $conn->connect_error]);
  exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    // Get all students
    $result = $conn->query("SELECT * FROM students");
    $students = [];
    while ($row = $result->fetch_assoc()) {
      $students[] = $row;
    }
    echo json_encode($students);
    break;

  case 'POST':
    // Add new student
    $data = json_decode(file_get_contents("php://input"), true);
    if (
      !empty($data['group']) &&
      !empty($data['firstName']) &&
      !empty($data['lastName']) &&
      !empty($data['gender']) &&
      !empty($data['birthday'])
    ) {
      $stmt = $conn->prepare("INSERT INTO students (group_name, first_name, last_name, gender, birthday, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $email = "{$data['firstName']}.{$data['lastName']}.{$data['group']}@cms.com";
      $stmt->bind_param("sssssss", $data['group'], $data['firstName'], $data['lastName'], $data['gender'], $data['birthday'], $email, $data['birthday']);
      if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["message" => "Student added successfully"]);
      } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to add student"]);
      }
      $stmt->close();
    } else {
      http_response_code(400);
      echo json_encode(["message" => "Incomplete data"]);
    }
    break;

  case 'PUT':
    // Edit student
    $data = json_decode(file_get_contents("php://input"), true);
    if (
      !empty($data['id']) &&
      !empty($data['group']) &&
      !empty($data['firstName']) &&
      !empty($data['lastName']) &&
      !empty($data['gender']) &&
      !empty($data['birthday'])
    ) {
      $stmt = $conn->prepare("UPDATE students SET group_name = ?, first_name = ?, last_name = ?, gender = ?, birthday = ?, email = ?, password = ? WHERE id = ?");
      $email = "{$data['firstName']}.{$data['lastName']}.{$data['group']}@cms.com";
      $stmt->bind_param("ssssssss", $data['group'], $data['firstName'], $data['lastName'], $data['gender'], $data['birthday'], $email, $data['birthday'], $data['id']);
      if ($stmt->execute()) {
        echo json_encode(["message" => "Student updated successfully"]);
      } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to update student"]);
      }
      $stmt->close();
    } else {
      http_response_code(400);
      echo json_encode(["message" => "Incomplete data"]);
    }
    break;

  case 'DELETE':
    // Delete student(s)
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['ids'])) {
      $ids = implode(",", array_map('intval', $data['ids']));
      $stmt = $conn->prepare("DELETE FROM students WHERE id IN ($ids)");
      if ($stmt->execute()) {
        echo json_encode(["message" => "Student(s) deleted successfully"]);
      } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to delete student(s)"]);
      }
      $stmt->close();
    } else {
      http_response_code(400);
      echo json_encode(["message" => "No IDs provided"]);
    }
    break;

  default:
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    break;
}

$conn->close();
