<?php

/**
 * Copyright 2009-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2009-2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Stream_Filter
 */

/**
 * Stream filter class to convert EOL characters.
 *
 * Handles conversion between different line ending formats (CRLF, LF, CR).
 * Correctly processes multi-character EOL sequences that span stream bucket
 * boundaries (~8192 bytes).
 *
 * Usage:
 *   stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
 *   stream_filter_[app|pre]pend($stream, 'horde_eol',
 *                               [ STREAM_FILTER_[READ|WRITE|ALL] ],
 *                               [ $params ]);
 *
 * $params is an array that can contain the following:
 *   - eol: (string) The EOL string to use.
 *          DEFAULT: <CR><LF> ("\r\n")
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Angelo Milazzo <bsod85@gmail.com>
 * @category  Horde
 * @copyright 2009-2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Stream_Filter
 */
class Horde_Stream_Filter_Eol extends php_user_filter
{
    /**
     * EOL patterns to search for in input stream.
     *
     * @var array
     */
    protected $_search;

    /**
     * Replacement EOL pattern(s) corresponding to $_search.
     *
     * @var string|array
     */
    protected $_replace;

    /**
     * Partial CRLF sequence saved from previous bucket.
     *
     * When a bucket ends with \r and we're searching for \r\n, this stores
     * the \r to prepend to the next bucket for complete processing.
     *
     * @var string
     */
    private $_prependNext = '';

    /**
     * Initialize the filter.
     *
     * Sets up search and replace patterns based on desired EOL format.
     *
     * @return bool  True on success.
     *
     * @see stream_filter_register()
     */
    #[ReturnTypeWillChange]
    public function onCreate()
    {
        $eol = $this->params['eol'] ?? "\r\n";

        if (!strlen($eol)) {
            // Strip all line endings
            $this->_search = ["\r", "\n"];
            $this->_replace = '';
        } elseif (in_array($eol, ["\r", "\n"])) {
            // Convert to single-character EOL (LF or CR)
            $this->_search = ["\r\n", ($eol == "\r") ? "\n" : "\r"];
            $this->_replace = $eol;
        } else {
            // Convert to multi-character EOL (e.g., CRLF)
            $this->_search = ["\r\n", "\r", "\n"];
            $this->_replace = ["\n", "\n", $eol];
        }

        return true;
    }

    /**
     * Filter stream data.
     *
     * Handles bucket-boundary CRLF splits by:
     * 1. Prepending any saved \r from previous bucket
     * 2. Saving trailing \r if it might be part of \r\n split across boundary
     * 3. Processing complete EOL sequences with str_replace
     *
     * @param resource $in       Input bucket brigade.
     * @param resource $out      Output bucket brigade.
     * @param int      $consumed Bytes consumed (modified by reference).
     * @param bool     $closing  True if stream is closing (last bucket).
     *
     * @return int  PSFS_PASS_ON to continue processing.
     *
     * @see stream_filter_register()
     */
    #[ReturnTypeWillChange]
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            // Restore any \r saved from previous bucket
            $bucket->data = $this->_prependNext . $bucket->data;
            $this->_prependNext = '';

            // If bucket ends with \r and we're searching for \r\n,
            // save the \r for next bucket (might be \r|\n split)
            if (
                !$closing
                && in_array("\r\n", $this->_search)
                && ($bucket->data[$bucket->datalen - 1] == "\r")
            ) {
                $bucket->data = substr($bucket->data, 0, -1);
                $this->_prependNext = "\r";
            }

            $bucket->data = str_replace($this->_search, $this->_replace, $bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

}
