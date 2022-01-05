<?php

namespace App;

use JetBrains\PhpStorm\Pure;
use PDOException;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/readings/ValidationReadingData.php";

class TakingReading extends ValidationReadingData {
    private array $month_to_number = [
        "Январь" => "01",
        "Февраль" => "02",
        "Март" => "03",
        "Апрель" => "04",
        "Май" => "05",
        "Июнь" => "06",
        "Июль" => "07",
        "Август" => "08",
        "Сентябрь" => "09",
        "Октябрь" => "10",
        "Ноябрь" => "11",
        "Декабрь" => "12",
    ];

    private array $type_of_reading = [
        "Горячее водоснабжение" => "HotWaterСharge",
        "Холодное водоснабжение" => "ColdWaterСharge",
        "Электроэнергия" => "ElectricityСharge",
        "Отопление" => "HeatingСharge",
        "Газ" => "GasСharge"
    ];

    private array $type_of_rate = [
        "Горячее водоснабжение" => "ГВС",
        "Холодное водоснабжение" => "ХВС",
        "Электроэнергия" => "Электроснабжение",
        "Отопление" => "Отопление",
        "Газ" => "Газ"
    ];

    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws TakingReadingException
     */
    public function add_reading(array $data, $user_id): bool
    {
        $period_of_reading = $data["Reading_year"]. "-". $this->month_to_number[$data["Reading_month"]] ."-01";
        $type_of_reading = $this->type_of_reading[$data["Reading_type"]];
        $type_of_rate = $this->type_of_rate[$data["Reading_type"]];

        try {
            $data = $this->validate_data($data, $user_id, $period_of_reading, $type_of_reading);
        } catch (TakingReadingException $exception) {
            throw new TakingReadingException($exception->getMessage());
        }

        if ($this->session->getData("user")["Is_staff"] == 1){
            $is_consumer = 0;
        }
        else{
            $is_consumer = 1;
        }

        // get rate_id and tariff_amount
        $rate_info = $this->database->getConnection()->query(
            "SELECT Rate_id, Unit_cost FROM Rate WHERE Service_name = '{$type_of_rate}'"
        )->fetch();

        // get mc_id
        $mc_id = $this->database->getConnection()->query(
            "SELECT Management_company_id FROM Consumer INNER JOIN Address A on Consumer.Address_id = A.Address_id
                       WHERE Consumer_id = ". $user_id
        )->fetch()["Management_company_id"];
//        $this->database->getConnection()->query(
//            "SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;"
//        );
//            $this->database->getConnection()->query(
//                "START TRANSACTION;"
//            );
//            $this->database->getConnection()->query(
//                "COMMIT;"
//            );
        $statement = $this->database->getConnection()->prepare(
            "INSERT INTO " . $type_of_reading . " (Amount_of_unit, Charge_period, 
                                                            Information_entering_date, Is_consumer, 
                                                            Consumer_id, Management_company_id, 
                                                            Rate_id, Tariff_amount)
                                VALUES (:amount_of_unit, :charge_period, 
                                        :information_entering_date, :is_consumer, 
                                        :consumer_id, :management_company_id, 
                                        :rate_id, :tariff_amount);"
        );
        $statement->execute([
            "amount_of_unit" => $data["Consumer_reading"],
            "charge_period" => $period_of_reading,
            "information_entering_date" => date("Y-m-d"),
            "is_consumer" => $is_consumer,
            "consumer_id" => $user_id,
            "management_company_id" => $mc_id,
            "rate_id" => $rate_info["Rate_id"],
            "tariff_amount" => $rate_info["Unit_cost"] * $data["Consumer_reading"]
        ]);
        return true;
    }
}