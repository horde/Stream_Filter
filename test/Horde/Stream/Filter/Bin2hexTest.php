<?php
/**
 * @category   Horde
 * @package    Stream_Filter
 * @subpackage UnitTests
 */
namespace Horde\Stream\Filter;
use Horde_Test_Case as TestCase;

/**
 * @category   Horde
 * @package    Stream_Filter
 * @subpackage UnitTests
 */
class Bin2hexTest extends TestCase
{
    public $fp;
    public $testdata;

    public function setup(): void
    {
        stream_filter_register('horde_bin2hex', 'Horde_Stream_Filter_Bin2hex');

        $this->testdata = str_repeat("0123456789ABCDE", 1000);

        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, $this->testdata);
    }

    public function testCrc32()
    {
        $filter = stream_filter_prepend($this->fp, 'horde_bin2hex', STREAM_FILTER_READ);

        rewind($this->fp);

        $this->assertEquals(
            bin2hex($this->testdata),
            stream_get_contents($this->fp)
        );

        stream_filter_remove($filter);
    }
}
