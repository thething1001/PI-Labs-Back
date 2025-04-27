<?php
class AuthGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function authenticate(string $email, string $password): array | false
    {
        $sql = "SELECT id, email, first_name, last_name, password 
                FROM cms_schema.students 
                WHERE email = :email";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user["password"]) {
            unset($user["password"]);
            return $user;
        }

        return false;
    }

    public function changeStatus(string $id, bool $status): bool
    {
        $sql = "UPDATE cms_schema.students
                SET status = :status
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_STR);
        $stmt->bindValue(":status", $status, PDO::PARAM_BOOL);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}