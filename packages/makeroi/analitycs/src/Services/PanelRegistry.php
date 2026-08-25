<?php

namespace Makeroi\Analitics\Services;

use InvalidArgumentException;
use Makeroi\Analitics\Panels\AnalyticsPanel;

class PanelRegistry
{
    /**
     * @return list<class-string<AnalyticsPanel>>
     */
    public function panelClasses(): array
    {
        /** @var list<class-string<AnalyticsPanel>> $panels */
        $panels = config('makeroi.analitycs.panels', []);

        return $panels;
    }

    /**
     * @return iterable<string, AnalyticsPanel>
     */
    public function all(): iterable
    {

        foreach ($this->panelClasses() as $panelClass) {
            $panel = app($panelClass);

            if (! $panel->enabled()) {
                continue;
            }
            yield $panelClass::slug() => $panel;
        }
    }

    public function get(string $slug): AnalyticsPanel
    {
        foreach ($this->panelClasses() as $panelClass) {
            if ($panelClass::slug() === $slug) {
                return app($panelClass);
            }
        }

        throw new InvalidArgumentException("Analytics panel [{$slug}] is not registered.");
    }

    public function defaultSlug(): ?string
    {
        $configured = config('makeroi.analitycs.default_panel');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        foreach ($this->all() as $slug => $panel) {
            return $slug;
        }

        return null;
    }

    public function defaultPanel(): ?AnalyticsPanel
    {
        $slug = $this->defaultSlug();

        return $slug === null ? null : $this->get($slug);
    }

    /**
     * @param  list<class-string<AnalyticsPanel>>  $panelClasses
     */
    public function assertValidPanels(array $panelClasses): void
    {
        $slugs = [];

        foreach ($panelClasses as $panelClass) {
            if (! is_subclass_of($panelClass, AnalyticsPanel::class)) {
                throw new InvalidArgumentException(
                    'Analytics panel ['.$panelClass.'] must extend '.AnalyticsPanel::class.'.'
                );
            }

            $slug = $panelClass::slug();

            if ($slug === '') {
                throw new InvalidArgumentException("Analytics panel [{$panelClass}] has an empty slug.");
            }

            if (isset($slugs[$slug])) {
                throw new InvalidArgumentException(
                    "Duplicate analytics panel slug [{$slug}] for [{$panelClass}] and [{$slugs[$slug]}]."
                );
            }

            $slugs[$slug] = $panelClass;
        }
    }
}
