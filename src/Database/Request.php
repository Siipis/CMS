<?php
namespace CMS\Database;

use CMS\Exception\RequestSyntaxException;
use Illuminate\Database\Query\Builder;

class Request
{
    // Request data
    protected $request;
    protected $accessor;
    protected $model;
    protected $take;
    protected $scope;
    protected $keys;
    protected $order;

    // Database query
    protected $query;

    // Models that require joins
    protected $joins;

    // Model configuration
    protected $visibleKeys;
    protected $hiddenKeys;

    // Compilation helpers
    protected $usesTable;
    protected $isLimited;
    protected $returnsFirst;

    public function __construct($request)
    {
        // Store the request data
        $this->request = $request;
        $this->accessor = $request['accessor'];
        $this->model = $request['model'];
        $this->take = $request['take'];
        $this->scope = $request['scope'];
        $this->keys = $request['keys'];
        $this->order = $request['order'];

        // Prepare variables
        $this->prepare();

        // Build the database query
        $this->query = $this->buildQuery();
    }

    /**
     * Prepare variables
     */
    protected function prepare()
    {
        // TODO: find a better place for this config
        $this->joins = [
            'user',
        ];

        // Use a table instead of a model
        if (in_array($this->model, $this->joins))
        {
            $this->usesTable = true;
        }

        // The results are limited
        if(count($this->take) > 1) {
            $this->isLimited = true;
        }

        // Fetch a list of hidden and visible table keys
        $visibleKeys = [];
        $hiddenKeys = [];

        if ($this->isJoined($this->model))
        {
            $className = '\App\\' . ucfirst($this->model);
            $model = new $className();

            $hidden = $model->getHidden();
            $visible = $model->getVisible();

            $className = '\App\\' . ucfirst(substr($this->getJoinTable($this->model), 0, -1));
            $model = new $className();

            $joinHidden = preg_filter('/^/', $this->getJoinTable($this->model).'.', $model->getHidden());
            $joiVisible = preg_filter('/^/', $this->getJoinTable($this->model).'.', $model->getVisible());

            $hiddenKeys = array_merge($hidden, $joinHidden);
            $visibleKeys = array_merge($visible, $joiVisible);
        }

        $this->hiddenKeys = $hiddenKeys;
        $this->visibleKeys = $visibleKeys;

        // If request has no keys, default to fetching all visible keys
        if (empty($this->keys))
        {
            $this->keys = $this->visibleKeys;
        }
    }

    /**
     * Runs the query and returns the result
     *
     * @return array
     */
    public function query()
    {
        $result = null;
        eval('$result = ' . $this->query);

        if ($result instanceof Builder) {
            $result = $result->get();
        }

        return $result;
    }

    /**
     * Builds the PHP database query
     *
     * @return string
     */
    protected function buildQuery()
    {
        return $this
            ->getModelQuery()
            ->getSelectQuery()
            ->getJoinQuery()
            ->getWhereQuery()
            ->getOrderQuery()
            ->getTakeQuery()
            ->closeQuery();
    }

    /**
     * Builds the query method (DB or Model)
     *
     * @return $this
     */
    private function getModelQuery()
    {
        if ($this->usesTable) {
            $this->addQuery("\\DB::table('$this->model"."s')");
        } else {
            $this->addQuery("\\App\\" . ucfirst($this->model) . "::");
        }

        return $this;
    }

    /**
     * Builds the where clause
     *
     * @return $this
     */
    private function getWhereQuery()
    {
        $scopes = $this->scope;

        // Also supports the ::all() call
        if (in_array('all', $scopes)) {
            if (!$this->usesTable && !$this->isLimited && !$this->returnsFirst) {
                $this->addQuery('all()');
            }

            unset($scopes['all']);
        }

        // Loop through scopes
        foreach ($scopes as $key => $scope)
        {
            if (is_int($key)) {
                continue;
            }

            $key = $this->getColumn($key);
            $value = '\'' . secure_string($scope) . '\'';

            $this->addQuery("where('$key', $value)");
        }

        return $this;
    }

    /**
     * Builds the select clause
     *
     * @return $this
     */
    private function getSelectQuery()
    {
        $col = null;

        foreach($this->keys as $i => $key)
        {
            if ($i > 0) {
                $col .= ', ';
            }

            $key = $this->getColumn($key);

            $col .= "'$key'";
        }

        if (!is_null($col)) {
            $this->addQuery("select($col)");
        }

        return $this;
    }

    /**
     * Builds the join clause
     *
     * @return $this
     */
    private function getJoinQuery()
    {
        if ($this->model == 'user')
        {
            $this->addQuery("join('profiles', 'users.id', '=', 'profiles.user_id')");
        }

        return $this;
    }

    /**
     * Builds the orderBy clause
     *
     * @return $this
     */
    private function getOrderQuery()
    {
        foreach($this->order as $order)
        {
            $s = explode(':', $order);

            $key = $this->getColumn($s[1]);
            $order = $s[0];

            $this->addQuery("orderBy('$key', '$order')");
        }

        return $this;
    }

    /**
     * Builds one of three clauses: first, pagination, or take
     *
     * @return $this
     */
    private function getTakeQuery()
    {
        if (!empty($this->take)) {
            if ($this->take['count'] == 1) {
                $this->addQuery('first()');
                $this->returnsFirst = true;
            }
            else if ($this->take['pagination']) {
                $this->addQuery('simplePaginate(' . $this->take['count'] . ')');
            } else {
                $this->addQuery('take(' . $this->take['count'] . ')');
            }
        }

        return $this;
    }

    /**
     * Ends the PHP line
     *
     * @return string
     */
    private function closeQuery()
    {
        $this->query .= ';';

        return $this->query;
    }

    /**
     * Appends code to the database query
     *
     * @param $string
     */
    private function addQuery($string)
    {
        if ($this->usesTable) {
            $prefix = is_null($this->query) ? '' : '->';
        } else {
            $prefix = (is_null($this->query) || ends_with($this->query, '::')) ? '' : '->';
        }

        $this->query .= $prefix . $string;
    }

    /**
     * In conflict cases, specifies the exact column
     *
     * @param string $key
     * @return string
     * @throws RequestSyntaxException
     */
    private function getColumn($key)
    {
        if ($this->model == 'user')
        {
            if ($key == 'email') {
                return 'profiles.email';
            }

            if ($key == 'name') {
                return 'profiles.name';
            }
        }

        return $this->validateKey($key);
    }

    /**
     * Returns true if the model is joined with another table
     *
     * @param string $model
     * @return bool
     */
    private function isJoined($model)
    {
        return in_array($model, $this->joins);
    }

    /**
     * Returns the name of the joined table
     *
     * @param string $model
     * @return null|string
     */
    private function getJoinTable($model)
    {
        if ($model == 'user') {
            return 'profiles';
        }

        return null;
    }

    /**
     * Throws an exception if the column has been set to hidden
     *
     * @param string $key
     * @return mixed
     * @throws RequestSyntaxException
     */
    protected function validateKey($key)
    {
        if (in_array($key, $this->hiddenKeys)) {
            throw new RequestSyntaxException("'$key' is protected and cannot be queried.");
        }

        return $key;
    }

    /**
     * Returns the accessor name
     *
     * @return string
     */
    public function getAccessor()
    {
        return $this->accessor;
    }

    /**
     * Returns the database query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

}