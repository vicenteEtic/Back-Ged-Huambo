<?php

namespace Tests\Unit\RH\Leave;

use App\Models\RH\Leave\Holiday;
use App\Repositories\RH\Leave\HolidayRepository;
use App\Services\RH\Leave\HolidayService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class HolidayServiceTest extends TestCase
{
    private HolidayService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new HolidayService(new HolidayRepository(new Holiday));
    }

    public function test_good_friday_2024()
    {
        $this->assertTrue($this->service->isGoodFriday(Carbon::parse('2024-03-29')));
    }

    public function test_good_friday_2025()
    {
        $this->assertTrue($this->service->isGoodFriday(Carbon::parse('2025-04-18')));
    }

    public function test_easter_sunday_is_not_good_friday()
    {
        $this->assertFalse($this->service->isGoodFriday(Carbon::parse('2025-04-20')));
    }

    public function test_normal_weekday_is_not_good_friday()
    {
        $this->assertFalse($this->service->isGoodFriday(Carbon::parse('2025-08-07')));
    }
}
