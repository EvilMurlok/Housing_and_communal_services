<?php

namespace App;

use JetBrains\PhpStorm\Pure;

require_once "/Users/macbookair/Desktop/hcsSite/src/addition/ValidationData.php";

class EditionNews extends ValidationData {

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
            $data = $this->validate_data($data, $required_news_id);
        }
        catch (AdditionNewsException $exception){
            throw new AdditionNewsException($exception->getMessage());
        }
        // поменять
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