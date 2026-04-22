<?php

declare(strict_types=1);

namespace Horde\Stream\Filter;

use Horde_Stream_Filter_Null;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NullFilter::class)]
class NullFilterTest extends TestCase
{
    private string $testdata;

    /** @var resource */
    private $fp;

    public function setUp(): void
    {
        $this->testdata = "abcde\0fghij";
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, $this->testdata);
    }

    public function tearDown(): void
    {
        fclose($this->fp);
    }

    public function testLegacyNull(): void
    {
        @stream_filter_register('horde_null', Horde_Stream_Filter_Null::class);
        $filter = stream_filter_prepend($this->fp, 'horde_null', STREAM_FILTER_READ);
        rewind($this->fp);

        $this->assertEquals('abcdefghij', stream_get_contents($this->fp));

        stream_filter_remove($filter);
    }

    public function testModernNullFilter(): void
    {
        NullFilter::register();
        $filter = stream_filter_prepend($this->fp, NullFilter::FILTER_NAME, STREAM_FILTER_READ);
        rewind($this->fp);

        $this->assertEquals('abcdefghij', stream_get_contents($this->fp));

        stream_filter_remove($filter);
    }
}
