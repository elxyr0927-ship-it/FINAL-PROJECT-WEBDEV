<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\Request;

final class QueryParam
{
    private const MAX_LENGTH = 255;

    public static function string(Request $request, string $key, string $default = ''): string
    {
        $value = $request->query->all()[$key] ?? null;

        if (!is_scalar($value)) {
            return $default;
        }

        return mb_substr(trim((string) $value), 0, self::MAX_LENGTH);
    }

    public static function int(Request $request, string $key): ?int
    {
        $value = $request->query->all()[$key] ?? null;

        if (!is_scalar($value)) {
            return null;
        }

        $int = filter_var(trim((string) $value), \FILTER_VALIDATE_INT);

        return false === $int ? null : $int;
    }
}
