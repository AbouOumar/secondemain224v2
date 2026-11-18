<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aucun appel réseau réel pendant les tests (Djomy, Google, FCM...) :
        // tout appel HTTP doit être simulé avec Http::fake(), sinon le test échoue.
        Http::preventStrayRequests();
    }
}
