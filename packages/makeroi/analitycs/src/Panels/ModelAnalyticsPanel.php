<?php

namespace Makeroi\Analitics\Panels;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Makeroi\Analitics\Columns\Column;
use Makeroi\Analitics\Detail\DetailModal;
use Makeroi\Analitics\Services\ModelTableDataResolver;
use packages\makeroi\analitycs\src\Services\PanelViewConfigAssembler;

/**
 * Панель с декларативными колонками и Eloquent-моделью.
 *
 * Фильтры, сортировка и пагинация обрабатываются пакетом по query-параметрам запроса.
 */
abstract class ModelAnalyticsPanel extends AnalyticsPanel
{
    /**
     * @return class-string<Model>
     */
    abstract public function model(): string;

    /**
     * @return list<Column>
     */
    abstract public function columns(): array;

    public function newQuery(): Builder
    {
        return $this->model()::query();
    }

    /**
     * Базовый запрос до фильтров (скоупы, join, where по умолчанию).
     */
    public function query(): Builder
    {
        return $this->newQuery();
    }

    public function config(): array
    {
        $detail = $this->detail();

        return app(PanelViewConfigAssembler::class)->assemble(
            columns: $this->columns(),
            bulkActions: $this->bulkActions(),
            detail: $detail instanceof DetailModal ? $detail->toArray() : $detail,
        );
    }

    public function data(Request $request): array
    {
        return app(ModelTableDataResolver::class)->resolve(
            $this->query(),
            $this->columns(),
            $request,
            $this->resolvePerPage($request),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function bulkActions(): array
    {
        return [];
    }

    /**
     * View-шаблон модалки (section=config → detail).
     * Предпочтительно {@see DetailModal::make()}.
     *
     * @return DetailModal|array<string, mixed>|null
     */
    protected function detail(): DetailModal|array|null
    {
        return null;
    }

    /**
     * Данные модалки по id строки (section=detail&id=).
     *
     * @return array<string, mixed>
     */
    public function detailData(Request $request): array
    {
        $id = $request->query('id', $request->input('id'));

        if ($id === null || $id === '') {
            abort(422, 'Query parameter id is required for section=detail.');
        }

        return $this->detailPayload($this->findForDetail($id));
    }

    /**
     * @return array<string, mixed>
     */
    protected function detailPayload(Model $model): array
    {
        return [];
    }

    protected function findForDetail(int|string $id): Model
    {
        $modelClass = $this->model();
        /** @var Model $probe */
        $probe = new $modelClass;
        $qualifiedKey = $probe->getQualifiedKeyName();

        /** @var Model|null $model */
        $model = (clone $this->query())->where($qualifiedKey, $id)->first();

        if ($model === null) {
            abort(404, 'Analytics row not found.');
        }

        return $model;
    }
}
