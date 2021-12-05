<?php

namespace App;

class ValidationData{
    protected Database $database;
    protected Session $session;

    public function __construct(Database $database, $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @throws AdditionNewsException
     */
    public function validate_data(&$data, $edit_news_id=0):array{
        // title block
        if (empty($data["Title"])) {
            throw new AdditionNewsException("Название новости не может быть пустым!");
        }
        if (preg_match("/^\d/", $data["Title"]) === 1) {
            throw new AdditionNewsException("Название новости не может начинаться с цифры!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Title FROM News WHERE Title = :Title AND News_id <> $edit_news_id"
        );
        $statement->bindParam(':Title', $data['Title']);
        $statement->execute();
        if ($statement->rowCount() > 0) {
            throw new AdditionNewsException("Новость с таким названием уже существует!");
        }

        // Content block
        if (empty($data["Content"])) {
            throw new AdditionNewsException("Содержание новости не может быть пустым!");
        }

        // Is_published block
        if (isset($data["Is_published"]) && $data["Is_published"] == "on"){
            $data["Is_published"] = 1;
        }
        else{
            $data["Is_published"] = 0;
        }
        return $data;
    }
}