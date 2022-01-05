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
use App\CreateCommonReceipt;
use App\CreatePhoneReceipt;
use App\Database;
use App\EditionNews;
use App\EditionOrganization;
use App\EditReading;
use App\ReceiptPayment;
use App\ReceiptPaymentException;
use App\Session;

use App\TakingReading;
use App\TopUpAccount;
use App\TopUpAccountException;

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
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/CreateCommonReceipt.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/CreatePhoneReceipt.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/CreateReceiptException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/ReceiptPayment.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/receipts/ReceiptPaymentException.php";


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
    $session_timeout = 120; // in seconds
    if (!isset($_SESSION['last_visit'])) {
        $_SESSION['last_visit'] = time();
    }
    if((!isset($_COOKIE["password_cookie_token"]) || empty($_COOKIE["password_cookie_token"]))
        && isset($_SESSION['user'])
        && ((time() - $_SESSION['last_visit']) > $session_timeout))
    {
        unset($_SESSION['last_visit']);
        unset($_SESSION['user']);
        header("Location: /logout/");
        exit;
    }
    $_SESSION['last_visit'] = time();
    $response = $handler->handle($request);
    $session->save();
//    var_dump($_SESSION);
//    var_dump($_COOKIE);
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
//$edit_reading = new EditReading($database, $session);

// creation_receipts block
$add_common_receipt = new CreateCommonReceipt($database, $session);
$add_phone_receipt = new CreatePhoneReceipt($database, $session);

// receipt payment block
$receipt_payment = new ReceiptPayment($database);

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/utils/all_functions.php";

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

$app->get("/contacts/{consumer_id}/",
    function(ServerRequestInterface $request, ResponseInterface $response, $args) use ($twig, $session, $database){
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer", "Consumer_id", $args['consumer_id']) == false){
            return notfoundPageRedirection($session, $response);
        }
        $query = $database->getConnection()->query(
            "SELECT mc.Company_name, mc.Full_name_boss, 
                              mc.Company_email, mc.Company_link,
                              mc.Telephone_number, mc.Address FROM Consumer c
                                           INNER JOIN Address a USING(Address_id)
                                           INNER JOIN ManagementCompany mc USING(Management_company_id)
                       WHERE c.Consumer_id = {$args['consumer_id']}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "info/contacts.twig", "contact_form", 1);
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
        if (checkAvailableRecords($database, "News", "News_id", $args['news_id']) == false){
            notfoundPageRedirection($session, $response);
        }
        else{
            $news_info = $database->getConnection()->query(
                "SELECT News_id, Title, Content, Is_published, Created_at FROM News
                   WHERE News_id = {$args['news_id']}"
            )->fetch();
            $session->setData("form_news", $news_info);
            $body = $twig->render("info/view-news.twig", [
                "user" => $session->getData("user"),
                "message" => $session->get_and_set_null("message"),
                "status" => $session->flush("status"),
                "form_news" => $session->flush("form_news")
            ]);
        }
        $response->getBody()->write($body);
        return $response;
    });

$app->get("/edit-news/{news_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!checkUserRights($session, "Обычные пользователи не могут редактировать новости")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "News", "News_id", $args['news_id']) == false){
            notfoundPageRedirection($session, $response);
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
            $edit_news->edit_news($params, $args["news_id"], );
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
        if (!checkUserRights($session, "Обычные пользователи не могут удалять новости!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "News", "News_id", $args['news_id']) == false){
            return notfoundPageRedirection($session, $response);
        }
        $params = $request->getQueryParams();
        if ($params["block"] == "on"){
            $database->getConnection()->query("LOCK TABLES News WRITE;");
        }
        sleep(10);
        $required_news_id = $args["news_id"];
        $database->getConnection()->query(
            "DELETE FROM News WHERE News_id = $required_news_id"
        );

        if ($params["block"] == "on")  {
            $database->getConnection()->query("UNLOCK TABLES;");
        }
        $session->setData("message", "Новость успешно удалена!");
        $session->setData("status", "success");
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/add-news/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        if (!checkUserRights($session, "Обычные пользователи не могут добавлять новости")) {
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
        return renderPage($session, $twig, $response, "authorization/login-entity.twig");
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
        return renderPage($session, $twig, $response, "authorization/register-entity.twig");
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
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer", "Consumer_id", $args['consumer_id']) == false){
            return notfoundPageRedirection($session, $response);
        }
        $query = $database->getConnection()->query(
            "SELECT C.First_name, C.Last_name, C.Patronymic, C.Birthday, C.Telephone_number, C.Consumer_email,
                              C.Passport_series, C.Passport_number, C.Flat, C.Personal_acc_hcs, C.Personal_acc_landline_ph, 
                              C.Personal_acc_long_dist_ph,
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
        if (!checkUserRights($session, "Пользователи без особых прав не могут просматривать список потребителей!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
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
        if (!checkUserRights($session, "Пользователи без особых прав не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer", "Consumer_id", $args['consumer_id']) == false){
            return notfoundPageRedirection($session, $response);
        }
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
        if (!checkUserRights($session, "Обычные пользователи не могут добавлять организации!")) {
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
        if (checkAvailableRecords($database, "ResourceOrganization",
                "Resource_organization_id", $args["organization_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
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
        if (!checkUserRights($session,  "Обычные пользователи не могут обновлять информацию об организации!")) {
            return $response->withHeader("Location", "/organization-list/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "ResourceOrganization",
                "Resource_organization_id", $args["organization_id"]) == false){
            return notfoundPageRedirection($session, $response);
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
        if (!checkUserRights($session, "Обычные пользователи не могут удалять информацию об организации!")) {
            return $response->withHeader("Location", "/organization-list/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "ResourceOrganization",
                "Resource_organization_id", $args["organization_id"]) == false){
            return notfoundPageRedirection($session, $response);
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
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        return top_up_account($request, $response, $database, $session, $twig);
    });

$app->post("/top-up-an-account-post/",
    function(ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $top_up_account){
        $params = (array)$request->getParsedBody();
        try {
            $top_up_account->top_up_account($params, $session->getData("user")["Telephone_number"], $params["block"]);
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

$app->get("/show-readings/{consumer_id}/",
    function(ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig){
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        $all_info = show_consumer_readings($database, $args["consumer_id"], $session);
        $body = $twig->render("readings/show-readings.twig",[
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "status" => $session->flush("status"),
            "readings" => $all_info[1],
            "consumer_info" => $all_info[0]
        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->get("/add-reading/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig){
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $required_parameters = getRequiredReadingsParameters($session);
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
        if (!checkUserRights($session, "Пользователи без особых прав не могут вносить за других показания!")) {
            return $response->withHeader("Location", "/organization-list/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        $consumer_info = $database->getConnection()->query(
            "SELECT Consumer_id, First_name, Last_name, Consumer_email 
                       FROM Consumer 
                       WHERE Consumer_id = {$args['consumer_id']}"
        )->fetch();
        $required_parameters = getRequiredReadingsParameters($session);
        $body = $twig->render($required_parameters["template_name"], [
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
    });


$app->post("/add-readings-post/",
    function(ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $add_reading){
        fulfill_reading_post_request($request, $session, $add_reading, $session->getData("user")["Consumer_id"]);
        return $response->withHeader("Location", "/add-reading/")
            ->withStatus(302);
    });

$app->post("/add-readings-post/{consumer_id}/",
    function(ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $add_reading){
        fulfill_reading_post_request($request, $session, $add_reading, $args['consumer_id']);
        return $response->withHeader("Location", "/add-reading/{$args['consumer_id']}/")
            ->withStatus(302);
    });


$app->get("/list-consumers-of-management-company/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig){
        if (!checkUserRights($session, "Обычные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        return get_lists_of_consumers($database, $twig, $session, $response,
            "readings/consumers-of-management-company.twig");

});

$app->get("/consumers-list-of-management-company/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig){
        if (!checkUserRights($session, "Обычные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        return get_lists_of_consumers($database, $twig, $session, $response,
            "receipts/consumers-list-of-management-company.twig");
    });

$app->get("/add-common-receipt/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig){
        if (!checkUserRights($session, "Обычные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        return renderRequiredReceiptForm($response, $database, $twig, $session,
            "receipts/add-common-receipt.twig", $args["consumer_id"]);
    });

$app->get("/add-phone-receipt/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig){
        if (!checkUserRights($session, "Обычные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        return renderRequiredReceiptForm($response, $database, $twig, $session,
            "receipts/add-phone-receipt.twig", $args["consumer_id"]);
    });

$app->post("/add-common-receipt-post/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $add_common_receipt){
        fulfill_receipts_post_request($request, $session, $add_common_receipt, $args['consumer_id'], 0);
        return $response->withHeader("Location", "/add-common-receipt/{$args['consumer_id']}/")
            ->withStatus(302);
    });

$app->post("/add-phone-receipt-post/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $add_phone_receipt){
        fulfill_receipts_post_request($request, $session, $add_phone_receipt, $args['consumer_id'], 1);
        return $response->withHeader("Location", "/add-phone-receipt/{$args['consumer_id']}/")
            ->withStatus(302);
    });

$app->get("/show-not-paid-common-receipts/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать запрошенную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        return show_receipts($request, $response, $twig, $database, $session, $args["consumer_id"], 0, 0);
    });

$app->get("/show-paid-common-receipts/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать данную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        return show_receipts($request, $response, $twig, $database, $session, $args["consumer_id"], 0, 1);
    });

$app->get("/show-not-paid-phone-receipts/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать данную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        return show_receipts($request, $response, $twig, $database, $session, $args["consumer_id"], 1, 0);
    });

$app->get("/show-paid-phone-receipts/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать данную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (checkAvailableRecords($database, "Consumer",
                "Consumer_id", $args["consumer_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        return show_receipts($request, $response, $twig, $database, $session, $args["consumer_id"], 1, 1);
    });

$app->get("/pay-receipt/{table_name}/{receipt_id}/",
    function(ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig){
        if (!checkGuestRights($session, "Незарегистрированные пользователи не могут просматривать данную страницу!")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $availableTableName = ["ReceiptHCS", "ReceiptCityPhone", "ReceiptDistancePhone"];
        if (in_array($args["table_name"], $availableTableName) == false or checkAvailableRecords($database, $args["table_name"],
                "Receipt_id", $args["receipt_id"]) == false){
            return notfoundPageRedirection($session, $response);
        }
        return show_payment_page($request, $response, $twig, $database, $session, $args["receipt_id"], $args["table_name"]);
    });

$app->post("/pay-receipt-post/{table_name}/{receipt_id}/",
    function(ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $twig, $session, $receipt_payment){
        $required_template = [
            "ReceiptHCS" => ["Общая квитанция ЖКУ", 0],
            "ReceiptCityPhone" => ["Квитанция городской телефон", 1],
            "ReceiptDistancePhone" => ["Квитанция междугородний телефон", 1]
        ];
        $consumer_id = $database->getConnection()->query(
            "SELECT Consumer_id
                       FROM ". $args["table_name"]
                       ." WHERE Receipt_id = {$args['receipt_id']}"
        )->fetch();
        try {
            $receipt_payment->pay_for_receipt($args["table_name"], $args["receipt_id"]);
            $session->setData("message", "Квитанция: '" . $required_template[$args["table_name"]][0] . "' успешно оплачена!");
            $session->setData("status", "success");
            return show_receipts($request, $response, $twig, $database,
                $session, $consumer_id["Consumer_id"], $required_template[$args["table_name"]][1], 0);
        }
        catch (ReceiptPaymentException $exception){
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
        }
        return top_up_account($request, $response, $database, $session, $twig);
    });

$app->run();