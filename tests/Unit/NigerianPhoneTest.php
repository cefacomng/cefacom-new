<?php

namespace Tests\Unit;

use App\Support\NigerianPhone;
use PHPUnit\Framework\TestCase;

class NigerianPhoneTest extends TestCase
{
    public function test_it_normalises_every_common_way_of_writing_a_mobile_number(): void
    {
        foreach (['08031234567', '8031234567', '2348031234567', '+2348031234567', '+234 803 123 4567', '0803-123-4567', '(0803) 123 4567', ' 0803 123 4567 '] as $input) {
            $this->assertSame('+2348031234567', NigerianPhone::normalize($input), $input);
        }
        $this->assertSame('+2347012345678', NigerianPhone::normalize('07012345678'));
        $this->assertSame('+2349012345678', NigerianPhone::normalize('09012345678'));
        $this->assertSame('+2348112345678', NigerianPhone::normalize('08112345678'));
    }

    public function test_it_rejects_numbers_that_are_not_nigerian_mobiles(): void
    {
        foreach (['', 'abc', '0803123456', '080312345678', '+14155550123', '0123456789', '01-234-5678', '08231234567', '2348031234'] as $input) {
            $this->assertNull(NigerianPhone::normalize($input), $input);
        }
        $this->assertNull(NigerianPhone::normalize(null));
    }
}
