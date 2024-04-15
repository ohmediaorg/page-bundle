<?php

namespace OHMedia\PageBundle\Cleanup;

use OHMedia\CleanupBundle\Interfaces\CleanerInterface;
use OHMedia\PageBundle\Repository\PageRevisionRepository;
use Symfony\Component\Console\Output\OutputInterface;

class PageRevisionCleaner implements CleanerInterface
{
    public const KEEP_PUBLISHED = 5;

    private $pageRevisionRepository;

    public function __construct(PageRevisionRepository $pageRevisionRepository)
    {
        $this->pageRevisionRepository = $pageRevisionRepository;
    }

    public function __invoke(OutputInterface $output): void
    {
        $this->cleanupDraft();

        $this->cleanupPublished();
    }

    private function cleanupDraft()
    {
        $threeMonthsAgo = new \DateTime('-3 months');

        $this->pageRevisionRepository->createQueryBuilder('pv')
            ->delete()
            ->where('pv.updated_at < :three_months')
            ->andWhere('pv.published = 0')
            ->setParameter('three_months', $threeMonthsAgo)
            ->getQuery()
            ->execute();
    }

    private function cleanupPublished()
    {
        // get a count of published page revisions by page
        $counts = $this->pageRevisionRepository->createQueryBuilder('pv')
            ->select('COUNT(pv.id) AS count')
            ->addSelect('IDENTITY(pv.page) AS page_id')
            ->where('pv.published = 1')
            ->groupBy('pv.page')
            ->getQuery()
            ->getArrayResult();

        foreach ($counts as $count) {
            $delete = (int) $count['count'] - self::KEEP_PUBLISHED;

            if ($delete <= 0) {
                continue;
            }

            $pageRevisions = $this->pageRevisionRepository->createQueryBuilder('pv')
                ->where('IDENTITY(pv.page) = :page_id')
                ->setParameter('page_id', $count['page_id'])
                ->andWhere('pv.published = 1')
                ->orderBy('pv.updated_at', 'ASC')
                ->setMaxResults($delete)
                ->getQuery()
                ->getResult();

            foreach ($pageRevisions as $pageRevision) {
                $this->pageRevisionRepository->remove($pageRevision, true);
            }
        }
    }
}
