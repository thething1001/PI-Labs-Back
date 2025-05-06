<?php
class StudentGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT *
                FROM cms_schema.students
                ORDER BY id ASC";

        $stmt = $this->conn->query($sql);

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    public function create(array $data): string
    {
        $sql = "INSERT INTO cms_schema.students (group_name, first_name, last_name, gender, birthday, status, email, password)
                VALUES (:group_name, :first_name, :last_name, :gender, :birthday, :status, :email, :password)";

        $stmt = $this->conn->prepare($sql);

        $email = "{$data['first_name']}.{$data['last_name']}.{$data['group_name']}@cms.com";

        $stmt->bindValue(":group_name", $data["group_name"], PDO::PARAM_STR);
        $stmt->bindValue(":first_name", $data["first_name"], PDO::PARAM_STR);
        $stmt->bindValue(":last_name", $data["last_name"], PDO::PARAM_STR);
        $stmt->bindValue(":gender", $data["gender"], PDO::PARAM_STR);
        $stmt->bindValue(":birthday", $data["birthday"], PDO::PARAM_STR);
        $stmt->bindValue(":status", isset($data["status"]) ? (bool) $data["status"] : false, PDO::PARAM_BOOL);
        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->bindValue(":password", $data["birthday"], PDO::PARAM_STR);

        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function get(string $id): array | false
    {
        $sql = "SELECT * FROM cms_schema.students WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function update(string $id, array $new): int
    {
        $sql = "UPDATE cms_schema.students 
                SET group_name = :group_name, first_name = :first_name, last_name = :last_name, 
                    gender = :gender, birthday = :birthday, status = :status, email = :email,
                    password = :password WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $email = "{$new['first_name']}.{$new['last_name']}.{$new['group_name']}@cms.com";

        $stmt->bindValue(":group_name", $new["group_name"], PDO::PARAM_STR);
        $stmt->bindValue(":first_name", $new["first_name"], PDO::PARAM_STR);
        $stmt->bindValue(":last_name", $new["last_name"], PDO::PARAM_STR);
        $stmt->bindValue(":gender", $new["gender"], PDO::PARAM_STR);
        $stmt->bindValue(":birthday", $new["birthday"], PDO::PARAM_STR);
        $stmt->bindValue(":status", isset($new["status"]) ? (bool) $new["status"] : false, PDO::PARAM_BOOL);
        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->bindValue(":password", $new["birthday"], PDO::PARAM_STR);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();
        return $id;
    }

    public function deleteByID(string $id): int
    {
        $sql = "DELETE FROM cms_schema.students WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount();
    }

    public function deleteSeveral(string $ids): int
    {
        $sql = "DELETE FROM cms_schema.students WHERE id IN ($ids)";
        $stmt = $this->conn->prepare($sql);

        $stmt->execute();
        return $stmt->rowCount();
    }

    public function findByUniqueFields(
        string $first_name,
        string $last_name,
        string $birthday,
        string $gender,
        string $group_name
    ): array | false
    {
        $sql = "SELECT * FROM cms_schema.students 
                WHERE first_name = :first_name 
                  AND last_name = :last_name 
                  AND birthday = :birthday 
                  AND gender = :gender 
                  AND group_name = :group_name 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":first_name", $first_name, PDO::PARAM_STR);
        $stmt->bindValue(":last_name", $last_name, PDO::PARAM_STR);
        $stmt->bindValue(":birthday", $birthday, PDO::PARAM_STR);
        $stmt->bindValue(":gender", $gender, PDO::PARAM_STR);
        $stmt->bindValue(":group_name", $group_name, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}