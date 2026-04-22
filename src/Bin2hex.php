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

class Bin2hex extends php_user_filter
{
    public const FILTER_NAME = 'horde.stream.filter.bin2hex';

    public static function register(): void
    {
        if (in_array(self::FILTER_NAME, stream_get_filters())) {
            return;
        }
        stream_filter_register(self::FILTER_NAME, static::class);
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = bin2hex($bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
