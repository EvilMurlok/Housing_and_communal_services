<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/ValidationReceiptData.php";

class CreateCommonReceipt extends ValidationReceiptData
{

    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    private array $charge_to_rate_name = [
        "ElectricityСharge" => "Электроснабжение",
        "HotWaterСharge" => "ГВС",
        "ColdWaterСharge" => "ХВС",
        "GasСharge" => "Газ",
        "HeatingСharge" => "Отопление"
    ];

    /**
     * @throws CreateReceiptException
     */
    public function add_receipt(array $data, $user_id, $is_phone = 0): bool
    {
        try {
            $required_parameters = $this->validate_get_receipt_parameters($data, $user_id, $is_phone);
        } catch (CreateReceiptException $exception) {
            throw new CreateReceiptException($exception->getMessage());
        }

        $period_of_receipt = $required_parameters["period_of_receipt"];
        $type_of_receipt = $required_parameters["type_of_receipt"];

        $all_charges = ["ElectricityСharge" => "Electricity_charge_id",
                        "HotWaterСharge" => "Hot_water_charge_id",
                        "ColdWaterСharge" => "Cold_water_charge_id",
                        "GasСharge" => "Gas_charge_id",
                        "HeatingСharge" => "Heating_charge_id"];
        $need_to_receive = [];
        $all_tariff_amounts = [];
        $all_charge_ids = [];
        $all_amounts_of_units = [];

        foreach ($all_charges as $charge_name => $id){
            $statement = $this->database->getConnection()->prepare(
                "SELECT Tariff_amount, {$id}, Amount_of_unit FROM ". $charge_name ."
                    WHERE Consumer_id = :user_id AND Charge_period = :period_of_receipt");
            $statement->execute([
                "user_id" => $user_id,
                "period_of_receipt" => $period_of_receipt
            ]);
            if ($statement->rowCount() == 0){
                $need_to_receive[] = $this->charge_to_rate_name[$charge_name];
            }
            else{
                $charge_info = $statement->fetch();
                $all_amounts_of_units[$id] = $charge_info["Amount_of_unit"];
                $all_charge_ids[$id] = $charge_info[$id];
                $all_tariff_amounts[$id] = $charge_info["Tariff_amount"];
            }
        }

        $consumer_info = $this->database->getConnection()->query(
            "SELECT First_name, Last_name, Consumer_email, Living_space FROM Consumer WHERE Consumer_id = {$user_id}"
        )->fetch();

        if (count($need_to_receive) != 0){
            throw new CreateReceiptException("Необходимо внести показания по пользователю "
                . $consumer_info['First_name'] . " " . $consumer_info["Last_name"]. " ("
                . $consumer_info["Consumer_email"] .")" ." за услуги: ".
                implode(", ", $need_to_receive). "!");
        }

        // deadline date creation block (+ 30 days to current date)
        $deadline_date = date('Y-m-d', strtotime(date("Y-m-d"). ' + 30 days'));

        // $last_month_day = date('t', strtotime(date($period_of_receipt)));

        $all_housing_rates_costs = [
        "Водоотведение" => 0,
        "Взнос на кап. ремонт" => 0,
        "Содержание жил. помещений" => 0,
        "Запирающее устройство" => 0
        ];

        foreach ($all_housing_rates_costs as $service_name => &$cost){
            $tariff_info = $this->database->getConnection()->query(
                "SELECT Unit_cost FROM Rate WHERE Service_name = '". $service_name. "'"
            )->fetch();
            $cost = $tariff_info["Unit_cost"];
        }

        // add all housing services costs to $all_tariff_amounts
        $all_tariff_amounts["Водоотведение"] = ($all_amounts_of_units["Cold_water_charge_id"] +
                                                $all_amounts_of_units["Hot_water_charge_id"]) * $all_housing_rates_costs["Водоотведение"];
        $all_tariff_amounts["Взнос на кап. ремонт"] = $consumer_info["Living_space"] *
                                                       $all_housing_rates_costs["Взнос на кап. ремонт"];
        $all_tariff_amounts["Содержание жил. помещений"] = $consumer_info["Living_space"] *
                                                           $all_housing_rates_costs["Содержание жил. помещений"];
        $all_tariff_amounts["Запирающее устройство"] = $all_housing_rates_costs["Запирающее устройство"];

        $statement = $this->database->getConnection()->prepare(
            "INSERT INTO " . $type_of_receipt . " (Receipt_period, 
                                                        Consumer_id, Electricity_charge_id, 
                                                        Hot_water_charge_id, Cold_water_charge_id, 
                                                        Gas_charge_id, Heating_charge_id,
                                                        Amount_water_disposal, Amount_housing_maintenance,
                                                        Amount_overhaul, Amount_intercom,
                                                        Deadline_date, Overdue_days, 
                                                        Total_summ, Is_paid)
                            VALUES (:receipt_period, :consumer_id, 
                                    :electricity_charge_id, 
                                    :hot_water_charge_id, :cold_water_charge_id, 
                                    :gas_charge_id, :heating_charge_id,
                                    :amount_water_disposal, :amount_housing_maintenance,
                                    :amount_overhaul, :amount_intercom,
                                    :deadline_date, :overdue_days, 
                                    :total_summ, :is_paid)"
        );

        $statement->execute([
            "receipt_period" => $period_of_receipt,
            "consumer_id" => $user_id,
            "electricity_charge_id" => $all_charge_ids["Electricity_charge_id"],
            "hot_water_charge_id" => $all_charge_ids["Hot_water_charge_id"],
            "cold_water_charge_id" => $all_charge_ids["Cold_water_charge_id"],
            "gas_charge_id" =>$all_charge_ids["Gas_charge_id"],
            "heating_charge_id" => $all_charge_ids["Heating_charge_id"],
            "amount_water_disposal" => $all_tariff_amounts["Водоотведение"],
            "amount_housing_maintenance" => $all_tariff_amounts["Взнос на кап. ремонт"],
            "amount_overhaul" => $all_tariff_amounts["Содержание жил. помещений"],
            "amount_intercom" => $all_tariff_amounts["Запирающее устройство"],
            "deadline_date" => $deadline_date,
            "overdue_days" => 0,
            "total_summ" => array_sum($all_tariff_amounts),
            "is_paid" => 0
        ]);


        return true;
    }
}