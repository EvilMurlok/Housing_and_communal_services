<?php

namespace App;

use JetBrains\PhpStorm\Pure;

class EditionOrganization extends ValidationOrganizationData{
    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws AdditionOrganizationException
     */
    public function edit_organization(array $data, $required_organization_id): bool
    {
        try{
            $data = $this->validate_data($data, $required_organization_id);
        }
        catch (AdditionOrganizationException $exception){
            throw new AdditionOrganizationException($exception->getMessage());
        }
        $statement = $this->database->getConnection()->prepare(
            "UPDATE ResourceOrganization 
                    SET Organization_name = :Organization_name, Organization_email = :Organization_email, 
                           Organization_link = :Organization_link, Telephone_number = :Telephone_number, 
                           Bank_details = :Bank_details
                    WHERE Resource_organization_id = :required_organization_id"
        );
        $statement->execute([
            'Organization_name' => $data['Organization_name'],
            'Organization_email' => $data['Organization_email'],
            'Organization_link' => $data['Organization_link'],
            'Telephone_number' => $data['Telephone_number'],
            'Bank_details' => $data['Bank_details'],
            'required_organization_id' => $required_organization_id
        ]);
        return true;
    }

}