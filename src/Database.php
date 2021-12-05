<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use InvalidArgumentException;

class Database {
    private PDO $connection;
    public function __construct(string $dsn, string $username='', string $password=''){
        try{
            $this->connection = new PDO($dsn, $username, $password);
        }
        catch (PDOException $exception){
            throw new InvalidArgumentException("Database connection error: " . $exception->getMessage());
        }
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    // возвращает объект бд, с которой дальше будем работать, выполнять запросы
    public function getConnection(): PDO{
        return $this->connection;
    }
}