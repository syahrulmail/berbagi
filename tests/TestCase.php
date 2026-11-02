<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Setting menyimpan cache statis per proses. Pada server produksi
        // setiap request adalah proses baru, tetapi pada test prosesnya
        // dibagi antar-kasus sehingga cache harus direset.
        Setting::flushMemo();
    }
}
