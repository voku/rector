<?php declare(strict_types=1);

namespace Rector\Celebrity\Tests\Rector\NotEqual\CommonNotEqualRector;

use Iterator;
use Rector\Celebrity\Rector\NotEqual\CommonNotEqualRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class CommonNotEqualRectorTest extends AbstractRectorTestCase
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
        return CommonNotEqualRector::class;
    }
}
