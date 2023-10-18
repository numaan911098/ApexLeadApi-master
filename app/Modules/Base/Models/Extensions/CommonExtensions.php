<?php

namespace App\Modules\Base\Models\Extensions;

use Facades\App\Services\Util;

trait CommonExtensions
{
    /**
     * Get array of columns with as prefix.
     *
     * @param array $models Models array.
     * @return array
     */
    public function getAsPrefixedSelectClause(array $models): array
    {
        $columns = [];

        foreach ($models as $model) {
            $columns[$model] = Util::getAsPrefixedTableColumns($model);
        }

        $clause = [];

        foreach ($columns as $columns) {
            $clause = array_merge($clause, array_values($columns));
        }

        return $clause;
    }

    /**
     * Extract models data into array.
     *
     * @param object $model Model instance.
     * @param array $rows Rows data.
     * @param array $modelAttributes Model table columns.
     * @param array $relations Load model relations also.
     * @param array $conditions Condition for model.
     * @return array
     */
    public function getRowsFields(
        object $model,
        array $rows,
        array $modelAttributes,
        array $relations = [],
        array $conditions = []
    ): array {
        $rowsFields = [];
        $table = $model->getTable();

        foreach ($rows as $row) {
            $row = (array) $row;
            $id = $row[$table . '.id'];

            if (is_null($id) || isset($rowsFields[$id])) {
                continue;
            }

            $matched = true;

            foreach ($conditions as $condition) {
                $matched = $row[$table . '.' . $condition['field']] === $condition['value'];
            }

            if (!$matched) {
                continue;
            }

            $rowsFields[$id] = $this->getRowFields($model, $row, $modelAttributes);

            foreach ($relations as $relation) {
                $rowsFields[$id][$relation['key']] = $this->getRowsFields(
                    $relation['model'],
                    $rows,
                    $relation['modelAttributes'],
                    [],
                    [['field' => $relation['condition'], 'value' => $id]]
                );
            }
        }

        return $rowsFields;
    }

    /**
     * Extract model data into array.
     *
     * @param object $model Model instance.
     * @param array $row Row data.
     * @param array $modelAttributes Model table columns.
     * @return array
     */
    public function getRowFields(object $model, array $row, array $modelAttributes): array
    {
        $rowFields = [];
        $table = $model->getTable();

        foreach ($modelAttributes as $modelAttribute) {
            $columnFullName = $table . '.' . $modelAttribute;

            if (is_null($row[$columnFullName]) && $modelAttribute === 'id') {
                break;
            }

            $rowFields[$modelAttribute] = $row[$columnFullName];
        }

        return $rowFields;
    }
}
