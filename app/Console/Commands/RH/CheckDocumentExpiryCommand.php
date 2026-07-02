<?php

namespace App\Console\Commands\RH;

use App\Models\RH\EmployeeDocument\EmployeeDocument;
use App\Notifications\RH\DocumentExpiryNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDocumentExpiryCommand extends Command
{
    protected $signature = 'rh:check-document-expiry {--days=30 : Number of days before expiry to notify}';
    protected $description = 'Send notifications for documents expiring within the specified days';

    public function handle(): void
    {
        $days = (int) $this->option('days');
        $targetDate = Carbon::today()->addDays($days);

        $documents = EmployeeDocument::whereDate('expiry_date', '<=', $targetDate)
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->where('is_verified', false)
            ->with('employee.user')
            ->get();

        if ($documents->isEmpty()) {
            $this->info('No documents expiring soon.');
            return;
        }

        foreach ($documents as $document) {
            $daysLeft = Carbon::today()->diffInDays($document->expiry_date, false) + 1;

            if ($document->employee && $document->employee->user) {
                $document->employee->user->notify(new DocumentExpiryNotification($document, $daysLeft));
                $this->info("Expiry notification sent for document: {$document->name} (employee: {$document->employee->full_name})");
            }
        }

        $this->info("Document expiry notifications sent for {$documents->count()} document(s).");
    }
}
