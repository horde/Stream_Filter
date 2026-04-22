<?php

declare(strict_types=1);

/**
 * Copyright 2011-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

namespace Horde\Stream\Filter;

use php_user_filter;

class Crc32 extends php_user_filter
{
    public const FILTER_NAME = 'horde.stream.filter.crc32';

    public static function register(): void
    {
        if (in_array(self::FILTER_NAME, stream_get_filters())) {
            return;
        }
        stream_filter_register(self::FILTER_NAME, static::class);
    }

    public function onCreate(): bool
    {
        $this->params->crc32 = 0;

        return true;
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            $this->params->crc32 = $this->crc32Combine(
                $this->params->crc32,
                crc32($bucket->data),
                $bucket->datalen,
            );
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    private function crc32Combine(int $crc1, int $crc2, int $len2): int
    {
        $odd = [0xedb88320];
        $row = 1;

        for ($n = 1; $n < 32; ++$n) {
            $odd[$n] = $row;
            $row <<= 1;
        }

        $this->gf2MatrixSquare($even, $odd);
        $this->gf2MatrixSquare($odd, $even);

        do {
            $this->gf2MatrixSquare($even, $odd);

            if ($len2 & 1) {
                $crc1 = $this->gf2MatrixTimes($even, $crc1);
            }

            $len2 >>= 1;

            if ($len2 === 0) {
                break;
            }

            $this->gf2MatrixSquare($odd, $even);
            if ($len2 & 1) {
                $crc1 = $this->gf2MatrixTimes($odd, $crc1);
            }

            $len2 >>= 1;
        } while ($len2 !== 0);

        $crc1 ^= $crc2;

        return $crc1;
    }

    private function gf2MatrixSquare(?array &$square, array &$mat): void
    {
        $square = [];
        for ($n = 0; $n < 32; ++$n) {
            $square[$n] = $this->gf2MatrixTimes($mat, $mat[$n]);
        }
    }

    private function gf2MatrixTimes(array $mat, int $vec): int
    {
        $i = $sum = 0;

        while ($vec) {
            if ($vec & 1) {
                $sum ^= $mat[$i];
            }

            $vec = ($vec >> 1) & 0x7FFFFFFF;
            ++$i;
        }

        return $sum;
    }
}
