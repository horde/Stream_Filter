<?php
/**
 * @category   Horde
 * @package    Stream_Filter
 * @subpackage UnitTests
 */
namespace Horde\Stream\Filter;
use Horde_Test_Case as TestCase;
use \stdClass;

/**
 * @category   Horde
 * @package    Stream_Filter
 * @subpackage UnitTests
 */
class Crc32Test extends TestCase
{
    public $fp;
    public $testdata;

    public function setup(): void
    {
        stream_filter_register('horde_crc32', 'Horde_Stream_Filter_Crc32');

        $this->testdata = str_repeat("0123456789ABCDE", 1000);

        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, $this->testdata);
    }

    public function testCrc32()
    {
        $params = new stdClass;
        $filter = stream_filter_prepend($this->fp, 'horde_crc32', STREAM_FILTER_READ, $params);

        rewind($this->fp);
        while (fread($this->fp, 1024)) {}

        $this->assertObjectHasAttribute('crc32', $params);

        $this->assertEquals(
            crc32($this->testdata),
            $params->crc32
        );

        stream_filter_remove($filter);
    }
}
