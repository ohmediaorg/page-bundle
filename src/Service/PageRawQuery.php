<?php

namespace OHMedia\PageBundle\Service;

use Doctrine\DBAL\Connection;

class PageRawQuery
{
    public function __construct(private Connection $connection)
    {
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

        $fields['id'] = $id;

        $this->connection->executeQuery($sql, $fields);
    }

    public function getPathWithTemplate(string $template): ?string
    {
        $sql = '
            SELECT p.path
            FROM `page_revision` pr
            JOIN `page` p on p.id = pr.page_id
            WHERE p.published IS NOT NULL
            AND p.published < UTC_TIMESTAMP()
            AND p.dynamic = 1
            AND (p.homepage IS NULL OR p.homepage = 0)
            AND (
                SELECT pr_sub.id
                FROM `page_revision` pr_sub
                WHERE pr_sub.page_id = p.id
                AND pr_sub.published = 1
                ORDER BY pr_sub.updated_at DESC
                LIMIT 1
            ) = pr.id
            AND pr.template = :pr_template';

        $results = $this->connection->executeQuery($sql, [
            'pr_template' => $template,
        ]);

        $result = $results->fetchAssociative();

        return $result['path'] ?? null;
    }
}
