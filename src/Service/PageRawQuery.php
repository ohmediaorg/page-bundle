<?php

namespace OHMedia\PageBundle\Service;

use Doctrine\DBAL\Connection;
use OHMedia\PageBundle\Entity\PageContentRow;
use OHMedia\PageBundle\Entity\PageContentText;
use OHMedia\WysiwygBundle\Util\Shortcode;

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

        $stmt = $this->connection->prepare($sql);

        $fields['id'] = $id;

        $stmt->execute($fields);
    }

    public function getPathWithShortcode(string $shortcode): ?string
    {
        // shortcodes can only be in page_content_text with type 'wysiwyg'
        $pctCount = '
            SELECT COUNT(pct.id)
            FROM `page_content_text` pct
            WHERE pct.page_revision_id = pr.id
            AND pct.type = :pct_type_wysiwyg
            AND pct.text LIKE :shortcode
        ';

        // a column's content is not output if the layout does not call for it
        $pcrOneColumnOr = 'pcr.layout = :pcr_one_column AND pcr.column_1 LIKE :shortcode';
        $pcrTwoColumnsOr = 'pcr.layout NOT IN (:pcr_two_column) AND (pcr.column_1 LIKE :shortcode OR pcr.column_2 LIKE :shortcode)';
        $pcrThreeColumnsOr = 'pcr.layout = :pcr_three_column AND (pcr.column_1 LIKE :shortcode OR pcr.column_2 LIKE :shortcode OR pcr.column_3 LIKE :shortcode)';

        $pcrCount = "
            SELECT COUNT(pcr.id)
            FROM `page_content_row` pcr
            WHERE pcr.page_revision_id = pr.id
            AND (($pcrOneColumnOr) OR ($pcrTwoColumnsOr) OR ($pcrThreeColumnsOr))
        ";

        $sql = "
            SELECT p.path
            FROM `page_revision` pr
            JOIN `page` p on p.id = pr.page_id
            WHERE pr.published = 1
            AND p.published IS NOT NULL
            AND p.published < UTC_TIMESTAMP()
            AND (($pctCount) > 0 OR ($pcrCount) > 0)
            ORDER BY pr.updated_at DESC
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);

        $results = $stmt->execute([
            'pct_type_wysiwyg' => PageContentText::TYPE_WYSIWYG,
            'pcr_one_column' => PageContentRow::LAYOUT_ONE_COLUMN,
            'pcr_two_column' => implode(', ', [
                PageContentRow::LAYOUT_TWO_COLUMN,
                PageContentRow::LAYOUT_SIDEBAR_LEFT,
                PageContentRow::LAYOUT_SIDEBAR_RIGHT,
            ]),
            'pcr_three_column' => PageContentRow::LAYOUT_THREE_COLUMN,
            'shortcode' => '%'.Shortcode::format($shortcode).'%',
        ]);

        $result = $results->fetch();

        return $result['path'] ?? null;
    }
}
