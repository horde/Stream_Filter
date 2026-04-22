<?php

declare(strict_types=1);

namespace Horde\Stream\Filter;

use Horde_Stream_Filter_Crc32;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Crc32::class)]
class Crc32Test extends TestCase
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

    public function testLegacyCrc32(): void
    {
        @stream_filter_register('horde_crc32', Horde_Stream_Filter_Crc32::class);
        $params = new stdClass();
        $filter = stream_filter_prepend($this->fp, 'horde_crc32', STREAM_FILTER_READ, $params);

        rewind($this->fp);
        while (fread($this->fp, 1024)) {
        }

        $this->assertTrue(property_exists($params, 'crc32'));
        $this->assertEquals(crc32($this->testdata), $params->crc32);

        stream_filter_remove($filter);
    }

    public function testModernCrc32(): void
    {
        Crc32::register();
        $params = new stdClass();
        $filter = stream_filter_prepend($this->fp, Crc32::FILTER_NAME, STREAM_FILTER_READ, $params);

        rewind($this->fp);
        while (fread($this->fp, 1024)) {
        }

        $this->assertTrue(property_exists($params, 'crc32'));
        $this->assertEquals(crc32($this->testdata), $params->crc32);

        stream_filter_remove($filter);
    }
}
