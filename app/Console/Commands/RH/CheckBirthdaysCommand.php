<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Employee\Employee;
use App\Notifications\RH\BirthdayNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckBirthdaysCommand extends Command
{
    protected $signature = 'rh:check-birthdays';
    protected $description = 'Send birthday notifications for employees whose birthday is today';

    public function handle(): void
    {
        $today = Carbon::today();

        $employees = Employee::whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->where('status', 'active')
            ->with('user')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('No birthdays today.');
            return;
        }

        foreach ($employees as $employee) {
            if ($employee->user) {
                $employee->user->notify(new BirthdayNotification($employee));
                $this->info("Birthday notification sent for: {$employee->full_name}");
            }
        }

        $this->info("Birthday notifications sent for {$employees->count()} employee(s).");
    }
}
