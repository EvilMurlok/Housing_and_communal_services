<?php

namespace App;

class ReceiptPayment{
    private Database $database;

    private array $convert_to_account = [
        "ReceiptHCS" => ["Personal_acc_hcs", "Общий счет ЖКУ"],
        "ReceiptCityPhone" => ["Personal_acc_landline_ph", "Счет за городской телефон"],
        "ReceiptDistancePhone" => ["Personal_acc_long_dist_ph", "Счет за междугородний телефон"]
    ];

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * @throws ReceiptPaymentException
     */
    public function pay_for_receipt($table_name, $receipt_id): bool{
        $receipt_info = $this->database->getConnection()->query(
            "SELECT Consumer_id, Total_summ
                       FROM ". $table_name
                       ." WHERE Receipt_id = $receipt_id"
        )->fetch();
        $required_account = $this->convert_to_account[$table_name][0];
        $required_consumer_account = $this->database->getConnection()->query(
            "SELECT ". $required_account ." 
                       FROM Consumer 
                       WHERE Consumer_id = {$receipt_info['Consumer_id']}"
        )->fetch();
        $required_consumer_account = $required_consumer_account[$required_account];
        if ($required_consumer_account < $receipt_info["Total_summ"]){
            $difference = $receipt_info['Total_summ'] - $required_consumer_account;
            throw new ReceiptPaymentException("На счёте ". $this->convert_to_account[$table_name][1] .
                " не хватает {$difference} ₽ для оплаты квитанции!");
        }
        $required_consumer_account -= $receipt_info["Total_summ"];
        $this->database->getConnection()->query(
            "UPDATE ". $table_name . " 
                       SET Is_paid = 1,
                           Payment_date = '". date('Y-m-d') ."'
                       WHERE Receipt_id = $receipt_id"
        );
        $this->database->getConnection()->query(
            "UPDATE Consumer SET ". $required_account ."=". $required_consumer_account
        );
        return true;
    }
}