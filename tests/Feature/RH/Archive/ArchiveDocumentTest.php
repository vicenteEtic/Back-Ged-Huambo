<?php

namespace Tests\Feature\RH\Archive;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Archive\ArchiveDocument;
use App\Models\RH\Archive\ArchiveCategory;
use App\Models\RH\Archive\ArchiveDocumentVersion;
use App\Models\RH\Archive\ArchiveDocumentShare;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class ArchiveDocumentTest extends RhTestCase
{
    protected Employee $employee;
    protected ArchiveCategory $category;
    protected User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $this->employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $this->user->id,
        ]);

        $this->category = ArchiveCategory::factory()->create();
        $this->creator = User::factory()->create();
    }

    public function test_can_list()
    {
        ArchiveDocument::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/archive/documents');
        $response->assertStatus(200);
    }

    public function test_can_create()
    {
        $data = ArchiveDocument::factory()->make([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/archive/documents', $data);
        $response->assertStatus(201);
    }

    public function test_can_show()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/archive/documents/' . $doc->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $data = ArchiveDocument::factory()->make([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/archive/documents/' . $doc->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/archive/documents/' . $doc->id);
        $response->assertStatus(204);
    }

    public function test_can_search()
    {
        ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
            'title' => 'Relatório Anual 2024',
            'status' => 'active',
        ]);

        $response = $this->getJsonAuth('/api/rh/archive/documents/search?q=Relatório');
        $response->assertStatus(200);
    }

    public function test_can_search_with_filters()
    {
        $response = $this->getJsonAuth('/api/rh/archive/documents/search?type=pdf&status=active&confidentiality=internal');
        $response->assertStatus(200);
    }

    public function test_can_approve()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
            'status' => 'draft',
        ]);

        $response = $this->postJsonAuth('/api/rh/archive/documents/' . $doc->id . '/approve');
        $response->assertStatus(200);
    }

    public function test_can_archive_document()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
            'status' => 'active',
        ]);

        $response = $this->postJsonAuth('/api/rh/archive/documents/' . $doc->id . '/archive');
        $response->assertStatus(200);
    }

    public function test_can_list_versions()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        ArchiveDocumentVersion::factory()->count(2)->create([
            'archive_document_id' => $doc->id,
            'created_by' => $this->creator->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/archive/documents/' . $doc->id . '/versions');
        $response->assertStatus(200);
    }

    public function test_can_create_version()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $data = ArchiveDocumentVersion::factory()->make([
            'archive_document_id' => $doc->id,
            'created_by' => $this->creator->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/archive/documents/' . $doc->id . '/versions', $data);
        $response->assertStatus(201);
    }

    public function test_can_list_shares()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        ArchiveDocumentShare::factory()->count(2)->create([
            'archive_document_id' => $doc->id,
            'shared_by' => $this->creator->id,
            'shared_with_user_id' => $this->user->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/archive/documents/' . $doc->id . '/shares');
        $response->assertStatus(200);
    }

    public function test_can_create_share()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $data = ArchiveDocumentShare::factory()->make([
            'archive_document_id' => $doc->id,
            'shared_by' => $this->creator->id,
            'shared_with_user_id' => $this->user->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/archive/documents/' . $doc->id . '/shares', $data);
        $response->assertStatus(201);
    }

    public function test_can_destroy_share()
    {
        $doc = ArchiveDocument::factory()->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $share = ArchiveDocumentShare::factory()->create([
            'archive_document_id' => $doc->id,
            'shared_by' => $this->creator->id,
            'shared_with_user_id' => $this->user->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/archive/documents/' . $doc->id . '/shares/' . $share->id);
        $response->assertStatus(204);
    }

    public function test_can_list_categories()
    {
        ArchiveCategory::factory()->count(3)->create();

        $response = $this->getJsonAuth('/api/rh/archive/categories');
        $response->assertStatus(200);
    }

    public function test_can_get_category_tree()
    {
        ArchiveCategory::factory()->count(3)->create();

        $response = $this->getJsonAuth('/api/rh/archive/categories/tree');
        $response->assertStatus(200);
    }

    public function test_can_get_documents_by_employee()
    {
        ArchiveDocument::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/archive/documents/by-employee/' . $this->employee->id);
        $response->assertStatus(200);
    }

    public function test_can_get_documents_by_category()
    {
        ArchiveDocument::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'employee_id' => $this->employee->id,
            'created_by' => $this->creator->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/archive/documents/by-category/' . $this->category->id);
        $response->assertStatus(200);
    }
}
