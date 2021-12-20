<?php

namespace App;

class ValidationReadingData{
    protected Database $database;
    protected Session $session;

    public function __construct(Database $database, $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @throws TakingReadingException
     */
    public function validate_data($data, $user_id, $period_of_reading, $type_of_reading) :array{
        // Consumer_reading block
        if (empty($data["Consumer_reading"])){
            throw new TakingReadingException("Поле для внесения показаний не может быть пустым!");
        }
        if ($data["Consumer_reading"] < 0){
            throw new TakingReadingException("Внесенное показание не может быть отрицательным!");
        }

        // $period_of_reading block
        if ($period_of_reading > date("Y-m-d")){
            throw new TakingReadingException("Вы не можете внести показания за будущие месяцы!");
        }

        // double reading checking
        $statement = $this->database->getConnection()->prepare(
            "SELECT * FROM ". $type_of_reading ."
                    WHERE Consumer_id = :user_id AND Charge_period = :period_of_reading");
        $statement->execute([
            "user_id" => $user_id,
            "period_of_reading" => $period_of_reading
        ]);
        if ($statement->rowCount() > 0){
            throw new TakingReadingException("Показание за услугу: '".
                $data["Reading_type"] ."', за период: ".
                $data["Reading_month"] . " " . $data["Reading_year"] . " уже существует!");
        }
        return $data;
    }
}