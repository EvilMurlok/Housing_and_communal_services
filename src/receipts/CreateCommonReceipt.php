<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/ValidationReceiptData.php";

class CreateCommonReceipt extends ValidationReceiptData{


    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws CreateReceiptException
     */
    public function add_receipt(array $data, $user_id, $is_phone=0): bool{
        try{
            $required_parameters = $this->validate_get_receipt_parameters($data, $user_id, $is_phone);
        }
        catch (CreateReceiptException $exception){
            throw new CreateReceiptException($exception->getMessage());
        }



        return true;
    }
}