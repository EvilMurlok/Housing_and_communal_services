<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionNews/ValidationNewsData.php";

class AdditionNews extends ValidationNewsData
{

    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }
    /**
     * @throws AdditionNewsException
     */
    public function add_news(array $data): bool
    {
        try{
            $this->validate_data($data);
        }
        catch (AdditionNewsException $exception){
            throw new AdditionNewsException($exception->getMessage());
        }
        $statement = $this->database->getConnection()->prepare(
            "INSERT INTO News (Title, Content, Is_published)
                            VALUES (:Title, :Content, :Is_published)"
        );
        $statement->execute([
            'Title' => $data['Title'],
            'Content' => $data['Content'],
            'Is_published' => $data['Is_published']
        ]);
        return true;
    }
}