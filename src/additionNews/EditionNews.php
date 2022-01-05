<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/additionNews/ValidationNewsData.php";

class EditionNews extends ValidationNewsData {

    #[Pure] public function __construct(Database $database, $session)
    {
        parent::__construct($database, $session);
    }

    /**
     * @throws AdditionNewsException
     */
    public function edit_news(array $data, $required_news_id): bool
    {
        try{
            $this->validate_data($data, $required_news_id);
        }
        catch (AdditionNewsException $exception){
            throw new AdditionNewsException($exception->getMessage());
        }
        $statement = $this->database->getConnection()->prepare(
            "UPDATE News SET Title = :Title, Content = :Content, Is_published = :Is_published
                    WHERE News_id = :required_news_id"
        );
        $statement->execute([
            'Title' => $data['Title'],
            'Content' => $data['Content'],
            'Is_published' => $data['Is_published'],
            'required_news_id' => $required_news_id

        ]);

        return true;
    }
}