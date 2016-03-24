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
     * Returns a single entry
     *
     * @param $id
     * @return mixed
     */
    abstract public function dataOne($id);

    /**
     * Returns the view accessor
     *
     * @return string
     */
    abstract public function getAccessor();

    /**
     * Attempts to call a data function
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $dataName = 'data' . ucfirst($name);

        if (method_exists($this, $dataName)) {
            return call_user_func_array([$this, $dataName], $arguments);
        }

        throw new \Exception("Fatal error: Call to undefined method [$name].");
    }
}