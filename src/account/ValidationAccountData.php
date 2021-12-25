<?php

namespace App;

class ValidationAccountData{
    protected Database $database;
    protected Session $session;

    public function __construct(Database $database, $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @throws TopUpAccountException
     */
    public function validate_data($data, $user_phone) :array{
        // phone block
        if (empty($data["Consumer_phone"])){
            throw new TopUpAccountException("Телефон не может быть пустым!");
        }
        if (!preg_match("/^([0-9])+$/", $data["Consumer_phone"])){
            throw new TopUpAccountException("Телефон должен состоять ТОЛЬКО из цифр!");
        }
        if (strlen($data["Consumer_phone"]) != 11){
            throw new TopUpAccountException("Телефон должен состоять ровно из 11 цифр!");
        }
        if ($data["Consumer_phone"] != $user_phone){
            throw new TopUpAccountException("Введенный телефон не совпадает с телефоном, привязанным к вашему аккаунту!");
        }

        // amount money block
        if (empty($data["Amount_of_money"])){
            throw new TopUpAccountException("Поле введенной суммы не должно быть пустым!");
        }
        if ($data["Amount_of_money"] <= 0 or $data["Amount_of_money"] > 10000){
            throw new TopUpAccountException("Введенная сумма не должна быть отрицательной или превышать 10000 ₽!");
        }
        return $data;
    }
}