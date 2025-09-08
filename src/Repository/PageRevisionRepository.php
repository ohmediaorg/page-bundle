<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\WysiwygBundle\Repository\WysiwygRepositoryInterface;

/**
 * @method PageRevision|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageRevision|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageRevision[]    findAll()
 * @method PageRevision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRevisionRepository extends ServiceEntityRepository implements WysiwygRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageRevision::class);
    }

    public function save(PageRevision $pageRevision, bool $flush = false): void
    {
        $this->getEntityManager()->persist($pageRevision);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PageRevision $pageRevision, bool $flush = false): void
    {
        $this->getEntityManager()->remove($pageRevision);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getShortcodeQueryBuilder(string $shortcode): QueryBuilder
    {
        $qb = $this->createQueryBuilder('pr');

        $ors = [];

        $ors[] = '(
            SELECT COUNT(pct)
            FROM OHMedia\PageBundle\Entity\PageContentText pct
            WHERE IDENTITY(pct.pageRevision) = pr.id
            AND pct.text LIKE :wysiwyg_like
        ) > 0';

        $ors[] = '(
            SELECT COUNT(pcr)
            FROM OHMedia\PageBundle\Entity\PageContentRow pcr
            WHERE IDENTITY(pcr.pageRevision) = pr.id
            AND (
                pcr.column_1 LIKE :wysiwyg_like OR
                pcr.column_2 LIKE :wysiwyg_like OR
                pcr.column_3 LIKE :wysiwyg_like
            )
        ) > 0';

        $ors = implode(' OR ', $ors);

        return $qb
            ->where('('.$ors.')')
            ->setParameters(new ArrayCollection([
                new Parameter('wysiwyg_like', '%'.$shortcode.'%'),
            ]))
        ;
    }

    public function getShortcodeRoute(): string
    {
        return 'page_view';
    }

    public function getShortcodeRouteParams(mixed $entity): array
    {
        return [
            'id' => $entity->getPage()->getId(),
            'revision' => $entity->getId(),
        ];
    }

    public function getShortcodeHeading(): string
    {
        return 'Pages';
    }

    public function getShortcodeLinkText(mixed $entity): string
    {
        return sprintf(
            '%s - Page Revision (ID:%s)',
            (string) $entity->getPage(),
            $entity->getId(),
        );
    }
}
