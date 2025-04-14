<?php
class Database
{
  public function __construct(
    private string $host,
    private string $name,
    private string $user,
    private string $password
  ) {}

  public function getConnection(): PDO
  {
    $dsn = "mysql:host={$this->host};dbname={$this->name};user={$this->user};password={$this->password}";
    $pdo = new PDO($dsn, $this->user, $this->password, [
      PDO::ATTR_EMULATE_PREPARES => false,
      PDO::ATTR_STRINGIFY_FETCHES => false
    ]);
    return $pdo;
  }
}
