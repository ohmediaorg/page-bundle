<?php

namespace OHMedia\PageBundle\Util;

use function Symfony\Component\String\u;

class ReadableUserType
{
    public static function get(string $type): ?string
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
