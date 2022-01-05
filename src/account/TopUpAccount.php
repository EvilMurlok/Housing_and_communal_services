<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/account/ValidationAccountData.php";

class TopUpAccount extends ValidationAccountData
{
    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws TopUpAccountException
     */
    public function top_up_account(array $data, $user_phone, $block="on"): bool
    {
        if ($data["Account_type"] == "Общий счет ЖКУ"){
            $kind_of_account = "Personal_acc_hcs";
        }
        elseif ($data["Account_type"] == "Городской телефон"){
            $kind_of_account = "Personal_acc_landline_ph";
        }
        else{
            $kind_of_account = "Personal_acc_long_dist_ph";
        }

        try {
            $data = $this->validate_data($data, $user_phone);
        } catch (TopUpAccountException $exception) {
            throw new TopUpAccountException($exception->getMessage());
        }
        if ($block == "on"){
            $this->database->getConnection()->query("LOCK TABLES Consumer WRITE;");
        }
        sleep(15);
        $statement = $this->database->getConnection()->prepare(
            "SELECT ". $kind_of_account ." FROM Consumer
                    WHERE Telephone_number = :user_phone"
        );
        $statement->execute([
            "user_phone" => $user_phone
        ]);
        $current_amount = $statement->fetch()[$kind_of_account];
        $current_amount = (int) $current_amount + (int) $data["Amount_of_money"];

        $statement = $this->database->getConnection()->prepare(
            "UPDATE Consumer SET ". $kind_of_account ."=$current_amount
                    WHERE Telephone_number = :user_phone"
        );
        $statement->execute([
            'user_phone' => $user_phone,
        ]);
        if ($block == "on") {
            $this->database->getConnection()->query("UNLOCK TABLES;");
        }
        return true;
    }
}