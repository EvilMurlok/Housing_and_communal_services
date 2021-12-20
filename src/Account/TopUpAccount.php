<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Account/ValidationAccountData.php";

class TopUpAccount extends ValidationAccountData
{
    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws TopUpAccountException
     */
    public function top_up_account(array $data, $user_phone, $kind_of_account): bool
    {
        try {
            $data = $this->validate_data($data, $user_phone);
        } catch (TopUpAccountException $exception) {
            throw new TopUpAccountException($exception->getMessage());
        }

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
            "UPDATE Consumer SET ". $kind_of_account ."={$current_amount}
                    WHERE Telephone_number = :user_phone"
        );
        $statement->execute([
            'user_phone' => $user_phone,
        ]);
        return true;
    }
}