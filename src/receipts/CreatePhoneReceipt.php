<?php

namespace App;

use DateTime;
use Exception;
use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/ValidationReceiptData.php";

class CreatePhoneReceipt extends ValidationReceiptData{

    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws CreateReceiptException
     * @throws Exception
     */
    public function add_receipt(array $data, $user_id, $is_phone=1): bool{
        try{
            $required_parameters = $this->validate_get_receipt_parameters($data, $user_id, $is_phone);
        }
        catch (CreateReceiptException $exception){
            throw new CreateReceiptException($exception->getMessage());
        }

        $period_of_receipt = $required_parameters["period_of_receipt"];
        $type_of_receipt = $required_parameters["type_of_receipt"];
        $type_of_rate = $required_parameters["type_of_rate"];

        $rate_info = $this->database->getConnection()->query(
            "SELECT Rate_id, Unit_cost FROM Rate WHERE Service_name = '{$type_of_rate}'"
        )->fetch();

        $tariff_amount = $rate_info["Unit_cost"] * $data["Consumer_reading"];
        $information_entering_date = date("Y-m-d");


        $deadline_date = date('Y-m-d', strtotime(date("Y-m-d"). ' + 30 days'));

//        testing working of functions
//        $last_month_day = date('t', strtotime($information_entering_date));
//        $deadline_date = date('Y-m-d', strtotime(date("Y-m-d"). ' - 3 days'));
//        $overdue_days = date_diff(new DateTime($deadline_date), new DateTime(date("Y-m-d")))->days;
//
//        $total_summ = ($overdue_days * 0.1 + 1) * $tariff_amount;

        $statement = $this->database->getConnection()->prepare(
            "INSERT INTO ". $type_of_receipt ." (Amount_of_minutes, Receipt_period, 
                                                        Information_entering_date, Consumer_id, 
                                                        Rate_id, Tariff_amount, 
                                                        Deadline_date, Overdue_days,
                                                        Total_summ, Is_paid)
                            VALUES (:amount_of_minutes, :receipt_period, 
                                    :information_entering_date, :consumer_id, 
                                    :rate_id, :tariff_amount, 
                                    :deadline_date, :overdue_days,
                                    :total_summ, :is_paid)"
        );

        $statement->execute([
            "amount_of_minutes" => $data["Consumer_reading"],
            "receipt_period" => $period_of_receipt,
            "information_entering_date" => $information_entering_date,
            "consumer_id" => $user_id,
            "rate_id" => $rate_info["Rate_id"],
            "tariff_amount" => $tariff_amount,
            "deadline_date" => $deadline_date,
            "overdue_days" => 0,
            "total_summ" => $tariff_amount,
            "is_paid" => 0
        ]);

        return true;
    }
}