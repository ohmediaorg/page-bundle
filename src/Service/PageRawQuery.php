<?php

namespace OHMedia\PageBundle\Service;

use Doctrine\DBAL\Connection;

class PageRawQuery
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    public function commit()
    {
        $this->connection->commit();
    }

    public function rollback()
    {
        $this->connection->rollback();
    }

    public function update(int $id, array $fields)
    {
        // ID shouldn't exist in fields
        unset($fields['id']);

        // Page::setHomepage() has too much logic to mimic
        // but it shouldn't be getting set this way
        unset($fields['homepage']);

        $set = [];

        foreach ($fields as $field => $value) {
            $set[] = "`$field` = :$field";
        }

        // making sure to mimic Page::setParentSlug() logic
        // which is called by Page::setSlug() and Page::setParent()

        $parentIdExists = array_key_exists('parent_id', $fields);
        $slugExists = array_key_exists('slug', $fields);

        if ($parentIdExists) {
            $fields['parent_slug_id'] = $fields['parent_id'] ?? 0;

            if ($slugExists) {
                $set[] = "`parent_slug` = CONCAT(:parent_slug_id, ':', :slug)";
            } else {
                $set[] = "`parent_slug` = CONCAT(:parent_slug_id, ':', `slug`)";
            }
        } elseif ($slugExists) {
            $set[] = "`parent_slug` = CONCAT(COALESCE(`parent_id`, 0), ':', :slug)";
        }

        $set = implode(', ', $set);

        $sql = "UPDATE `page` SET $set WHERE `id` = :id";

        $stmt = $this->connection->prepare($sql);

        $fields['id'] = $id;

        $stmt->execute($fields);
    }
}
