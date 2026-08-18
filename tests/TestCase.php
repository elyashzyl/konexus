<?php

namespace Tests;

use App\Support\CampusContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CampusContext is a request-scoped static that must not leak between
        // tests, otherwise AUTO_ANCHORED_MODELS (e.g. grade levels) get
        // anchored to a campus id that no longer exists in the fresh DB.
        CampusContext::clear();
    }
}
