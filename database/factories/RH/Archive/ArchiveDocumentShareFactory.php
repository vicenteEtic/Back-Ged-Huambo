<?php

namespace Database\Factories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocument;
use App\Models\RH\Archive\ArchiveDocumentShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArchiveDocumentShareFactory extends Factory
{
    protected $model = ArchiveDocumentShare::class;

    public function definition(): array
    {
        return [
            'archive_document_id' => ArchiveDocument::factory(),
            'shared_with_user_id' => User::factory(),
            'permission' => fake()->randomElement(['view', 'download', 'edit']),
            'expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'shared_by' => User::factory(),
        ];
    }
}
