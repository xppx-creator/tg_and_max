<?php

it('returns table configuration for config section', function () {
    $response = $this->getJson('/api/v1/analytics/stub?section=config');

    $response->assertOk();
    $response->assertJsonStructure([
        'columns',
        'bulkActions',
    ]);
    $response->assertJsonPath('columns.0.code', 'when');
});

it('returns rows from panel for data section', function () {
    $response = $this->getJson('/api/v1/analytics/stub?section=data');

    $response->assertOk();
    $response->assertJsonPath('total', 1);
    $response->assertJsonPath('rows.0.id', 1);
});

it('returns metrics for metrics section', function () {
    $response = $this->getJson('/api/v1/analytics/stub?section=metrics');

    $response->assertOk();
    $response->assertJsonPath('metrics.0.label', 'Всего');
    $response->assertJsonPath('metrics.0.value', 1);
});

it('serves second registered panel', function () {
    $response = $this->getJson('/api/v1/analytics/logs?section=config');

    $response->assertOk();
    $response->assertJsonPath('columns.0.code', 'when');
});

it('returns detail payload for detail section', function () {
    $response = $this->getJson('/api/v1/analytics/stub?section=detail&id=1');

    $response->assertOk();
    $response->assertJsonPath('detail.id', 1);
});

it('returns validation error for detail without id', function () {
    $response = $this->getJson('/api/v1/analytics/stub?section=detail');

    $response->assertUnprocessable();
});

it('returns validation error for unknown section', function () {
    $response = $this->getJson('/api/v1/analytics/stub?section=unknown');

    $response->assertUnprocessable();
});

it('returns not found for unregistered panel route', function () {
    $response = $this->getJson('/api/v1/analytics/unknown?section=config');

    $response->assertNotFound();
});

it('uses string-contains-filter in assembled config', function () {
    $response = $this->getJson('/api/v1/analytics/logs?section=config');

    $response->assertOk();
    $filters = collect($response->json('columns'))
        ->flatMap(fn ($column) => $column['filters'] ?? [])
        ->firstWhere('code', 'title_str');

    expect($filters['settings']['type'] ?? null)->toBe('string-contains-filter');
});
