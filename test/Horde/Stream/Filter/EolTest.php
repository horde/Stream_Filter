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
class EolTest extends TestCase
{
    public $fp;

    public function setup(): void
    {
        stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, "A\r\nB\rC\nD\r\n\r\nE\r\rF\n\nG\r\n\n\r\nH\r\n\r\r\nI");
    }

    public function tearDown(): void
    {
        fclose($this->fp);
    }

    public static function lineEndingProvider()
    {
        return array(
            array("\r", "A\rB\rC\rD\r\rE\r\rF\r\rG\r\r\rH\r\r\rI"),
            array("\n", "A\nB\nC\nD\n\nE\n\nF\n\nG\n\n\nH\n\n\nI"),
            array("\r\n", "A\r\nB\r\nC\r\nD\r\n\r\nE\r\n\r\nF\r\n\r\nG\r\n\r\n\r\nH\r\n\r\n\r\nI"),
            array("", "ABCDEFGHI"),
        );
    }

    /**
     * @dataProvider lineEndingProvider
     */
    public function testFilterLineEndings($eol, $expected)
    {
        $filter = stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, array('eol' => $eol));
        rewind($this->fp);
        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testBug12673()
    {
        $test = str_repeat(str_repeat("A", 1) . "\r\n", 4000);

        rewind($this->fp);
        fwrite($this->fp, $test);

        $filter = stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, array('eol' => "\r\n"));
        rewind($this->fp);

        $this->assertEquals($test, stream_get_contents($this->fp));

        $test = str_repeat(str_repeat("A", 14) . "\r\n", 2);

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, array('eol' => "\r\n"));
        rewind($this->fp);

        $this->assertEquals(
            $test,
            fread($this->fp, 14)
                . fread($this->fp, 1)
                . fread($this->fp, 1)
                . fread($this->fp, 14)
                . fread($this->fp, 2)
                . fread($this->fp, 100)
        );
    }

    public function testUnixStyleNewLineSubstitution()
    {
        $test = str_repeat("A\r\n", 4000);
        $expectedResult = str_repeat("A\n", 4000);

        rewind($this->fp);
        fwrite($this->fp, $test);

        $filter = stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expectedResult, stream_get_contents($this->fp));
    }

    /**
     * Test CRLF split at bucket boundary (byte 8191).
     *
     * This is the specific case that PR #2 fixes. The \r from \r\n falls
     * at the end of the first bucket (8192 bytes), causing incorrect
     * double newline conversion in buggy implementation.
     */
    public function testCrlfBucketBoundarySplit()
    {
        // 2730 * 3 bytes = 8190 bytes, then X\r\n crosses boundary
        $test = str_repeat("A\r\n", 2730) . "X\r\n" . "END";
        $expected = str_repeat("A\n", 2730) . "X\n" . "END";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    /**
     * Test trailing bare \r at end of stream.
     */
    public function testTrailingCarriageReturn()
    {
        $test = "Line1\r\nLine2\r";
        $expected = "Line1\nLine2\n";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    /**
     * Test conversion to CRLF (multi-character target EOL).
     *
     * Ensures original Bug #12673 fix still works.
     */
    public function testConversionToMultiCharEol()
    {
        $test = str_repeat("A\n", 4000);
        $expected = str_repeat("A\r\n", 4000);

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, ['eol' => "\r\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    /**
     * Test double CRLF sequences.
     */
    public function testDoubleCrlf()
    {
        $test = "A\r\n\r\nB";
        $expected = "A\n\nB";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    /**
     * Test CR-only input conversion.
     */
    public function testCarriageReturnOnly()
    {
        $test = "A\rB\rC";
        $expected = "A\nB\nC";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

}
