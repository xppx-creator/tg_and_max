<?php

use Makeroi\Analitics\Services\PanelRegistry;
use Makeroi\Analitics\Tests\Support\StubAnalyticsPanel;

it('serves spa index with injected bootstrap payload', function () {
    $dist = dirname(__DIR__, 2).'/dist/index.html';

    if (! is_file($dist)) {
        test()->markTestSkipped('SPA dist is not built. Run: npm run build in package directory.');
    }

    config(['makeroi.analitycs.spa.enabled' => true]);
    config(['makeroi.analitycs.spa.route_prefix' => 'analytics']);
    config(['makeroi.analitycs.spa.window_key' => '__MAKEROI_ANALITYCS__']);
    config(['makeroi.analitycs.default_panel' => 'stub']);

    $response = $this->get('/analytics?token=test-token&lang=ru');

    $response->assertOk();
    $response->assertHeader('content-type', 'text/html; charset=UTF-8');

    $html = $response->getContent();
    expect($html)->toContain('window["__MAKEROI_ANALITYCS__"]');
    expect($html)->toContain('"defaultPanel":"stub"');
    expect($html)->toContain('"tableEndpoint":"/analytics/stub"');
    expect($html)->toContain('"token":"test-token"');
    expect($html)->toContain('"slug":"stub"');
    expect($html)->toContain('"slug":"logs"');
});

it('rejects duplicate panel slugs on registry assert', function () {
    $registry = new PanelRegistry;

    expect(fn () => $registry->assertValidPanels([
        StubAnalyticsPanel::class,
        StubAnalyticsPanel::class,
    ]))->toThrow(InvalidArgumentException::class);
});
