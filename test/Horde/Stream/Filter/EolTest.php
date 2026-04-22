<?php

declare(strict_types=1);

namespace Horde\Stream\Filter;

use Horde_Stream_Filter_Eol;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Eol::class)]
class EolTest extends TestCase
{
    /** @var resource */
    private $fp;

    public function setUp(): void
    {
        @stream_filter_register('horde_eol', Horde_Stream_Filter_Eol::class);
        Eol::register();
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, "A\r\nB\rC\nD\r\n\r\nE\r\rF\n\nG\r\n\n\r\nH\r\n\r\r\nI");
    }

    public function tearDown(): void
    {
        fclose($this->fp);
    }

    public static function lineEndingProvider(): array
    {
        return [
            'CR' => ["\r", "A\rB\rC\rD\r\rE\r\rF\r\rG\r\r\rH\r\r\rI"],
            'LF' => ["\n", "A\nB\nC\nD\n\nE\n\nF\n\nG\n\n\nH\n\n\nI"],
            'CRLF' => ["\r\n", "A\r\nB\r\nC\r\nD\r\n\r\nE\r\n\r\nF\r\n\r\nG\r\n\r\n\r\nH\r\n\r\n\r\nI"],
            'strip' => ["", "ABCDEFGHI"],
        ];
    }

    #[DataProvider('lineEndingProvider')]
    public function testLegacyFilterLineEndings(string $eol, string $expected): void
    {
        $filter = stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, ['eol' => $eol]);
        rewind($this->fp);
        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    #[DataProvider('lineEndingProvider')]
    public function testModernFilterLineEndings(string $eol, string $expected): void
    {
        $filter = stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => $eol]);
        rewind($this->fp);
        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testBug12673(): void
    {
        $test = str_repeat(str_repeat("A", 1) . "\r\n", 4000);

        rewind($this->fp);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\r\n"]);
        rewind($this->fp);

        $this->assertEquals($test, stream_get_contents($this->fp));

        $test = str_repeat(str_repeat("A", 14) . "\r\n", 2);

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\r\n"]);
        rewind($this->fp);

        $this->assertEquals(
            $test,
            fread($this->fp, 14)
                . fread($this->fp, 1)
                . fread($this->fp, 1)
                . fread($this->fp, 14)
                . fread($this->fp, 2)
                . fread($this->fp, 100),
        );
    }

    public function testUnixStyleNewLineSubstitution(): void
    {
        $test = str_repeat("A\r\n", 4000);
        $expected = str_repeat("A\n", 4000);

        rewind($this->fp);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testCrlfBucketBoundarySplit(): void
    {
        $test = str_repeat("A\r\n", 2730) . "X\r\n" . "END";
        $expected = str_repeat("A\n", 2730) . "X\n" . "END";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testTrailingCarriageReturn(): void
    {
        $test = "Line1\r\nLine2\r";
        $expected = "Line1\nLine2\n";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testConversionToMultiCharEol(): void
    {
        $test = str_repeat("A\n", 4000);
        $expected = str_repeat("A\r\n", 4000);

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\r\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testDoubleCrlf(): void
    {
        $test = "A\r\n\r\nB";
        $expected = "A\n\nB";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testCarriageReturnOnly(): void
    {
        $test = "A\rB\rC";
        $expected = "A\nB\nC";

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, Eol::FILTER_NAME, STREAM_FILTER_READ, ['eol' => "\n"]);
        rewind($this->fp);

        $this->assertEquals($expected, stream_get_contents($this->fp));
    }
}
