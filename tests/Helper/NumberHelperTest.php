<?php

namespace App\Tests\Helper;

use App\Helper\NumberHelper;
use PHPUnit\Framework\TestCase;

class NumberHelperTest extends TestCase
{
    public function testRound2(): void
    {
        $this->assertSame(4.25, NumberHelper::round2(4.254));
        $this->assertSame(4.26, NumberHelper::round2(4.255));
        $this->assertSame(0.0, NumberHelper::round2(0));
        $this->assertSame(123.46, NumberHelper::round2(123.4567));
        $this->assertSame(-5.43, NumberHelper::round2(-5.4321));
    }
}
