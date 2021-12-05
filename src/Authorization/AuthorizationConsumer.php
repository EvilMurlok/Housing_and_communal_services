<?php

namespace App;

use DateTime;
use Exception;

class AuthorizationConsumer{
    private Database $database;
    private Session $session;

    private function get_address_statement(array $data): bool|\PDOStatement
    {
        $statement = $this->database->getConnection()->prepare(
            "SELECT * FROM Address WHERE City_name = :City_name and Street = :Street
                        and House = :House and Housing = :Housing"
        );
        $statement->bindParam(':City_name', $data['City_name']);
        $statement->bindParam(':Street', $data['Street']);
        $statement->bindParam(':House', $data['House']);
        $statement->bindParam(':Housing', $data['Housing']);
        $statement->execute();
        return $statement;
    }

    public function __construct(Database $database, $session){
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @throws AuthorizationConsumerException
     * @throws Exception
     */
    public function register(array $data): bool{
        // First_name block
        if (empty($data['First_name'])){
            throw new AuthorizationConsumerException("Имя не может быть пустым!");
        }

        // Last_name block
        if (empty($data['Last_name'])){
            throw new AuthorizationConsumerException("Фамилия не может быть пустой!");
        }

        // Company_email block
        if (empty($data['Consumer_email'])){
            throw new AuthorizationConsumerException("Почта не может быть пустой!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Consumer_email FROM Consumer WHERE Consumer_email = :Consumer_email"
        );
        $statement->bindParam(':Consumer_email', $data['Consumer_email']);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AuthorizationConsumerException("Пользователь с таким email уже существует!");
        }

        // Consumer_password block
        if (empty($data['Consumer_password'])){
            throw new AuthorizationConsumerException("Пароль не может быть пустым!");
        }
        if ($data['Consumer_password'] !== $data['confirm_password']){
            throw new AuthorizationConsumerException("Пароли не совпадают!");
        }

        // Birthday block
        if (empty($data["Birthday"])){
            throw new AuthorizationConsumerException("Дата рождения не может быть пустой!");
        }
        $date_arr = explode("-", $data["Birthday"]);
        $date_arr = array_reverse($date_arr);
        $date_to_fill = implode("-", $date_arr);
//        if ((int)$date_to_fill[1] > 12 or (int)$date_to_fill[1] < 1 or (int)$date_to_fill[2] < 1 or (int)$date_to_fill[2] > 31){
//
//            throw new AuthorizationConsumerException("Неверно введен день или месяц!");
//        }
        $required_date = new DateTime($date_to_fill);
        $today = new DateTime("now");
        if ($required_date > $today){
            throw new AuthorizationConsumerException("Вы из будущего?!");
        }
        $interval = $today->diff($required_date);
        if ($interval->y < 18){
            throw new AuthorizationConsumerException("Несовершеннолетним регистрация запрещена!");
        }

        // Telephone_number
        if (empty($data['Telephone_number'])){
            throw new AuthorizationConsumerException("Телефон не может быть пустым!");
        }
        if (!preg_match("/^([0-9])+$/", $data['Telephone_number'])){
            throw new AuthorizationConsumerException("Телефон должен состоять ТОЛЬКО из цифр!");
        }
        if (strlen($data['Telephone_number']) != 11){
            throw new AuthorizationConsumerException("Телефон должен состоять ровно из 11 цифр!");
        }
        $statement = $this->database->getConnection()->prepare(
            "SELECT Telephone_number FROM ManagementCompany WHERE Telephone_number = :Telephone_number"
        );
        $statement->bindParam(':Telephone_number', $data['Telephone_number']);
        $statement->execute();
        if ($statement->rowCount() > 0){
            throw new AuthorizationConsumerException("Компания с таким телефоном уже существует!");
        }

        //Passport_series block
        if (empty($data['Passport_series'])){
            throw new AuthorizationConsumerException("Серия паспорта не может быть пустой!");
        }
        if ($data['Passport_series'] < 1000 or $data['Passport_series'] > 9999){
            throw new AuthorizationConsumerException("Серия паспорта должна быть 4-х значным числом!");
        }

        //Passport_number block
        if (empty($data['Passport_number'])){
            throw new AuthorizationConsumerException("Номер паспорта не может быть пустым!");
        }
        if ($data['Passport_number'] < 100000 or $data['Passport_number'] > 999999){
            throw new AuthorizationConsumerException("Номер паспорта должен быть 6-ти значным числом!");
        }

        //Living_space block
        if (empty($data['Living_space'])){
            throw new AuthorizationConsumerException("Жилая площадь не может быть пустой!");
        }
        if ($data['Living_space'] <= 0){
            throw new AuthorizationConsumerException("Жилая площадь не может быть отрицательной!");
        }

        //City_name block
        if (empty($data['City_name'])){
            throw new AuthorizationConsumerException("Поле города не может быть пустым!");
        }

        //Street block
        if (empty($data['Street'])){
            throw new AuthorizationConsumerException("Поле улицы не может быть пустым!");
        }

        //House block
        if (empty($data['House'])){
            throw new AuthorizationConsumerException("Номер дома не может быть пустым!");
        }
        if ($data['House'] <= 0){
            throw new AuthorizationConsumerException("Номер дома не может быть отрицательным!");
        }

        if ($data["Housing"] == null){
            $data["Housing"] = 1;
        }
        //Housing block
        if ($data['Housing'] < 0){
            throw new AuthorizationConsumerException("Номер корпуса не может быть отрицательным!");
        }

        //Flat block
        if (empty($data['Flat'])){
            throw new AuthorizationConsumerException("Номер квартиры не может быть пустым!");
        }
        if ($data['Flat'] <= 0){
            throw new AuthorizationConsumerException("Номер квартиры не может быть отрицательным!");
        }

        // insertion block
        $statement = $this->get_address_statement($data);
        $user_address_id = -1;
        if ($statement->rowCount() == 0){
            $statement1 = $this->database->getConnection()->prepare(
                "SELECT Management_company_id from ManagementCompany WHERE Company_name = :Company_name"
            );
            $statement1->bindParam(':Company_name', $data['Company_name']);
            $statement1->execute();
            $chosen_company_id = $statement1->fetch()["Management_company_id"];
            $statement1 = $this->database->getConnection()->prepare(
                "INSERT INTO Address (City_name, Street, House, Housing, Management_company_id)
                    VALUES (:City_name, :Street, :House, :Housing, :Management_company_id)"
            );
            $statement1->execute([
                "City_name" => $data["City_name"],
                "Street" => $data["Street"],
                "House" => $data["House"],
                "Housing" => $data["Housing"],
                "Management_company_id" => $chosen_company_id
            ]);
            $statement1 = $this->get_address_statement($data);
            $user_address_id = $statement1->fetch()["Address_id"];
        }
        else{
            $user_address_id = $statement->fetch()["Address_id"];
        }

        $statement = $this->database->getConnection()->prepare(
            "INSERT INTO Consumer (First_name, Last_name, Patronymic, Consumer_email, 
                      Consumer_password, Birthday, Telephone_number, Passport_series, Passport_number, 
                      Living_space, Address_id, Flat) 
                      VALUES (:First_name, :Last_name, :Patronymic, :Consumer_email, 
                      :Consumer_password, :Birthday, :Telephone_number, :Passport_series, :Passport_number, 
                      :Living_space, :Address_id, :Flat)"
        );
        $statement->execute([
            "First_name" => $data["First_name"],
            "Last_name" => $data["Last_name"],
            "Patronymic" => $data["Patronymic"],
            "Consumer_email" => $data["Consumer_email"],
            "Consumer_password" => password_hash($data['Consumer_password'], PASSWORD_BCRYPT),
            "Birthday" => $date_to_fill,
            "Telephone_number" => $data["Telephone_number"],
            "Passport_series" => $data["Passport_series"],
            "Passport_number" => $data["Passport_number"],
            "Living_space" => $data["Living_space"],
            "Address_id" => $user_address_id,
            "Flat" => $data["Flat"]
        ]);


        return true;
    }

    /**
     * @throws AuthorizationConsumerException
     */
    public function login(string $email_or_phone, string $password): bool{
        if (empty($email_or_phone)){
            throw new AuthorizationConsumerException("Email или телефон пользователя не может быть пустым!");
        }
        if (empty($password)){
            throw new AuthorizationConsumerException("Пароль не может быть пустым!");
        }
        if (str_contains($email_or_phone, "@")){
            $statement = $this->database->getConnection()->prepare(
            // Company_email, Company_password
                "SELECT * from Consumer WHERE Consumer_email = :Company_email"
            );
            $statement->execute([
                "Company_email" => $email_or_phone
            ]);
            $consumer = $statement->fetch();
            if (empty($consumer)){
                throw new AuthorizationConsumerException("Потребителя с таким email нет!");
            }
        }
        else{
            if (!preg_match("/^([0-9])+$/", $email_or_phone)){
                throw new AuthorizationConsumerException("Телефон должен состоять ТОЛЬКО из цифр!");
            }
            if (strlen($email_or_phone) != 11){
                throw new AuthorizationConsumerException("Телефон должен состоять ровно из 11 цифр!");
            }

            $statement = $this->database->getConnection()->prepare(
            // Telephone_number, Company_password
                "SELECT * from Consumer WHERE Telephone_number = :Telephone_number"
            );
            $statement->execute([
                "Telephone_number" => $email_or_phone
            ]);
            $consumer = $statement->fetch();
            if (empty($consumer)){
                throw new AuthorizationConsumerException("Пользователя с таким телефоном нет!");
            }
        }

        if (password_verify($password, $consumer["Consumer_password"])){
            $this->session->setData(
                "user", [
                "Consumer_id" => $consumer["Consumer_id"],
                "First_name" => $consumer["First_name"],
                "Last_name" => $consumer["Last_name"],
                "Patronymic" => $consumer["Patronymic"],
                "Consumer_email" => $consumer["Consumer_email"],
                "Birthday" => $consumer["Birthday"],
                "Telephone_number" => $consumer["Telephone_number"],
                "Passport_series" => $consumer["Passport_series"],
                "Passport_number" => $consumer["Passport_number"],
                "Personal_acc_hcs" => $consumer["Personal_acc_hcs"],
                "Personal_acc_landline_ph" => $consumer["Personal_acc_landline_ph"],
                "Personal_acc_long_dist_ph" => $consumer["Personal_acc_long_dist_ph"],
                "Address_id" => $consumer["Address_id"],
                "Flat" => $consumer["Flat"],
                "Living_space" => $consumer["Living_space"],
                "Is_staff" => $consumer["Is_staff"]
            ]);
            return True;
        }

        throw new AuthorizationConsumerException("Неверный email или пароль!");
    }
}