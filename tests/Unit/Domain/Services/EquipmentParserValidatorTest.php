<?php

namespace Tests\Unit\Domain\Services;

use App\Application\Exceptions\IncompleteFileException;
use App\Application\Exceptions\InvalidFileException;
use App\Domain\Services\EquipmentParserValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(EquipmentParserValidator::class)]
class EquipmentParserValidatorTest extends TestCase
{
    private EquipmentParserValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validator = new EquipmentParserValidator();
    }

    #[Group('happy_test')]
    public function testValidateHeader(): void
    {
        $lines = [
            '|------------------------------------------------------------------|',
            '|Material Material Description Size/dimensions Equipment Stat Location Room SLoc Superord.Equipment ManufactSerialNumber Serial Number|',
            '|Description of Technical Object Size/dimensions Gross Weight WUn Length Width Height Uni MS Plnt Plnt Cost Ctr Valid From to PP WkCtr Work ctr WorkCtr|',
            '|Work ctr Net Weight Old material no. MS PP S Created On Created By Chngd On Changed by Short description Short desc.|',
            '|------------------------------------------------------------------|',
        ];

        $this->validator->validateHeader($lines);
        $this->assertTrue(true);
    }

    #[Group('sad_test')]
    public function testValidateHeaderWhenRequiredColumnMissing(): void
    {
        $lines = [
            '|Material Material Description Size/dimensions Equipment Stat Location Room SLoc Superord.Equipment ManufactSerialNumber|',
            '|Description of Technical Object Size/dimensions Gross Weight WUn Length Width Height Uni MS Plnt Plnt Cost Ctr Valid From to PP WkCtr Work ctr WorkCtr|',
            '|Work ctr Net Weight Old material no. MS PP S Created On Created By Chngd On Changed by Short description Short desc.|',
        ];

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Invalid header');

        $this->validator->validateHeader($lines);
    }

    #[Group('happy_test')]
    public function testIsHeaderOrFooterForDataLine(): void
    {
        $line = '|THIS IS A DATA LIKE LINE|';
        $this->assertFalse($this->validator->isHeaderOrFooter($line));
    }

    #[Group('sad_test')]
    public function testIsHeaderOrFooterForHeaderLine(): void
    {
        $line = '|Material Description something|';
        $this->assertTrue($this->validator->isHeaderOrFooter($line));
    }

    #[Group('happy_test')]
    public function testValidateMinimumRecord(): void
    {
        $equipments = array_fill(0, 50, ['x' => 'y']);
        $this->validator->validateMinimumRecord($equipments);
        $this->assertTrue(true);
    }

    #[Group('sad_test')]
    public function testValidateMinimumRecordFalse(): void
    {
        $equipments = array_fill(0, 49, ['x' => 'y']);

        $this->expectException(IncompleteFileException::class);
        $this->expectExceptionMessage('Total rows as less than expected');

        $this->validator->validateMinimumRecord($equipments);
    }

    #[Group('happy_test')]
    public function testValidateLineLength(): void
    {
        $line = $this->fixedLengthLine(252);
        $this->validator->validateLineLength([$line, $line, $line]);
        $this->assertTrue(true);
    }

    #[Group('sad_test')]
    public function testValidateLineLengthWhenRowsAreNotThree(): void
    {
        $line = $this->fixedLengthLine(252);

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('not complete 3-line records');

        $this->validator->validateLineLength([$line, $line]);
    }

    #[Group('sad_test')]
    public function testValidateLineLengthWhenLengthBelowRequirement(): void
    {
        $good = $this->fixedLengthLine(252);
        $bad = $this->fixedLengthLine(251);

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('line length mismatch');

        $this->validator->validateLineLength([$good, $bad, $good]);
    }

    private function fixedLengthLine(int $length): string
    {
        return str_repeat('X', $length);
    }
}
