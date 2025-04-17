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

    public function storeSession(string $userId, string $token): void
    {
        $sql = "INSERT INTO cms_schema.sessions (user_id, token, created_at)
                VALUES (:user_id, :token, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindValue(":token", $token, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function clearSession(string $token): void
    {
        $sql = "DELETE FROM cms_schema.sessions WHERE token = :token";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":token", $token, PDO::PARAM_STR);
        $stmt->execute();
    }
}