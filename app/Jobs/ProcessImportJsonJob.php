<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Services\Transation\TransactionService;

class ProcessImportJsonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected int $userId;

    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $content = Storage::get($this->filePath);
        $data = json_decode($content, true);

        if (empty($data['records'])) {
            return;
        }

        // Chama o service para despachar os jobs em batches
        app(TransactionService::class)->dispatchImportJobs($data['records'], $this->userId);

        // Remove o arquivo temporário
        Storage::delete($this->filePath);
    }
}
