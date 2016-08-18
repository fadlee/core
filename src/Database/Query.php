<?php
namespace TypeRocket\Database;

class Query
{
    public $table = null;
    public $idColumn = 'id';

    public $lastCompiledSQL = null;
    public $cachedResult = null;
    public $returnOne = false;
    protected $query = [];

    /**
     * Get Date Time
     *
     * @return bool|string
     */
    public function getDateTime()
    {
        return date('Y-m-d H:i:s', time());
    }

    /**
     * Find all
     *
     * @param array|\ArrayObject $ids
     *
     * @return Query $this
     */
    public function findAll( $ids = [] )
    {
        if(!empty($ids)) {
            $this->where( $this->idColumn , 'IN', $ids);
        }

        return $this;
    }

    /**
     * Get results from find methods
     *
     * @return array|null|object
     */
    public function get() {
        return $this->runQuery();
    }

    /**
     * Where
     *
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     * @param string $condition
     *
     * @return $this
     */
    public function where($column, $arg1, $arg2 = null, $condition = 'AND')
    {
        $whereQuery = [];

        if( !empty($this->query['where']) ) {
            $whereQuery['condition'] = strtoupper($condition);
        } else {
            $whereQuery['condition'] = 'WHERE';
        }

        $whereQuery['column'] = $column;
        $whereQuery['operator'] = '=';
        $whereQuery['value'] = $arg1;

        if( isset($arg2) ) {
            $whereQuery['operator'] = $arg1;
            $whereQuery['value'] = $arg2;
        }

        $this->query['where'][] = $whereQuery;

        return $this;
    }

    /**
     * Or Where
     *
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     *
     * @return Query
     */
    public function orWhere($column, $arg1, $arg2 = null)
    {
        return $this->where($column, $arg1, $arg2, 'OR');
    }

    /**
     * Order by
     *
     * @param string $column name of column
     * @param string $direction default ASC other DESC
     *
     * @return $this
     */
    public function orderBy($column = 'id', $direction = 'ASC')
    {
        $this->query['order_by']['column'] = $column;
        $this->query['order_by']['direction'] = $direction;

        return $this;
    }

    /**
     * Take only a select group
     *
     * @param $limit
     *
     * @param int $offset
     *
     * @return $this
     */
    public function take( $limit, $offset = 0 ) {
        $this->query['take']['limit'] = (int) $limit;
        $this->query['take']['offset'] = (int) $offset;

        return $this;
    }

    /**
     * @return array|bool|false|int|null|object
     */
    public function first() {
        $this->returnOne = true;
        $this->take(1);
        return $this->get();
    }

    /**
     * Create resource by TypeRocket fields
     *
     * When a resource is created the Model ID should be set to the
     * resource's ID.
     *
     * @param array|\ArrayObject $fields
     *
     * @return mixed
     */
    public function create( $fields)
    {
        $this->query['create'] = true;
        unset($this->query['select']);
        $this->query['data'] = $fields;

        return $this->runQuery();
    }

    /**
     * Update resource by TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return mixed
     */
    public function update( $fields = [])
    {
        $this->query['update'] = true;
        unset($this->query['select']);
        $this->query['data'] = $fields;

        return $this->runQuery();
    }

    /**
     * Find resource by ID
     *
     * @param $id
     *
     * @return $this
     */
    public function findById($id)
    {
        $this->returnOne = true;
        return $this->where( $this->idColumn, $id)->take(1)->findAll();
    }

    /**
     * Find by ID or die
     *
     * @param $id
     *
     * @return object
     */
    public function findOrDie($id) {
        if( ! $data = $this->findById($id)->get() ) {
            wp_die('Something went wrong');
        }

        return $data;
    }

    /**
     * Find first where of die
     *
     * @param $column
     * @param $arg1
     * @param null $arg2
     * @param string $condition
     *
     * @return object
     * @internal param $id
     *
     */
    public function findFirstWhereOrDie($column, $arg1, $arg2 = null, $condition = 'AND') {
        if( ! $data = $this->where( $column, $arg1, $arg2, $condition)->first() ) {
            wp_die('Something went wrong');
        }

        return $data;
    }

    /**
     * Delete
     *
     * @param array|\ArrayObject $ids
     *
     * @return array|false|int|null|object
     */
    public function delete( $ids = [] ) {
        $this->query['delete'] = true;
        unset($this->query['select']);

        if(!empty($ids)) {
            $this->where( $this->idColumn , 'IN', $ids);
        }

        return $this->runQuery();
    }

    /**
     * Count results
     *
     * @return array|bool|false|int|null|object
     */
    public function count()
    {
        $this->query['count'] = true;

        return $this->runQuery();
    }

    /**
     * Select only specific columns
     *
     * @param $args
     *
     * @return $this
     */
    public function select($args)
    {
        if( is_array($args) ) {
            $this->query['select'] = $args;
        } else {
            $this->query['select'] = func_get_args();
        }

        return $this;
    }

    /**
     * Run the SQL query from the query property
     *
     * @param array|\ArrayObject $query
     *
     * @return array|bool|false|int|null|object
     */
    protected function runQuery( $query = [] ) {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $table = $this->table;
        $sql_select_columns = '*';
        $sql_where = $sql_limit = $sql_values = $sql_columns = $sql_update = $sql = $sql_order ='';

        if( empty($query) ) {
            $query = $this->query;
        }

        // compile where
        if( !empty($query['where']) ) {
            foreach( $query['where'] as $where ) {

                if( is_array($where['value']) ) {

                    $where['value'] = array_map(function($value) use ($wpdb) {
                        return $wpdb->prepare( '%s', $value );
                    }, $where['value']);

                    $where['value'] = '(' . implode(',', $where['value']) . ')';
                } else {
                    $where['value'] = $wpdb->prepare( '%s', $where['value'] );
                }

                $sql_where .= ' ' . implode(' ', $where);
            }
        }

        // compile insert
        if( !empty($query['create']) && !empty($query['data']) ) {
            $inserts = $columns = [];
            foreach( $query['data'] as $column => $data ) {
                $columns[] = preg_replace("/[^a-z0-9\\\\_]+/", '', $column);

                if( is_array($data) ) {
                    $inserts[] = $wpdb->prepare( '%s', json_encode($data) );
                } else {
                    $inserts[] = $wpdb->prepare( '%s', $data );
                }
            }

            $sql_columns = ' (' . implode(',', $columns) . ') ';
            $sql_values .= ' ( ' . implode(',', $inserts) . ' ) ';
        }

        // compile update
        if( !empty($query['update']) && !empty($query['data']) ) {
            $inserts = $columns = [];
            foreach( $query['data'] as $column => $data ) {
                $columns[] = preg_replace("/[^a-zA-Z0-9\\\\_]+/", '', $column);

                if( is_array($data) ) {
                    $inserts[] = $wpdb->prepare( '%s', json_encode($data) );
                } else {
                    $inserts[] = $wpdb->prepare( '%s', $data );
                }
            }

            $sql_update = implode(', ', array_map(
                function ($v, $k) { return sprintf("%s=%s", $k, $v); },
                $inserts,
                $columns
            ));
        }

        // compile columns to select
        if( !empty($query['select']) && is_array($query['select']) ) {
            $sql_select_columns = trim(array_reduce($query['select'], function( $carry = '', $value ) use ( $table ) {
                return $carry . ',`' . preg_replace("/[^a-z0-9\\\\_]+/", '', $value) . '`';
            }), ',');
        }

        // compile take
        if( !empty($query['take']) ) {
            $sql_limit .= ' ' . $wpdb->prepare( 'LIMIT %d OFFSET %d', $query['take'] );
        }

        // compile order
        if( !empty($query['order_by']) ) {
            $order_column = preg_replace("/[^a-z0-9\\\\_]+/", '', $query['order_by']['column']);
            $order_direction = $query['order_by']['direction'] == 'ASC' ? 'ASC' : 'DESC';
            $sql_order .= " ORDER BY {$order_column} {$order_direction}";
        }

        if( array_key_exists('delete', $query) ) {
            $sql = 'DELETE FROM ' . $table . $sql_where;
            $result = $wpdb->query( $sql );
        } elseif( array_key_exists('create', $query) ) {
            $sql = 'INSERT INTO ' . $table . $sql_columns . ' VALUES ' . $sql_values;
            $result = false;
            if( $wpdb->query( $sql ) ) {
                $result = $wpdb->insert_id;
            };
        } elseif( array_key_exists('update', $query) ) {
            $sql = 'UPDATE ' . $table . ' SET ' . $sql_update . $sql_where;
            $result = $wpdb->query( $sql );
        } elseif( array_key_exists('count', $query) ) {
            $sql = 'SELECT COUNT(*) FROM '. $table . $sql_where . $sql_order . $sql_limit;
            $result = $wpdb->get_var( $sql );
        } else {
            $sql = 'SELECT ' . $sql_select_columns .' FROM '. $table . $sql_where . $sql_order . $sql_limit;
            $results = $wpdb->get_results( $sql, ARRAY_A );
            if($results && $this->returnOne) {
                $result = $results[0];
            } else {
                $result = new Results();
                foreach ($results as $object) {
                    $result->prepend($object);
                }
            }
        }

        $this->lastCompiledSQL = $sql;

        return $result;
    }
}