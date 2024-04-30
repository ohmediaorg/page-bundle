<?php

namespace OHMedia\PageBundle\Cleanup;

use OHMedia\CleanupBundle\Interfaces\CleanerInterface;
use OHMedia\PageBundle\Repository\Page301Repository;
use Symfony\Component\Console\Output\OutputInterface;

class Page301Cleaner implements CleanerInterface
{
    public function __construct(private Page301Repository $page301Repository)
    {
    }

    public function __invoke(OutputInterface $output): void
    {
        $lastYear = new \DateTime('-1 year');

        $this->page301Repository
            ->createQueryBuilder('p')
            ->delete()
            ->where('p.created_at < :lastYear')
            ->setParameter('lastYear', $lastYear)
            ->getQuery()
            ->execute();
    }
}
