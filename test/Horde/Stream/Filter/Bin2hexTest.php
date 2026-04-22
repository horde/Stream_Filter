<?php

declare(strict_types=1);

namespace Horde\Stream\Filter;

use Horde_Stream_Filter_Bin2hex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bin2hex::class)]
class Bin2hexTest extends TestCase
{
    private string $testdata;

    /** @var resource */
    private $fp;

    public function setUp(): void
    {
        $this->testdata = str_repeat("0123456789ABCDE", 1000);
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, $this->testdata);
    }

    public function tearDown(): void
    {
        fclose($this->fp);
    }

    public function testLegacyBin2hex(): void
    {
        @stream_filter_register('horde_bin2hex', Horde_Stream_Filter_Bin2hex::class);
        $filter = stream_filter_prepend($this->fp, 'horde_bin2hex', STREAM_FILTER_READ);
        rewind($this->fp);

        $this->assertEquals(bin2hex($this->testdata), stream_get_contents($this->fp));

        stream_filter_remove($filter);
    }

    public function testModernBin2hex(): void
    {
        Bin2hex::register();
        $filter = stream_filter_prepend($this->fp, Bin2hex::FILTER_NAME, STREAM_FILTER_READ);
        rewind($this->fp);

        $this->assertEquals(bin2hex($this->testdata), stream_get_contents($this->fp));

        stream_filter_remove($filter);
    }
}
