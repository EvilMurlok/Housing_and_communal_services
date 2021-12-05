<?php
header("Content-Type: text/html; charset=utf-8");
error_reporting(-1);
require __DIR__ . "/vendor/autoload.php";

use App\AdditionNews;
use App\AdditionNewsException;
use App\AuthorizationConsumer;
use App\AuthorizationConsumerException;
use App\AuthorizationEntity;
use App\AuthorizationEntityException;
use App\Database;
use App\EditionNews;
use App\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Slim\Factory\AppFactory;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Database.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Session.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Authorization/AuthorizationConsumer.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Authorization/AuthorizationConsumerException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Authorization/AuthorizationEntity.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Authorization/AuthorizationEntityException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/addition/AdditionNews.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/addition/AdditionNewsException.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/addition/EditionNews.php";

$loader = new FilesystemLoader("templates");
$twig = new Environment($loader);

$session = new Session();
// будет отрабатываться для каждого запроса get или post, определенного снизу
// и где бы мы ни захотели воспользоваться сессией, она уже будет создана
$sessionMiddleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($session) {
    $session->start();
    $session_timeout = 120; // in seconds
    if (!isset($_SESSION['last_visit'])) {
        $_SESSION['last_visit'] = time();
    }
    if(isset($_SESSION['user']) && (time() - $_SESSION['last_visit']) > $session_timeout) {
        unset($_SESSION['last_visit']);
        unset($_SESSION);
        header("Location: /logout/");
        exit;
    }
    $_SESSION['last_visit'] = time();
    $response = $handler->handle($request);
    $session->save();
//    var_dump($_SESSION);
    return $response;
};

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware(); // $_POST
$app->add($sessionMiddleware);


$config = include_once "/Users/macbookair/Desktop/Housing_and_communal_services/config/databaseInfo.php";
$dsn = $config["dsn"];
$username = $config["username"];
$password = $config["password"];
$database = new Database($dsn, $username, $password);

// authorization block
$authorization_entity = new AuthorizationEntity($database, $session);
$authorization_consumer = new AuthorizationConsumer($database, $session);

// addition edition block
$add_news = new AdditionNews($database, $session);
$edit_news = new EditionNews($database, $session);

// такие callback-функции должны возвращать строго $response!
$app->get("/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session, $database) {

        $query = $database->getConnection()->query(
            "SELECT News_id, Title, Content, Is_published, Created_at FROM News 
                   WHERE Is_published = 1
                   ORDER BY Created_at DESC LIMIT 8"
        );
        $news = $query->fetchAll();
        $session->setData("form_news", $news);
        $body = $twig->render("index.twig", [
            "user" => $session->getData("user"),
            "message" => $session->get_and_set_null("message"),
            "status" => $session->flush("status"),
            "form_news" => $session->flush("form_news")
        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->get("/view-news/{news_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        $query = $database->getConnection()->query(
            "SELECT News_id, Title, Content, Is_published, Created_at FROM News
                   WHERE News_id = {$args['news_id']}"
        );
        $news = $query->fetch();
        $session->setData("form_news", $news);
        $body = $twig->render("info/view-news.twig", [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            "status" => $session->flush("status"),
            "form_news" => $session->getData("form_news")
        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->get("/edit-news/{news_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $session, $twig) {
        $session->setData("news_id", $args['news_id']);
        $body = $twig->render("addition/edit-news.twig", [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            "form_news" => $session->flush("form_news"),
            "status" => $session->flush("status"),
            "news_id" => $session->flush("news_id")
        ]);
        $response->getBody()->write($body);
        return $response;
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

        $body = $twig->render("addition/add-news.twig", [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            "status" => $session->flush("status"),
            "form_news" => $session->flush("form_news")
        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->post("/add-news-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($add_news, $session) {

        if ($session->getData("user")["Is_staff"] != 1) {
            $session->setData("message", "Неавторизованные пользователи не могут добавлять новости!");
            $session->setData("status", "danger");
            return $response->withHeader("Location", "/")
                ->withStatus(302);
        }
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
        return $response->withHeader("Location", "/add-news/")
            ->withStatus(302);
    });

$app->get("/login-consumer/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {

        $body = $twig->render("authorization/login-consumer.twig", [
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "status" => $session->flush("status")

        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->post("/login-consumer-post/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($authorization_consumer, $session) {

        $params = (array)$request->getParsedBody();
        try {
            $authorization_consumer->login($params['Consumer_phone_email'], $params['Consumer_password']);
        } catch (AuthorizationConsumerException $exception) {
            $session->setData("message", $exception->getMessage());
            $session->setData("status", "danger");
            $session->setData("form", $params);
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

        $body = $twig->render("authorization/login-entity.twig", [
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "status" => $session->flush("status")

        ]);
        $response->getBody()->write($body);
        return $response;
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

        $body = $twig->render("authorization/register-entity.twig", [
            "message" => $session->flush("message"),
            "form" => $session->flush("form"),
            "status" => $session->flush("status")
        ]);
        $response->getBody()->write($body);
        return $response;
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
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session, $twig) {

        $session->setData('user', null);

        if (http_response_code() == 302){
            $body = $twig->render("index.twig", [
                "message" => "Ваша сессия истекла!",
                "status" => "dander"
            ]);
            $response->getBody()->write($body);
            return $response;
        }
        return $response->withHeader("Location", "/")
            ->withStatus(302);
    });

$app->get("/contacts/",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {

        $body = $twig->render("info/contacts.twig", [
            "user" => $session->getData("user")
        ]);
        $response->getBody()->write($body);
        return $response;
    });

// тут запросы к нескольким таблицам!
$app->get("/view-consumer/{consumer_id}/",
    function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($database, $twig, $session){
        $query = $database->getConnection()->query(
            "SELECT A.City_name, A.Street, A.House, A.Housing, MC.Company_name, MC.Address, MC.Full_name_boss,
                              MC.Company_email, MC.Telephone_number
                       FROM Consumer AS C 
                       INNER JOIN Address A ON C.Address_id = A.Address_id 
                       INNER JOIN ManagementCompany MC on A.Management_company_id = MC.Management_company_id
                       WHERE C.Consumer_id = {$args["consumer_id"]}"
        );
        $consumer_info = $query->fetch();
        $body = $twig->render("info/user-info.twig", [
            "user" => $session->getData("user"),
            "consumer_info" => $consumer_info,

        ]);
        $response->getBody()->write($body);
        return $response;
    });

$app->run();