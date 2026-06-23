<?php

declare(strict_types=1);

namespace App\Support;

/** Lectura tipada de arreglos de datos validados (espejo de campoStr/campoInt). */
trait FieldCast
{
    /** @param array<string, mixed> $src */
    protected function str(array $src, string $key): ?string
    {
        $v = $src[$key] ?? null;
        if (is_string($v)) {
            return $v === '' ? null : $v;
        }

        return is_int($v) || is_float($v) ? (string) $v : null;
    }

    /** @param array<string, mixed> $src */
    protected function intOrNull(array $src, string $key): ?int
    {
        $v = $src[$key] ?? null;

        return is_numeric($v) ? (int) $v : null;
    }
}
