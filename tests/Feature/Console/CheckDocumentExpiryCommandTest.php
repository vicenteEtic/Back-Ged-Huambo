<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckDocumentExpiryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_expiry_command_runs_successfully()
    {
        $this->artisan('rh:check-document-expiry')
            ->assertExitCode(0);
    }
}
