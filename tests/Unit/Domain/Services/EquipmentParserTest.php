<?php

namespace Tests\Unit\Domain\Services;

use App\Application\Exceptions\IncompleteFileException;
use App\Application\Exceptions\InvalidFileException;
use App\Domain\Services\EquipmentParser;
use App\Domain\Services\EquipmentParserValidator;
use Illuminate\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(EquipmentParser::class)]
class EquipmentParserTest extends TestCase
{
    private readonly EquipmentParserValidator $validator;
    private readonly EquipmentParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new EquipmentParser(
            $this->validator = Mockery::mock(EquipmentParserValidator::class),
        );
    }

    #[Group('happy_test')]
    public function testParseFile(): void
    {
        $this->validator->shouldReceive('validateHeader')->once();
        $this->validator->shouldReceive('isHeaderOrFooter')->andReturn(false);
        $this->validator->shouldReceive('validateLineLength')->once();
        $this->validator->shouldReceive('validateMinimumRecord')->once();

        $content = implode(PHP_EOL, [
            $this->buildFixedWidthLine([
                [0, 19, 'RANDOM-MAT-A-FET'],
                [19, 41, 'Alpha text'],
                [60, 33, '10X20X30'],
                [89, 19, '123456789'],
                [108, 14, 'SYS USR'],
                [122, 11, 'LOC-A'],
                [133, 9, 'ROOM-A'],
                [142, 5, 'SL-A'],
                [147, 19, 'SUP-A'],
                [166, 31, 'MSN-A'],
                [197, 53, 'SER-A'],
            ]),
            $this->buildFixedWidthLine([
                [0, 41, 'IH09 Alpha'],
                [41, 38, 'DIM ALT A'],
                [79, 17, '1.250,50 KG'],
                [166, 28, 'PLANTA COSTA'],
                [194, 10, '01.01.2020'],
                [205, 10, '31.12.9999'],
                [224, 9, 'WC-A'],
            ]),
            $this->buildFixedWidthLine([
                [31, 19, 'OLD-A'],
                [58, 11, '02.01.2020'],
                [69, 13, 'CREATORA'],
                [82, 11, '03.01.2020'],
                [92, 13, 'CHANGERA'],
                [105, 41, 'Short A'],
                [146, 103, 'Long A'],
            ]),
        ]).PHP_EOL;

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);

        $items = $this->parser->parseFile('fake-equipment-file.txt');

        $this->assertCount(1, $items);
        $this->assertSame('123456789', $items[0]->getAttribute('Equipment'));
        $this->assertSame('RANDOM-MAT-A-FET', $items[0]->getAttribute('Material'));
        $this->assertSame('RANDOM-MAT-A', $items[0]->getAttribute('MaterialWithoutFet'));
    }

    #[Group('sad_test')]
    public function testParseFileWhenValidatorThrowsInvalidException(): void
    {
        $this->validator->shouldReceive('validateHeader');
        $this->validator->shouldReceive('isHeaderOrFooter')->andReturn(false);
        $this->validator->shouldReceive('validateLineLength')
            ->andThrow(new InvalidFileException('Corrupt export'));

        $content = implode(PHP_EOL, [
            $this->buildFixedWidthLine([[89, 19, '123456789']]),
            $this->buildFixedWidthLine([]),
            $this->buildFixedWidthLine([]),
        ]).PHP_EOL;

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Corrupt export');

        $this->parser->parseFile('fake-equipment-file.txt');
    }

    #[Group('sad_test')]
    public function testParseFilehWhenEquipmentValueMissing(): void
    {
        $this->validator->shouldReceive('validateHeader');
        $this->validator->shouldReceive('isHeaderOrFooter')->andReturn(false);
        $this->validator->shouldReceive('validateLineLength');
        $this->validator->shouldReceive('validateMinimumRecord');

        $content = implode(PHP_EOL, [
            $this->buildFixedWidthLine([
                [0, 19, 'RANDOM-MAT-B-FET'],
                [89, 19, 'NO-ID-HERE'],
            ]),
            $this->buildFixedWidthLine([]),
            $this->buildFixedWidthLine([]),
        ]) . PHP_EOL;

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Missing equipment value');

        $this->parser->parseFile('fake-equipment-file.txt');
    }

    #[Group('sad_test')]
    public function testParseFileWhereMinimumRecordFalse(): void
    {
        $this->validator->shouldReceive('validateHeader');
        $this->validator->shouldReceive('isHeaderOrFooter')->andReturn(false);
        $this->validator->shouldReceive('validateLineLength');
        $this->validator->shouldReceive('validateMinimumRecord')->andThrow(new IncompleteFileException('error'));

        $content = implode(PHP_EOL, [
            $this->buildFixedWidthLine([[89, 19, '123456780']]),
            $this->buildFixedWidthLine([]),
            $this->buildFixedWidthLine([]),
        ]) . PHP_EOL;

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);

        $this->expectException(IncompleteFileException::class);
        $this->expectExceptionMessage('error');

        $this->parser->parseFile('fake-equipment-file.txt');
    }

    /**
     * @param array<int, array{0:int, 1:int, 2:string}> $segments
     */
    private function buildFixedWidthLine(array $segments): string
    {
        $line = str_repeat(' ', 250);

        foreach ($segments as [$start, $length, $value]) {
            $line = substr_replace($line, str_pad(substr($value, 0, $length), $length), $start, $length);
        }

        return '|'.$line.'|';
    }
}
