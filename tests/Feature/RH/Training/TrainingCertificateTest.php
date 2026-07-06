<?php

namespace Tests\Feature\RH\Training;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Training\TrainingCertificate;

class TrainingCertificateTest extends RhTestCase
{
    public function test_certificate_factory_creates_model()
    {
        $certificate = TrainingCertificate::factory()->create();
        $this->assertNotNull($certificate->id);
        $this->assertNotNull($certificate->certificate_number);
    }
}
