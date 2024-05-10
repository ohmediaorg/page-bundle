<?php

namespace OHMedia\PageBundle\Cleanup;

use OHMedia\CleanupBundle\Interfaces\CleanerInterface;
use OHMedia\PageBundle\Repository\PageRevisionRepository;
use Symfony\Component\Console\Output\OutputInterface;

class PageRevisionCleaner implements CleanerInterface
{
    public const KEEP_PUBLISHED = 5;

    public function __construct(private PageRevisionRepository $pageRevisionRepository)
    {
    }

    public function __invoke(OutputInterface $output): void
    {
        $this->cleanupDraft();

        $this->cleanupPublished();
    }

    private function cleanupDraft()
    {
        $threeMonthsAgo = new \DateTime('-3 months');

        $this->pageRevisionRepository->createQueryBuilder('pr')
            ->delete()
            ->where('pr.updated_at < :three_months')
            ->andWhere('pr.published = 0')
            ->setParameter('three_months', $threeMonthsAgo)
            ->getQuery()
            ->execute();
    }

    private function cleanupPublished()
    {
        // get a count of published page revisions by page
        $counts = $this->pageRevisionRepository->createQueryBuilder('pr')
            ->select('COUNT(pr.id) AS count')
            ->addSelect('IDENTITY(pr.page) AS page_id')
            ->where('pr.published = 1')
            ->groupBy('pr.page')
            ->getQuery()
            ->getArrayResult();

        foreach ($counts as $count) {
            $delete = (int) $count['count'] - self::KEEP_PUBLISHED;

            if ($delete <= 0) {
                continue;
            }

            $pageRevisions = $this->pageRevisionRepository->createQueryBuilder('pr')
                ->where('IDENTITY(pr.page) = :page_id')
                ->setParameter('page_id', $count['page_id'])
                ->andWhere('pr.published = 1')
                ->orderBy('pr.updated_at', 'ASC')
                ->setMaxResults($delete)
                ->getQuery()
                ->getResult();

            foreach ($pageRevisions as $pageRevision) {
                $this->pageRevisionRepository->remove($pageRevision, true);
            }
        }
    }
}
