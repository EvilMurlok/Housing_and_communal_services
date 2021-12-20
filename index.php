<?php
header("Content-Type: text/html; charset=utf-8");
error_reporting(-1);
require __DIR__ . "/vendor/autoload.php";

use App\AdditionNews;
use App\AdditionNewsException;
use App\AdditionOrganization;
use App\AdditionOrganizationException;
use App\AuthorizationConsumer;
use App\AuthorizationConsumerException;
use App\AuthorizationEntity;
use App\AuthorizationEntityException;
use App\Database;
use App\EditionNews;
use App\EditionOrganization;
use App\Session;

use App\TakingReading;
use App\TakingReadingException;
use App\TopUpAccount;
use App\TopUpAccountException;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Slim\Factory\AppFactory;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Database.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Session.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/authorization/AuthorizationConsumer.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/authorization/AuthorizationConsumerException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/authorization/AuthorizationEntity.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/authorization/AuthorizationEntityException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionNews/AdditionNews.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionNews/AdditionNewsException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionNews/EditionNews.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionOrganization/AdditionOrganization.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionOrganization/EditionOrganization.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionOrganization/AdditionOrganizationException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/account/TopUpAccount.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/account/TopUpAccountException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/readings/TakingReading.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/readings/TakingReadingException.php";

$config = include_once "/Users/macbookair/Desktop/Housing_and_communal_services/config/databaseInfo.php";
$dsn = $config["dsn"];
$username = $config["username"];
$password = $config["password"];
$database = new Database($dsn, $username, $password);

$loader = new FilesystemLoader("templates");
$twig = new Environment($loader);

$session = new Session();
// будет отрабатываться для каждого запроса get или post, определенного снизу
// и где бы мы ни захотели воспользоваться сессией, она уже будет создана
$sessionMiddleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($session, $database) {
    $session->start();
    if(isset($_COOKIE["password_cookie_token"]) && !empty($_COOKIE["password_cookie_token"])){
        var_dump(
            "WE ARE HERE!"
        );
        $query = $database->getConnection()->query(
            "SELECT *
                           FROM Consumer
                           WHERE Password_cookie_token = '".$_COOKIE["password_cookie_token"]."'");
        $select_user_data = $query->fetch();
        if(!$select_user_data){
            var_dump("SOMETHING WENT WRONG!");
        }else{
            $session->setData(
                "user", [
                "Consumer_id" => $select_user_data["Consumer_id"],
                "First_name" => $select_user_data["First_name"],
                "Last_name" => $select_user_data["Last_name"],
                "Consumer_email" => $select_user_data["Consumer_email"],
                "Telephone_number" => $select_user_data["Telephone_number"],
                "Is_staff" => $select_user_data["Is_staff"]
            ]);
        }
    }
//    $session_timeout = 1200; // in seconds
//    if (!isset($_SESSION['last_visit'])) {
//        $_SESSION['last_visit'] = time();
//    }
//    if((!isset($_COOKIE["password_cookie_token"]) || empty($_COOKIE["password_cookie_token"]))
//        && isset($_SESSION['user'])
//        && ((time() - $_SESSION['last_visit']) > $session_timeout))
//    {
//        unset($_SESSION['last_visit']);
//        unset($_SESSION);
//        header("Location: /logout/");
//        exit;
//    }
//    $_SESSION['last_visit'] = time();
    $response = $handler->handle($request);
    $session->save();
    var_dump($_SESSION);
    var_dump($_COOKIE);
    return $response;
};

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware(); // $_POST
$app->add($sessionMiddleware);


// authorization block
$authorization_entity = new AuthorizationEntity($database, $session);
$authorization_consumer = new AuthorizationConsumer($database, $session);

// addition edition news block
$add_news = new AdditionNews($database, $session);
$edit_news = new EditionNews($database, $session);

// addition edition organization block
$add_organization = new AdditionOrganization($database, $session);
$edit_organization = new EditionOrganization($database, $session);

// top up an account block
$top_up_account = new TopUpAccount($database, $session);

// taking_reading block
$add_reading = new TakingReading($database, $session);

function renderPageByQuery($query, $session, $twig, $response, $name_render_page, $name_form = "form", $need_one = 0): ResponseInterface
{
    $rows = null;
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

function checkUserRights($session, $response, $message): bool
{
    if ($session->getData("user") == null or $session->getData("user")["Is_staff"] != 1) {
        $session->setData("message", $message);
        $session->setData("status", "danger");
        return false;
    }
    return true;
}

#[ArrayShape(["types" => "string[]", "months" => "string[]", "years" => "array", "template_name" => "string"])]
function getRequiredParameters($session) :array{
    if ($session->getData("user")["Is_staff"] == 1){
        $types = ["Горячее водоснабжение", "Холодное водоснабжение", "Электроэнергия", "Отопление", "Газ"];
        $template_name = "readings/taking-reading-by-mc.twig";
    }
    else{
        $types = ["Горячее водоснабжение", "Холодное водоснабжение", "Электроэнергия"];
        $template_name = "readings/taking-readings.twig";
    }
    $months = ["Январь", "Февраль", "Март", "Апрель", "Май",
        "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
    $years = [];
    for ($i = 2019; $i <= (int) date("Y"); ++$i){
        $years[] = $i;
    }
    return [
        "types" => $types,
        "months" => $months,
        "years" => $years,
        "template_name" => $template_name
    ];
}

function fulfill_post_request($request, &$session, $add_reading, $user_id) {
    $params = (array) $request->getParsedBody($user_id);
    try {
        $add_reading->add_reading($params, $user_id);
        $session->setData("message", "Показание за услугу: '" . $params["Reading_type"] . "' успешно внесено!");
        $session->setData("status", "success");
    }
    catch (TakingReadingException $exception){
        $session->setData("message", $exception->getMessage());
        $session->setData("status", "danger");
        $session->setData("form", $params);
    }
}
// такие callback-функции должны возвращать строго $response!
$app->get("/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session, $database) {

        $query = $database->getConnection()->query(
            "SELECT News_id, Title, Content, Is_published, Created_at FROM News 
                   WHERE Is_published = 1
                   ORDER BY Created_at DESC LIMIT 8"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "index.twig", "form_news");
    });

$app->get("/rates/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session, $database) {
        $query = $database->getConnection()->query(
            "SELECT r.Resource_organization_id, r.Service_name, r.Unit, 
                              r.Unit_cost, o.Organization_name, 
                              o.Telephone_number, o.Organization_email
                       FROM Rate r
                       INNER JOIN ResourceOrganization o using (Resource_organization_id)
                       ORDER BY r.Service_name
                       LIMIT 4"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/rates-info.twig", "rates");
    });

$app->get("/view-news/{news_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        $query = $database->getConnection()->query(
            "SELECT News_id, Title, Content, Is_published, Created_at FROM News
                   WHERE News_id = {$args['news_id']}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/view-news.twig", "form_news", 1);
    });

$app->get("/edit-news/{news_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!checkUserRights($session, $response, "Обычные пользователи не могут редактировать новости")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT News_id, Title, Content, Is_published, Created_at FROM News
                   WHERE News_id = {$args['news_id']}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "addition/edit-news.twig", "form_news", 1);
    });

$app->post("/edit-news-post/{news_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($edit_news, $session) {
        $params = (array)$request->getParsedBody(); // вернет все параметры, переданные через POST
        try {
            $edit_news->edit_news($params, $args["news_id"]);
            $session->setData("message", "Новость успешно отредактирована!");
            $session->setData("status", "success");
        } catch (AdditionNewsException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form_news", $params);
            return $response->withHeader("Location", "/edit-news/{$args['news_id']}/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get('/delete-news/{news_id}/',
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session) {
        if (!checkUserRights($session, $response, "Обычные пользователи не могут удалять новости")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $required_news_id = $args["news_id"];
        $database->getConnection()->query(
            "DELETE FROM News WHERE News_id = $required_news_id"
        );
        $session->setData("message", "Новость успешно удалена!");
        $session->setData("status", "success");
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/add-news/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        if (!checkUserRights($session, $response, "Обычные пользователи не могут добавлять новости")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        return renderPage($session, $twig, $response, "addition/add-news.twig", "form_news");
    });

$app->post("/add-news-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($add_news, $session) {
        $params = (array)$request->getParsedBody(); // вернет все параметры, переданные через POST
        try {
            $add_news->add_news($params);
            $session->setData("message", "Новость успешно создана!");
            $session->setData("status", "success");
        } catch (AdditionNewsException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form_news", $params);
            return $response->withHeader("Location", "/add-news/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/login-consumer/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $database, $session) {
        return renderPage($session, $twig, $response, "authorization/login-consumer.twig");
    });

$app->post("/login-consumer-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($authorization_consumer, $session) {
        $params = (array)$request->getParsedBody();
        $remember_me = "off";
        if (isset($params["Remember_me"])){
            $remember_me = "on";
        }
        try {
            $authorization_consumer->login($params['Consumer_phone_email'], $params['Consumer_password'], $remember_me);
        } catch (AuthorizationConsumerException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form", $params['Consumer_phone_email']);
            return $response->withHeader("Location", "/login-consumer/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/register-consumer/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session, $database) {
        $query = $database->getConnection()->query(
            "SELECT Company_name from ManagementCompany"
        );
        $mcs = $query->fetchAll();
        $mcs_arr = [];
        foreach ($mcs as $value) {
            $mcs_arr[] = $value;
        }
        $session->setData("mcs", $mcs_arr);
        $body = $twig->render("authorization/register-consumer.twig", [
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "mcs" => $session->flush("mcs"),
            "status" => $session->flush("status")
        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->post("/register-consumer-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($authorization_consumer, $session) {

        $params = (array)$request->getParsedBody(); // вернет все параметры, переданные через POST
        try {
            $authorization_consumer->register($params);
            $session->setData("message", "Пользователь успешно создан, войдите в аккаунт!");
            $session->setData("status", "success");

        } catch (AuthorizationConsumerException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form", $params);
            return $response->withHeader("Location", "/register-consumer/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/login-entity/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        return renderPage($session, $twig, $response, "authorization/login-entity.twig", "form");
    });

$app->post("/login-entity-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($authorization_entity, $session) {
        $params = (array)$request->getParsedBody();
        try {
            $authorization_entity->login($params['Company_phone_email'], $params['Company_password']);
        } catch (AuthorizationEntityException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("form", $params);
            $session->setData("status", "danger");
            return $response->withHeader("Location", "/login-entity/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/register-entity/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        return renderPage($session, $twig, $response, "authorization/register-entity.twig", "form");
    });

$app->post("/register-entity-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($authorization_entity, $session) {

        $params = (array)$request->getParsedBody(); // вернет все параметры, переданные через POST
        try {
            $authorization_entity->register($params);
            $session->setData("message", "Управляющая компания успешно создана, войдите в аккаунт!");
            $session->setData("status", "success");
        } catch (AuthorizationEntityException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("form", $params);
            $session->setData("status", "danger");
            return $response->withHeader("Location", "/register-entity/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/logout/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session, $twig, $database) {
        if(isset($_COOKIE["password_cookie_token"]) && !empty($_COOKIE["password_cookie_token"])){
            $database->getConnection()->query(
                "UPDATE Consumer 
                           SET password_cookie_token = '' 
                           WHERE Consumer_email = '".$_SESSION["user"]["Consumer_email"]."'"
            );
            setcookie("password_cookie_token", "", -time() + 60, "/");
        }
        $_SESSION = array();
        header("Location: /");
        exit;
    });

$app->get("/contacts/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        return renderPage($session, $twig, $response, "info/contacts.twig", "some_form");
    });

// тут запросы к нескольким таблицам!
$app->get("/view-consumer/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $twig, $session) {
        $query = $database->getConnection()->query(
            "SELECT C.First_name, C.Last_name, C.Patronymic, C.Birthday, C.Telephone_number, C.Consumer_email,
                              C.Passport_series, C.Passport_number, C.Flat, 
                              A.City_name, A.Street, A.House, A.Housing, MC.Company_name, MC.Address, MC.Full_name_boss,
                              MC.Company_email, MC.Telephone_number
                       FROM Consumer AS C 
                       INNER JOIN Address A ON C.Address_id = A.Address_id 
                       INNER JOIN ManagementCompany MC on A.Management_company_id = MC.Management_company_id
                       WHERE C.Consumer_id = {$args["consumer_id"]}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/consumer-info.twig", "consumer_info", 1);
    });

$app->get("/consumer-list/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig) {
        $query = $database->getConnection()->query(
            "SELECT Consumer_id, First_name, Last_name, Patronymic, 
                              Consumer_email, Birthday, Telephone_number
                       FROM Consumer 
                       ORDER BY Last_name, First_name LIMIT 4"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/consumer-list.twig", "consumers");
    });

$app->get("/read-more-consumer/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $twig, $session) {
        $query = $database->getConnection()->query(
            "SELECT C.First_name, C.Last_name, C.Patronymic,
                              C.Consumer_email, C.Birthday, C.Telephone_number,
                              C.Passport_series, C.Passport_number, C.Flat, C.Living_space,
                              C.Personal_acc_hcs, C.Personal_acc_landline_ph, C.Personal_acc_long_dist_ph,
                              A.City_name, A.Street, A.House, A.Housing
                       FROM Consumer AS C 
                       INNER JOIN Address A ON C.Address_id = A.Address_id 
                       WHERE C.Consumer_id = {$args["consumer_id"]}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/read-more-consumer.twig", "consumer_info", 1);
    });

$app->get("/add-organization/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        if (!checkUserRights($session, $response, "Обычные пользователи не могут добавлять организации!")) {
            return $response->withHeader("Location", "/organization-list/")->withStatus(302);
        }
        return renderPage($session, $twig, $response,
            "addition/add-organization.twig", "form_org");
    });

$app->post("/add-organization-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($add_organization, $session) {
        $params = (array)$request->getParsedBody(); // вернет все параметры, переданные через POST
        try {
            $add_organization->add_organization($params);
            $session->setData("message", "Организация успешно создана!");
            $session->setData("status", "success");
        } catch (AdditionOrganizationException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form_org", $params);
            return $response->withHeader("Location", "/add-organization/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/organization-list/")
            ->withStatus(302);
    });

$app->get("/organization-list/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig) {
        $query = $database->getConnection()->query(
            "SELECT Resource_organization_id, Organization_name, Telephone_number, Organization_email, 
                              Organization_link, Bank_details, Address
                       FROM ResourceOrganization
                       ORDER BY Organization_name"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/organization-list.twig", "organizations");
    });

$app->get("/view-organization/{organization_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        $query = $database->getConnection()->query(
            "SELECT Resource_organization_id, Organization_name, Telephone_number, Organization_email, 
                              Organization_link, Bank_details, Address 
                       FROM ResourceOrganization
                       WHERE Resource_organization_id = {$args["organization_id"]}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/view-organization.twig", "form_org", 1);
    });

$app->get("/edit-organization/{organization_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!checkUserRights($session, $response, "Обычные пользователи не могут обновлять информацию об организации!")) {
            return $response->withHeader("Location", "/organization-list/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT Resource_organization_id, Organization_name, Telephone_number, Organization_email, 
                              Organization_link, Bank_details, Address 
                       FROM ResourceOrganization
                       WHERE Resource_organization_id = {$args["organization_id"]}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "addition/edit-organization.twig", "form_org", 1);
    });

$app->post("/edit-organization-post/{organization_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($edit_organization, $session) {
        $params = (array)$request->getParsedBody(); // вернет все параметры, переданные через POST
        try {
            $edit_organization->edit_organization($params, $args["organization_id"]);
            $session->setData("message", "Информация об организации успешно обновлена!");
            $session->setData("status", "success");
        } catch (AdditionOrganizationException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form_org", $params);
            return $response->withHeader("Location", "/edit-organization/{$args["organization_id"]}/")
                ->withStatus(302);
        }
        return $response->withHeader("Location", "/organization-list/")
            ->withStatus(302);
    });

$app->get("/delete-organization/{organization_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session) {
        if (!checkUserRights($session, $response, "Обычные пользователи не могут удалять информацию об организации!")) {
            return $response->withHeader("Location", "/organization-list/")->withStatus(302);
        }
        $required_organization_id = $args["organization_id"];
        $database->getConnection()->query(
            "DELETE FROM ResourceOrganization WHERE Resource_organization_id = $required_organization_id"
        );
        $session->setData("message", "Организация успешно удалена!");
        $session->setData("status", "success");
        return $response->withHeader("Location", "/organization-list/")
            ->withStatus(302);
    });

$app->get("/top-up-an-account/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig){
        $choices = ["Общий счет ЖКУ", "Городской телефон", "Междугородний телефон"];
        $all_accounts = $database->getConnection()->query("
            SELECT Personal_acc_hcs, Personal_acc_landline_ph, Personal_acc_long_dist_ph 
            FROM Consumer
            WHERE Telephone_number = '" . $session->getData('user')['Telephone_number'] . "'"
        )->fetch();
        $body = $twig->render("account/top-up-an-account.twig", [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "status" => $session->flush("status"),
            "choices" => $choices,
            "accounts" => $all_accounts
        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->post("/top-up-an-account-post/",
    function(ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $top_up_account){
        $params = (array)$request->getParsedBody();
        try {
            $top_up_account->top_up_account($params, $session->getData("user")["Telephone_number"]);
            $session->setData("message", "Счет: '" . $params["Account_type"] . "' успешно пополнен!");
            $session->setData("status", "success");
        }
        catch (TopUpAccountException $exception){
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form", $params);
        }
        return $response->withHeader("Location", "/top-up-an-account/")
            ->withStatus(302);
    });


$app->get("/add-reading/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig){
        $required_parameters = getRequiredParameters($session);
        $body = $twig->render($required_parameters["template_name"], [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "status" => $session->flush("status"),
            "types" => $required_parameters["types"],
            "months" => $required_parameters["months"],
            "years" => $required_parameters["years"]
        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->get("/add-reading/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig){
        $required_parameters = getRequiredParameters($session);
        $body = $twig->render($required_parameters["template_name"], [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "status" => $session->flush("status"),
            "types" => $required_parameters["types"],
            "months" => $required_parameters["months"],
            "years" => $required_parameters["years"],
            "consumer_id" => $args["consumer_id"]
        ]);
        $response->getBody()->write($body);
        return $response;
    });


$app->post("/add-readings-post/",
    function(ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $add_reading){
        fulfill_post_request($request, $session, $add_reading, $session->getData("user")["Consumer_id"]);
        return $response->withHeader("Location", "/add-reading/")
            ->withStatus(302);
    });

$app->post("/add-readings-post/{consumer_id}/",
    function(ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $add_reading){
        fulfill_post_request($request, $session, $add_reading, $args['consumer_id']);
        return $response->withHeader("Location", "/add-reading/{$args['consumer_id']}/")
            ->withStatus(302);
    });


$app->get("/list-consumers-of-management-company/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig){
        $query = $database->getConnection()->query(
            "SELECT Consumer_id, First_name, Last_name, Patronymic, 
                              Consumer_email, Birthday, Telephone_number
                       FROM Consumer c INNER JOIN Address a USING(Address_id)
                       WHERE a.Management_company_id = {$session->getData("user")["Management_company_id"]}
                       ORDER BY Last_name, First_name"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "readings/consumers-of-management-company.twig", "consumers");
});
$app->run();