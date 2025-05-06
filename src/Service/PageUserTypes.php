<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\SecurityBundle\Entity\User;
use OHMedia\SecurityBundle\Repository\UserRepository;

use function Symfony\Component\String\u;

class PageUserTypes
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function getUserTypes(): array
    {
        $result = $this->userRepository->createQueryBuilder('u')
            ->select('u.type')
            ->where('u.type NOT IN (:admin_types)')
            ->setParameter('admin_types', [
                User::TYPE_DEVELOPER,
                User::TYPE_SUPER,
                User::TYPE_ADMIN,
            ])
            ->orderBy('u.type', 'ASC')
            ->groupBy('u.type')
            ->getQuery()
            ->getResult();

        return array_map(function ($r) {
            return $r['type'];
        }, $result);
    }

    public static function readable(string $type): ?string
    {
        try {
            $reflection = new \ReflectionClass($type);

            $text = u($reflection->getShortName())
                ->snake()
                ->replace('_', ' ')
                ->title(true);

            return (string) $text;
        } catch (\Exception $e) {
            return null;
        }
    }
}
