<?php

declare(strict_types=1);

/**
 * Copyright 2009-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 * @author Angelo Milazzo <bsod85@gmail.com>
 */

namespace Horde\Stream\Filter;

use php_user_filter;

class Eol extends php_user_filter
{
    public const FILTER_NAME = 'horde.stream.filter.eol';

    /** @var string[] */
    private array $search = [];

    private string|array $replace = '';

    private string $prependNext = '';

    public static function register(): void
    {
        if (in_array(self::FILTER_NAME, stream_get_filters())) {
            return;
        }
        stream_filter_register(self::FILTER_NAME, static::class);
    }

    public function onCreate(): bool
    {
        $eol = $this->params['eol'] ?? "\r\n";

        if (!strlen($eol)) {
            $this->search = ["\r", "\n"];
            $this->replace = '';
        } elseif (in_array($eol, ["\r", "\n"])) {
            $this->search = ["\r\n", ($eol === "\r") ? "\n" : "\r"];
            $this->replace = $eol;
        } else {
            $this->search = ["\r\n", "\r", "\n"];
            $this->replace = ["\n", "\n", $eol];
        }

        return true;
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = $this->prependNext . $bucket->data;
            $this->prependNext = '';

            if (
                !$closing
                && in_array("\r\n", $this->search)
                && ($bucket->data[$bucket->datalen - 1] === "\r")
            ) {
                $bucket->data = substr($bucket->data, 0, -1);
                $this->prependNext = "\r";
            }

            $bucket->data = str_replace($this->search, $this->replace, $bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
