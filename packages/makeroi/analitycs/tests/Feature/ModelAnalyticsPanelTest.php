<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeroi\Analitics\Tests\Support\AnalyticsLogEntry;

uses(RefreshDatabase::class);

it('builds view config from column definitions', function () {
    $response = $this->getJson('/api/v1/analytics/logs?section=config');

    $response->assertOk();
    $response->assertJsonPath('columns.0.code', 'when');
    $response->assertJsonPath('columns.0.filters.0.settings.type', 'date-range-filter');
    $response->assertJsonPath('columns.1.filters.0.settings.type', 'string-contains-filter');
});

it('returns paginated model rows', function () {
    AnalyticsLogEntry::query()->create([
        'logged_at' => '2026-06-01 10:00:00',
        'title' => 'Alpha',
        'channel' => 'TG',
    ]);
    AnalyticsLogEntry::query()->create([
        'logged_at' => '2026-06-02 10:00:00',
        'title' => 'Beta',
        'channel' => 'MAX',
    ]);

    $response = $this->getJson('/api/v1/analytics/logs?section=data');

    $response->assertOk();
    $response->assertJsonPath('total', 2);
    $response->assertJsonPath('rows.0.cells.title', 'Alpha');
});

it('applies string filter from request', function () {
    AnalyticsLogEntry::query()->create([
        'logged_at' => '2026-06-01 10:00:00',
        'title' => 'Alpha deal',
        'channel' => 'TG',
    ]);
    AnalyticsLogEntry::query()->create([
        'logged_at' => '2026-06-02 10:00:00',
        'title' => 'Other',
        'channel' => 'MAX',
    ]);

    $response = $this->getJson('/api/v1/analytics/logs?section=data&filters[title_str]=Alpha');

    $response->assertOk();
    $response->assertJsonPath('total', 1);
    $response->assertJsonPath('rows.0.cells.title', 'Alpha deal');
});

it('applies sort from request', function () {
    AnalyticsLogEntry::query()->create([
        'logged_at' => '2026-06-01 10:00:00',
        'title' => 'Alpha',
        'channel' => 'TG',
    ]);
    AnalyticsLogEntry::query()->create([
        'logged_at' => '2026-06-02 10:00:00',
        'title' => 'Beta',
        'channel' => 'MAX',
    ]);

    $response = $this->getJson('/api/v1/analytics/logs?section=data&sort=title&sort_dir=desc');

    $response->assertOk();
    $response->assertJsonPath('rows.0.cells.title', 'Beta');
});
