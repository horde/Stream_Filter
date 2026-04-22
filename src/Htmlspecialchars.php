<?php

declare(strict_types=1);

/**
 * Copyright 2012-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Jan Schneider <jan@horde.org>
 */

namespace Horde\Stream\Filter;

use php_user_filter;

class Htmlspecialchars extends php_user_filter
{
    public const FILTER_NAME = 'horde.stream.filter.htmlspecialchars';

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
            $bucket->data = htmlspecialchars($bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
