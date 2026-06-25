<?php

namespace OHMedia\PageBundle\Cleanup;

use OHMedia\CleanupBundle\Interfaces\CleanerInterface;
use OHMedia\PageBundle\Repository\RedirectRepository;
use Symfony\Component\Console\Output\OutputInterface;

class RedirectCleaner implements CleanerInterface
{
    public function __construct(private RedirectRepository $redirectRepository)
    {
    }

    public function __invoke(OutputInterface $output): void
    {
        $lastYear = new \DateTime('-1 year');

        $this->redirectRepository
            ->createQueryBuilder('r')
            ->delete()
            ->where('r.created_at < :lastYear')
            ->setParameter('lastYear', $lastYear)
            ->getQuery()
            ->execute();
    }
}
