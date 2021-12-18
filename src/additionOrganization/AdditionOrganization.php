<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionOrganization/ValidationOrganizationData.php";


class AdditionOrganization extends ValidationOrganizationData {

    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws AdditionOrganizationException
     */
    public function add_organization(array $data): bool
    {
        try{
            $data = $this->validate_data($data);
        }
        catch (AdditionOrganizationException $exception){
            throw new AdditionOrganizationException($exception->getMessage());
        }

        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO ResourceOrganization (Organization_name, Telephone_number, Organization_email, 
                                  Organization_link, Bank_details, Address)
                            VALUES (:Organization_name, :Telephone_number, 
                                    :Organization_email, :Organization_link, :Bank_details, :Address)'
        );
        $statement->execute([
            "Organization_name" => $data["Organization_name"],
            "Telephone_number" => $data["Telephone_number"],
            "Organization_email" => $data["Organization_email"],
            "Organization_link" => $data["Organization_link"],
            "Bank_details" => $data["Bank_details"],
            "Address" => $data["Address"],
        ]);
        return true;
    }

}