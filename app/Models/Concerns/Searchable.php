<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Tìm gần đúng một từ khóa trên nhiều cột (OR giữa các cột): Model::search('an', ['sku', 'name']).
     *
     * Ký tự đặc biệt của LIKE là % và _ được escape, nên người dùng gõ "%" chỉ tìm đúng ký tự "%".
     * Dùng "!" làm ký tự escape vì cách này chạy giống nhau trên MySQL và SQLite.
     * Tên cột do code truyền vào (không lấy từ URL).
     *
     * @param  array<int, string>  $columns
     */
    public function scopeSearch(Builder $query, ?string $keyword, array $columns): Builder
    {
        if ($keyword === null || $keyword === '' || $columns === []) {
            return $query;
        }

        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword).'%';

        return $query->where(function (Builder $q) use ($columns, $pattern) {
            foreach ($columns as $column) {
                $q->orWhereRaw($q->getGrammar()->wrap($column)." like ? escape '!'", [$pattern]);
            }
        });
    }
}
