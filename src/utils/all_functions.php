<?php

use Psr\Http\Message\ResponseInterface;
use JetBrains\PhpStorm\ArrayShape;
use App\TakingReadingException;
use App\CreateReceiptException;


function renderPageByQuery($query, $session, $twig, $response, $name_render_page, $name_form = "form", $need_one = 0): ResponseInterface
{
    if ($need_one == 1) {
        $rows = $query->fetch();
    } else {
        $rows = $query->fetchAll();
    }
    $session->setData($name_form, $rows);
    $body = $twig->render($name_render_page, [
        "user" => $session->getData("user"),
        "message" => $session->get_and_set_null("message"),
        "status" => $session->flush("status"),
        $name_form => $session->flush($name_form)
    ]);
    $response->getBody()->write($body);
    return $response;
}

function renderPage($session, $twig, $response, $name_render_page, $name_form = "form"): ResponseInterface
{
    $body = $twig->render($name_render_page, [
        "user" => $session->getData("user"),
        "message" => $session->flush("message"),
        "status" => $session->flush("status"),
        $name_form => $session->flush($name_form)
    ]);
    $response->getBody()->write($body);
    return $response;
}

function checkUserRights($session, $message): bool
{
    if ($session->getData("user") == null or $session->getData("user")["Is_staff"] != 1) {
        $session->setData("message", $message);
        $session->setData("status", "danger");
        return false;
    }
    return true;
}

#[ArrayShape(["types" => "string[]", "months" => "string[]", "years" => "array", "template_name" => "string"])]
function getRequiredReadingsParameters($session): array
{
    if ($session->getData("user")["Is_staff"] == 1) {
        $types = ["Горячее водоснабжение", "Холодное водоснабжение", "Электроэнергия", "Отопление", "Газ"];
        $template_name = "readings/taking-reading-by-mc.twig";
    } else {
        $types = ["Горячее водоснабжение", "Холодное водоснабжение", "Электроэнергия"];
        $template_name = "readings/taking-readings.twig";
    }
    $months = ["Январь", "Февраль", "Март", "Апрель", "Май",
        "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
    $years = [];
    for ($i = 2019; $i <= (int)date("Y"); ++$i) {
        $years[] = $i;
    }
    return [
        "types" => $types,
        "months" => $months,
        "years" => $years,
        "template_name" => $template_name
    ];
}

#[ArrayShape(["types" => "string[]", "months" => "string[]", "years" => "array"])]
function getRequiredReceiptParameters(): array
{
    $types = ["Квитанция междугородний телефон", "Квитанция городской телефон"];
    $months = ["Январь", "Февраль", "Март", "Апрель", "Май",
        "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
    $years = [];
    for ($i = 2019; $i <= (int)date("Y"); ++$i) {
        $years[] = $i;
    }
    return [
        "types" => $types,
        "months" => $months,
        "years" => $years
    ];
}

function renderRequiredReceiptForm($response, $database, $twig, &$session, $template_name, $consumer_id)
{
    $consumer_info = $database->getConnection()->query(
        "SELECT Consumer_id, First_name, Last_name, Consumer_email 
                       FROM Consumer 
                       WHERE Consumer_id = $consumer_id"
    )->fetch();
    $required_parameters = getRequiredReceiptParameters();
    $body = $twig->render($template_name, [
        "user" => $session->getData("user"),
        "message" => $session->flush("message"),
        "form" => $session->flush("form"),
        "status" => $session->flush("status"),
        "types" => $required_parameters["types"],
        "months" => $required_parameters["months"],
        "years" => $required_parameters["years"],
        "consumer_info" => $consumer_info
    ]);
    $response->getBody()->write($body);
    return $response;
}

function fulfill_reading_post_request($request, &$session, $add_reading, $user_id)
{
    $params = (array)$request->getParsedBody($user_id);
    try {
        $add_reading->add_reading($params, $user_id);
        $session->setData("message", "Показание за услугу: '" . $params["Reading_type"] . "' успешно внесено!");
        $session->setData("status", "success");
    } catch (TakingReadingException $exception) {
        $session->setData("message", $exception->getMessage());
        $session->setData("status", "danger");
        $session->setData("form", $params);
    }
}

function get_lists_of_consumers($database, $twig, $session, $response, $template_name): ResponseInterface
{
    $query = $database->getConnection()->query(
        "SELECT Consumer_id, First_name, Last_name, Patronymic, 
                              Consumer_email, Birthday, Telephone_number
                       FROM Consumer c INNER JOIN Address a USING(Address_id)
                       WHERE a.Management_company_id = {$session->getData("user")["Management_company_id"]}
                       ORDER BY Last_name, First_name"
    );
    return renderPageByQuery($query, $session, $twig, $response, $template_name, "consumers");
}

function fulfill_receipts_post_request($request, &$session, $add_receipt, $user_id, $is_phone)
{
    $params = (array)$request->getParsedBody($user_id);
    try {
        $add_receipt->add_receipt($params, $user_id);
        if ($is_phone == 1) {
            $session->setData("message", "Квитанция: '" . $params["Receipt_type"] . "' успешно создана!");
        } else {
            $session->setData("message", "Квитанция: 'Общая квитанция ЖКУ' успешно создана!");
        }
        $session->setData("status", "success");
    } catch (CreateReceiptException $exception) {
        $session->setData("message", $exception->getMessage());
        $session->setData("status", "danger");
        $session->setData("form", $params);
    }
}

/**
 * @throws Exception
 */
#[ArrayShape(["Overdue_days" => "", "Total_summ" => ""])]
function change_total_summ($deadline_date, $overdue_days, $total_tariff_amount): array
{
    $new_total_summ = $total_tariff_amount;
    $new_overdue_days = $overdue_days;
    if ($deadline_date < date('Y-m-d', strtotime($deadline_date. ' + 40 days'))){
        $new_overdue_days = date_diff(new DateTime($deadline_date),
            new DateTime(date('Y-m-d', strtotime($deadline_date. ' + 40 days'))))->days;
        $new_total_summ = ($new_overdue_days * 0.001 + 1) * $total_tariff_amount;
    }
    return [
        "Overdue_days" => $new_overdue_days,
        "Total_summ" => $new_total_summ
    ];
}

/**
 * @throws Exception
 */
function show_receipts($request, $response, $twig, $database, &$session, $user_id, $is_phone, $is_paid): ResponseInterface
{
    $consumer_info = $database->getConnection()->query(
        "SELECT First_name, Last_name, Patronymic, Telephone_number, Living_space, City_name, Street, Housing, House, Flat
             FROM Consumer INNER JOIN Address A on Consumer.Address_id = A.Address_id
             WHERE Consumer_id = $user_id"
    )->fetch();

    $convert_to_english_rate = [
        "ГВС" => "hot_water",
        "ХВС" => "cold_water",
        "Водоотведение" => "water_disposal",
        "Отопление" => "heating",
        "Электроснабжение" => "electricity",
        "Газ" => "gas",
        "Взнос на кап. ремонт" => "overhaul",
        "Содержание жил. помещений" => "housing_maintenance",
        "Запирающее устройство" => "intercom",
        "Междугородние звонки" => "long_distance_phone",
        "Городские звонки" => "landline_phone"
    ];
    $rates_info = $database->getConnection()->query(
        "SELECT Service_name, Unit, Unit_cost FROM Rate"
    )->fetchAll();

    $all_rates_info = [];
    foreach ($rates_info as $value){
        $all_rates_info[$convert_to_english_rate[$value["Service_name"]]] = $value;
    }

    if ($is_phone == 1) {
        $receipts_info = $database->getConnection()->query(
            "SELECT  Receipt_id, Amount_of_minutes, Receipt_period,
                     Tariff_amount as Total_tariff_amount, Deadline_date, 
                     Service_name, Unit, Unit_cost,
                     Payment_date, Overdue_days, Total_summ, Is_paid
             FROM ReceiptCityPhone
             INNER JOIN Rate USING(Rate_id)
             WHERE Consumer_id = $user_id AND Is_paid = $is_paid
             UNION
             SELECT  Receipt_id, Amount_of_minutes, Receipt_period,
                     Tariff_amount as Total_tariff_amount, Deadline_date, 
                     Service_name, Unit, Unit_cost,
                     Payment_date, Overdue_days, Total_summ, Is_paid
             FROM ReceiptDistancePhone
             INNER JOIN Rate USING(Rate_id)
             WHERE Consumer_id = $user_id AND Is_paid = $is_paid"
        )->fetchAll();
    } else {
        $receipts_info = $database->getConnection()->query(
            "SELECT  Receipt_HCS_id, Receipt_period, Amount_water_disposal, Amount_housing_maintenance, Amount_overhaul, Amount_intercom, 
		             Deadline_date, Overdue_days, Total_summ, Payment_date,
                     rh.Tariff_amount AS Total_tariff_amount,
		             ec.Amount_of_unit as electricity_unit, ec.Tariff_amount as electricity_tariff,
		             hwс.Amount_of_unit as hot_water_unit, hwс.Tariff_amount as hot_water_tariff,
		             cwс.Amount_of_unit as cold_water_unit, cwс.Tariff_amount as cold_water_tariff,
		             gс.Amount_of_unit as gas_unit, gс.Tariff_amount as gas_tariff,
		             hс.Amount_of_unit as heating_unit, hс.Tariff_amount as heating_tariff, Is_paid
		    FROM ReceiptHCS rh 
            INNER JOIN Consumer USING(Consumer_id)
            INNER JOIN ElectricityСharge ec USING(Electricity_charge_id)
            INNER JOIN HotWaterСharge hwс USING(Hot_water_charge_id)
            INNER JOIN ColdWaterСharge cwс USING(Cold_water_charge_id)
            INNER JOIN GasСharge gс USING(Gas_charge_id)
            INNER JOIN HeatingСharge hс USING(Heating_charge_id)
            WHERE rh.Consumer_id = $user_id and Is_paid = $is_paid"
        )->fetchAll();
    }

    $required_id = ["Receipt_HCS_id", "Receipt_id"];

    if ($is_paid == 0){
        foreach ($receipts_info as &$receipt){
            if (count($receipt) >= 14){
                $table_name = "ReceiptHCS";
            }
            elseif ($receipt["Service_name"] == "Городские звонки"){
                $table_name = "ReceiptCityPhone";
            }
            else{
                 $table_name = "ReceiptDistancePhone";
            }
            $new_data = change_total_summ($receipt["Deadline_date"], $receipt["Overdue_days"], $receipt["Total_tariff_amount"]);
            if ($new_data["Overdue_days"] != 0){
                $receipt["Overdue_days"] = $new_data["Overdue_days"];
                $receipt["Total_summ"] = $new_data["Total_summ"];
                $database->getConnection()->query(
                    "UPDATE ". $table_name ." SET Overdue_days={$receipt["Overdue_days"]},
                                               Total_summ={$receipt["Total_summ"]}
                        WHERE ". $required_id[$is_phone] ."={$receipt[$required_id[$is_phone]]}"
                );
            }
        }
    }

    $required_template = ["receipts/show-common-receipts.twig", "receipts/show-phone-receipts.twig"];
    file_put_contents("logs.txt", count($receipts_info));
    $body = $twig->render($required_template[$is_phone], [
        "user" => $session->getData("user"),
        "message" => $session->get_and_set_null("message"),
        "status" => $session->flush("status"),
        "receipts" => $receipts_info,
        "rates" => $all_rates_info,
        "consumer_info" => $consumer_info
    ]);

    $response->getBody()->write($body);
    return $response;
}