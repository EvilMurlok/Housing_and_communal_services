<?php
declare(strict_types=1);
namespace App;

class AuthorizationEntity {
    private Database $database;
    private Session $session;

    public function __construct(Database $database, $session){
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @throws AuthorizationEntityException
     */
    public function register(array $data): bool{
        // Company_name block
        if (empty($data['Company_name'])){
            throw new AuthorizationEntityException("Наименование компании не может быть пустым!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Company_name FROM ManagementCompany WHERE Company_name = :Company_name"
        );
        $statement->bindParam(':Company_name', $data['Company_name']);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AuthorizationEntityException("Компания с таким наименованием уже существует!");
        }

        // Full_name_boss block
        if (empty($data['Full_name_boss'])){
            throw new AuthorizationEntityException("ФИО руководителя не может быть пустым!");
        }

        // Telephone_number
        if (empty($data['Telephone_number'])){
            throw new AuthorizationEntityException("Телефон компании не может быть пустым!");
        }
        if (!preg_match("/^([0-9])+$/", $data['Telephone_number'])){
            throw new AuthorizationEntityException("Телефон должен состоять ТОЛЬКО из цифр!");
        }
        if (strlen($data['Telephone_number']) != 11){
            throw new AuthorizationEntityException("Телефон должен состоять ровно из 11 цифр!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Telephone_number FROM ManagementCompany WHERE Telephone_number = :Telephone_number"
        );
        $statement->bindParam(':Telephone_number', $data['Telephone_number']);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AuthorizationEntityException("Компания с таким телефоном уже существует!");
        }

        // Company_email block
        if (empty($data['Company_email'])){
            throw new AuthorizationEntityException("Почта компании не может быть пустой!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Company_email FROM ManagementCompany WHERE Company_email = :Company_email"
        );
        $statement->bindParam(':Company_email', $data['Company_email']);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AuthorizationEntityException("Компания с таким email уже существует!");
        }

        // Address block
        if (empty($data['Address'])){
            throw new AuthorizationEntityException("Адрес компании не может быть пустым!");
        }

        // Company_password block
        if (empty($data['Company_password'])){
            throw new AuthorizationEntityException("Пароль не может быть пустой!");
        }
        if ($data['Company_password'] !== $data['confirm_password']){
            throw new AuthorizationEntityException("Пароли не совпадают!");
        }

        $statement = $this->database->getConnection()->prepare(
          'INSERT INTO ManagementCompany (Company_name, Company_password, 
                               Full_name_boss, Company_email, Company_link, Telephone_number, Address)
                            VALUES (:Company_name, :Company_password, 
                                    :Full_name_boss, :Company_email, 
                                    :Company_link, :Telephone_number, :Address)'
        );
        $statement->execute([
            'Company_name' => $data['Company_name'],
            'Company_password' => password_hash($data['Company_password'], PASSWORD_BCRYPT),
            'Full_name_boss' => $data['Full_name_boss'],
            'Company_email' => $data['Company_email'],
            'Company_link' => $data['Company_link'],
            'Telephone_number' => $data['Telephone_number'],
            'Address' => $data['Address'],
        ]);
        return true;
    }

    /**
     * @throws AuthorizationEntityException
     */
    public function login(string $email_or_phone, string $password): bool{

        if (empty($email_or_phone)){
            throw new AuthorizationEntityException("Email или телефон компании не может быть пустым!");
        }
        if (empty($password)){
            throw new AuthorizationEntityException("Пароль не может быть пустым!");
        }
        if (str_contains($email_or_phone, "@")){
            $statement = $this->database->getConnection()->prepare(
            // Company_email, Company_password
                "SELECT * from ManagementCompany WHERE Company_email = :Company_email"
            );
            $statement->execute([
                "Company_email" => $email_or_phone
            ]);
            $management_company = $statement->fetch();
            if (empty($management_company)){
                throw new AuthorizationEntityException("Управляющей компании с таким email нет!");
            }
        }
        else{
            if (!preg_match("/^([0-9])+$/", $email_or_phone)){
                throw new AuthorizationEntityException("Телефон должен состоять ТОЛЬКО из цифр!");
            }
            if (strlen($email_or_phone) != 11){
                throw new AuthorizationEntityException("Телефон должен состоять ровно из 11 цифр!");
            }

            $statement = $this->database->getConnection()->prepare(
            // Telephone_number, Company_password
                "SELECT * from ManagementCompany WHERE Telephone_number = :Telephone_number"
            );
            $statement->execute([
                "Telephone_number" => $email_or_phone
            ]);
            $management_company = $statement->fetch();
            if (empty($management_company)){
                throw new AuthorizationEntityException("Управляющей компании с таким телефоном нет!");
            }
        }

        if (password_verify($password, $management_company["Company_password"])){
            $this->session->setData(
                "user", [
                    "Management_company_id" => $management_company["Management_company_id"],
                    "Company_name" => $management_company["Company_name"],
                    "Full_name_boss" => $management_company["Full_name_boss"],
                    "Company_email" => $management_company["Company_email"],
                    "Company_link" => $management_company["Company_link"],
                    "Telephone_number" => $management_company["Telephone_number"],
                    "Address" => $management_company["Address"],
                    "Is_staff" => $management_company["Is_staff"]
            ]);
            return True;
        }
        throw new AuthorizationEntityException("Неверный email или пароль!");
    }
}