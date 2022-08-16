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
class NullTest extends TestCase
{
    public $fp;
    public $testdata;

    public function setup(): void
    {
        stream_filter_register('horde_null', 'Horde_Stream_Filter_Null');

        $this->testdata = "abcde\0fghij";
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, $this->testdata);
    }

    public function testNull()
    {
        $filter = stream_filter_prepend($this->fp, 'horde_null', STREAM_FILTER_READ);
        rewind($this->fp);
        $this->assertEquals(
            'abcdefghij',
            stream_get_contents($this->fp)
        );
        stream_filter_remove($filter);
    }
}
