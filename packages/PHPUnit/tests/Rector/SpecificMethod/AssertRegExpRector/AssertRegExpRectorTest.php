<?php declare(strict_types=1);

namespace Rector\PHPUnit\Tests\Rector\SpecificMethod\AssertRegExpRector;

use Iterator;
use Rector\PHPUnit\Rector\SpecificMethod\AssertRegExpRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class AssertRegExpRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideDataForTest()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideDataForTest(): Iterator
    {
        yield [__DIR__ . '/Fixture/fixture.php.inc'];
    }

    protected function getRectorClass(): string
    {
        return AssertRegExpRector::class;
    }
}
