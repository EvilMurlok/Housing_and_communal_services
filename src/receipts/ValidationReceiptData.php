<?php

namespace App;


use JetBrains\PhpStorm\ArrayShape;

class ValidationReceiptData{
    protected Database $database;
    protected Session $session;

    protected array $month_to_number = [
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

    private array $type_of_receipt = [
        "Общая квитанция ЖКУ" => "ReceiptHCS",
        "Квитанция междугородний телефон" => "ReceiptDistancePhone",
        "Квитанция городской телефон" => "ReceiptCityPhone"
    ];

    private array $type_of_rate = [
        "Общая квитанция ЖКУ" => "",
        "Квитанция междугородний телефон" => "Междугородние звонки",
        "Квитанция городской телефон" => "Городские звонки"
    ];


    public function __construct(Database $database, $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @throws CreateReceiptException
     */
    public function validate_data($data, $user_id, $period_of_receipt, $type_of_receipt, $is_phone) :void{
        // Consumer_reading block
        if ($is_phone == 1){
            if (empty($data["Consumer_reading"])){
                throw new CreateReceiptException("Поле для внесения минут не может быть пустым!");
            }
            if ($data["Consumer_reading"] < 0){
                throw new CreateReceiptException("Количество минут не может быть отрицательным!");
            }
        }

        // $period_of_receipt block
        if ($period_of_receipt > date("Y-m-d")){
            throw new CreateReceiptException("Вы не можете создать тариф за будущие месяцы!");
        }

        // double receipt checking
        $statement = $this->database->getConnection()->prepare(
            "SELECT * FROM ". $type_of_receipt ."
                    WHERE Consumer_id = :user_id AND Receipt_period = :period_of_receipt");
        $statement->execute([
            "user_id" => $user_id,
            "period_of_receipt" => $period_of_receipt
        ]);
        if ($statement->rowCount() > 0){
            throw new CreateReceiptException("Квитанция: '".
                $data["Receipt_type"] ."', за период: ".
                $data["Receipt_month"] . " " . $data["Receipt_year"] . " уже существует!");
        }
    }

    /**
     * @throws CreateReceiptException
     */

    #[ArrayShape(["period_of_receipt" => "string", "type_of_receipt" => "mixed|string", "type_of_rate" => "mixed|string"])]
    protected function validate_get_receipt_parameters($data, $user_id, $is_phone): array{
        $period_of_receipt = $data["Receipt_year"]. "-". $this->month_to_number[$data["Receipt_month"]] ."-01";
        $type_of_receipt = $this->type_of_receipt[$data["Receipt_type"]];
        $type_of_rate = $this->type_of_rate[$data["Receipt_type"]];

        try {
            $this->validate_data($data, $user_id, $period_of_receipt, $type_of_receipt, $is_phone);
        } catch (CreateReceiptException $exception) {
            throw new CreateReceiptException($exception->getMessage());
        }
        return ["period_of_receipt" => $period_of_receipt,
                "type_of_receipt" => $type_of_receipt,
                "type_of_rate" => $type_of_rate];
    }

}