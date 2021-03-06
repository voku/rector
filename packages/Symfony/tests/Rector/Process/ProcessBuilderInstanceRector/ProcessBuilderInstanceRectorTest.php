<?php declare(strict_types=1);

namespace Rector\Symfony\Tests\Rector\Process\ProcessBuilderInstanceRector;

use Iterator;
use Rector\Symfony\Rector\Process\ProcessBuilderInstanceRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ProcessBuilderInstanceRectorTest extends AbstractRectorTestCase
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
        return ProcessBuilderInstanceRector::class;
    }
}
