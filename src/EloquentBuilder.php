<?php

namespace Rougin\Datatables;

use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent Builder
 *
 * @package Datatables
 * @author  Rougin Royce Gutib <rougingutib@gmail.com>
 */
class EloquentBuilder extends AbstractBuilder implements BuilderInterface
{
    /**
     * @var array
     */
    protected $getParameters;

    /**
     * @var mixed
     */
    protected $queryBuilder;

    /**
     * @param mixed  $builder
     * @param array  $get
     */
    public function __construct($builder, $get)
    {
        $this->getParameters = $get;
        $this->queryBuilder  = $builder;

        // If a model's name is injected.
        if (is_string($builder)) {
            $model = new $builder;

            $this->queryBuilder = $model->query();
            if (method_exists($builder, 'scopeDataTable')) {
                $this->queryBuilder->datatable();
            }
        }
    }

    /**
     * Generates a JSON response to the DataTable.
     *
     * @param  boolean $withKeys
     * @return array
     */
    public function make($withKeys = false)
    {
        // Model must have datatable scope
        $count = $this->queryBuilder->count();
        $data  = $this->getQueryResult($this->queryBuilder, $this->getParameters);

        return $this->getResponse($data, $count, $this->getParameters);
    }

    /**
     * Returns the data from the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  array                                 $get
     * @return array
     */
    protected function getQueryResult(Builder $builder, array $get)
    {
        $schema  = $builder->getModel()->getConnection()->getSchemaBuilder();
        $table   = $builder->getModel()->getTable();
        $columns = $schema->getColumnListing($table);

        $builder->dataTable(); // use datatable scope
        $builder->where(function ($query) use($columns, $get) {
            foreach ($columns as $index => $column) {
                $query->orWhere($column, 'LIKE', '%' . $get['search']['value'] . '%');
            }
        });

        $columns = $get['columns'];
        $order_column_id = $get['order'][0]['column'];
        $order = $columns[$order_column_id]['name'];
        $dir = $get['order'][0]['dir'];
        $dir = in_array($dir, ['asc', 'desc']) ? $dir : 'asc';

        $builder->limit($get['length']);
        $builder->offset($get['start']);
        $order ? $builder->orderBy($order, $dir) : '';

        return $builder->get()->toArray();
    }
}
