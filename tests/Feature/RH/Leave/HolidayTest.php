<?php

namespace Tests\Feature\RH\Leave;

use App\Models\RH\Leave\Holiday;
use Illuminate\Support\Facades\Http;
use Tests\Feature\RH\RhTestCase;

class HolidayTest extends RhTestCase
{
    protected string $model = Holiday::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('holiday.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('holiday.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('holiday.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('holiday.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('holiday.destroy', $item->id));
        $response->assertStatus(204);
    }

    public function test_sync_stores_portuguese_names_from_api(): void
    {
        Http::fake([
            'date.nager.at/api/v3/PublicHolidays/*/AO' => Http::response([
                ['date' => '2026-01-01', 'localName' => 'Dia de Ano Novo', 'name' => "New Year's Day"],
                ['date' => '2026-04-03', 'localName' => 'Sexta Feira Santa', 'name' => 'Good Friday'],
                ['date' => '2026-12-25', 'localName' => 'Dia de Natal', 'name' => 'Christmas Day'],
            ]),
        ]);

        $response = $this->postJsonAuth(route('holiday.sync'), ['year' => 2026]);
        $response->assertStatus(200)
            ->assertJsonPath('message', '3 feriados de 2026 sincronizados com sucesso.');

        $this->assertSame(
            'Dia de Ano Novo',
            $this->model::whereDate('date', '2026-01-01')->value('name')
        );
        $this->assertSame(
            'Dia de Natal',
            $this->model::whereDate('date', '2026-12-25')->value('name')
        );
    }

    public function test_sync_translates_english_names_when_local_name_missing(): void
    {
        Http::fake([
            'date.nager.at/api/v3/PublicHolidays/*/AO' => Http::response([
                ['date' => '2026-11-11', 'localName' => '', 'name' => 'Independence Day'],
            ]),
        ]);

        $response = $this->postJsonAuth(route('holiday.sync'), ['year' => 2026]);
        $response->assertStatus(200);

        $this->assertSame(
            'Dia da Independência',
            $this->model::whereDate('date', '2026-11-11')->value('name')
        );
    }
}
