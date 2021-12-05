<?php

namespace App;

class ValidationOrganizationData{
    protected Database $database;
    protected Session $session;

    public function __construct(Database $database, $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @throws AdditionOrganizationException
     */
    public function validate_data(&$data, $edit_organization_id=0):array{
        if (empty($data["Organization_name"])){
            throw new AdditionOrganizationException("Наименование организации не может быть пустым!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Organization_name FROM ResourceOrganization 
                    WHERE Organization_name = :Organization_name AND Resource_organization_id <> $edit_organization_id"
        );
        $statement->bindParam(":Organization_name", $data["Organization_name"]);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AdditionOrganizationException("Организация с таким наименованием уже существует!");
        }

        // Telephone_number
        if (empty($data["Telephone_number"])){
            throw new AdditionOrganizationException("Телефон организации не может быть пустым!");
        }
        if (!preg_match("/^([0-9])+$/", $data["Telephone_number"])){
            throw new AdditionOrganizationException("Телефон должен состоять ТОЛЬКО из цифр!");
        }
        if (strlen($data["Telephone_number"]) != 11){
            throw new AdditionOrganizationException("Телефон должен состоять ровно из 11 цифр!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Telephone_number FROM ResourceOrganization 
                    WHERE Telephone_number = :Telephone_number AND Resource_organization_id <> $edit_organization_id"
        );
        $statement->bindParam(":Telephone_number", $data["Telephone_number"]);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AdditionOrganizationException("Организация с таким телефоном уже существует!");
        }

        // Company_email block
        if (empty($data["Organization_email"])){
            throw new AdditionOrganizationException("Почта организации не может быть пустой!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Organization_email FROM ResourceOrganization 
                    WHERE Organization_email = :Organization_email AND Resource_organization_id <> $edit_organization_id"
        );
        $statement->bindParam(":Organization_email", $data["Organization_email"]);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AdditionOrganizationException("Организация с таким email уже существует!");
        }

        // Address block
        if (empty($data["Address"])){
            throw new AdditionOrganizationException("Адрес организации не может быть пустым!");
        }

        // Bank_details block
        if (empty($data["Bank_details"])){
            throw new AdditionOrganizationException("Реквизиты организации не могут быть пустыми!");
        }
        return $data;
    }
}