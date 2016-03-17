<?php


namespace CMS\Data;


abstract class DataProvider
{
    protected $access;

    /**
     * Returns all data
     *
     * @return mixed
     */
    abstract public function dataAll();

    /**
     * Returns the view accessor
     *
     * @return string
     */
    abstract public function getAccessor();

    /*
    |--------------------------------------------------------------------------
    | Access control
    |--------------------------------------------------------------------------
    |
    | Restricts the data to users who have permission to view it
    |
    */

    public function __call($name, $arguments)
    {
        $dataName = 'data' . ucfirst($name);

        if (method_exists($this, $dataName)) {
            if (!$this->can($dataName)) {
                if (\Auth::check() || \Request::ajax()) {
                    abort(401);
                }

                redirect('/login');
            }

            return call_user_func([$this, $dataName], $arguments);
        }

        throw new \Exception("Fatal error: Call to undefined method [$name].");
    }

    /**
     * Set access limitations.
     *
     * @param  string $permission
     * @param  array $options
     * @return void
     */
    protected function access($permission, array $options = [])
    {
        $this->access[$permission] = $options;
    }

    /**
     * Returns the access settings
     *
     * @return mixed
     */
    public function getAccess()
    {
        return $this->access;
    }

    private function can($method)
    {
        if (!empty($this->access)) {
            foreach ($this->access as $required => $options) {
                if (!\Access::can($required)) {
                    if (isset($options['except'])) {
                        if (in_array($method, $options['except'])) {
                            continue;
                        }
                    } else if (isset($options['only'])) {
                        if (!in_array($method, $options['only'])) {
                            continue;
                        }
                    }

                    return false;
                }
            }
        }

        return true;
    }
}