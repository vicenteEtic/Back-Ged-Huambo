<?php

namespace Tests\Feature\Process;

use Tests\Feature\RH\RhTestCase;
use App\Models\Process\Process;
use App\Models\RH\Department\Department;

class ProcessTest extends RhTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Department::unguard(false);
    }

    public function test_can_list(): void
    {
        Process::factory()->count(3)->create();
        $response = $this->getJsonAuth(route('process.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $dept = Department::factory()->create();
        $user = $this->user;

        $data = Process::factory()->make([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'origin_department_id' => $dept->id,
            'received_by' => $user->id,
            'created_by' => $user->id,
        ])->toArray();

        $response = $this->postJsonAuth(route('process.store'), $data);
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'sequence_number',
            'subject',
            'status',
            'current_department_id',
        ]);
    }

    public function test_can_show(): void
    {
        $process = Process::factory()->create();
        $response = $this->getJsonAuth(route('process.show', $process->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $process = Process::factory()->create();
        $data = $process->toArray();
        $data['subject'] = 'Assunto atualizado';

        $response = $this->putJsonAuth(route('process.update', $process->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $process = Process::factory()->create();
        $response = $this->deleteJsonAuth(route('process.destroy', $process->id));
        $response->assertStatus(204);
    }

    public function test_can_show_movements(): void
    {
        $process = Process::factory()->create();
        $response = $this->getJsonAuth(route('process.movements', $process->id));
        $response->assertStatus(200);
    }

    public function test_can_show_comments(): void
    {
        $process = Process::factory()->create();
        $response = $this->getJsonAuth(route('process.comments', $process->id));
        $response->assertStatus(200);
    }
}
