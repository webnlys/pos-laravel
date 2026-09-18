<?php

namespace Tests\Unit;

use App\Support\MoneyWords;
use Tests\TestCase;

class MoneyWordsTest extends TestCase
{
    public function test_converts_whole_amount(): void
    {
        $this->assertSame('Ten Saudi Riyals Only', MoneyWords::convert(10, 'SAR'));
        $this->assertSame('One UAE Dirham Only', MoneyWords::convert(1, 'AED'));
    }

    public function test_converts_amount_with_fraction(): void
    {
        $this->assertSame(
            'One Hundred Twenty-Five UAE Dirhams and Fifty Fils Only',
            MoneyWords::convert(125.50, 'AED')
        );
    }

    public function test_converts_zero(): void
    {
        $this->assertSame('Zero UAE Dirhams Only', MoneyWords::convert(0, 'AED'));
    }
}
