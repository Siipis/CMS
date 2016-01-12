<?php
namespace CMS\Parser;

use CMS\Database\Request;
use CMS\Exception\RequestSyntaxException;

class RequestParser implements SubParserInterface
{
    protected $parent;

    protected $hasPagination;

    public function __construct(Parser $parent)
    {
        $this->parent = $parent;

        $this->hasPagination = false;
    }

    /**
     * Parses the "request" key in the template config
     *
     * @param $source
     * @return mixed
     * @throws RequestSyntaxException
     */
    public function parse($source)
    {
        if ($this->parent->configHas('request')) {
            $requests = $this->parent->configGet('request');

            // Compile each request line into a Request object
            $run = [];
            foreach ($requests as $line => $request) {
                try {
                    $request = new Request($this->compile($request));

                    array_push($run, $request);

                } catch (RequestSyntaxException $e) {
                    if (config('cms.requests.strict_syntax')) {
                        throw new RequestSyntaxException($e->getMessage(), $line);
                    }
                }
            }

            // Run queries only after no syntax errors are found
            foreach($run as $request) {
                $this->parent->setAttribute($request->getAccessor(), $request->query());
            }
        }

        return $source;
    }

    /**
     * Compiles the request into an array
     *
     * @param string $request
     * @return array
     * @throws RequestSyntaxException
     */
    protected function compile($request)
    {
        // Clean up request before compiling
        $request = trim(strtolower($request));

        $parts = $this->split($request);

        if (count($parts) < 2) {
            throw new RequestSyntaxException("Missing argument 'model'.");
        }

        $req = [];
        // Always compile accessor, model, scope and pagination
        $req['accessor'] = $this->compileAccessor($parts[0]);
        $req['model'] = $this->compileModel($parts[1]);
        $req['scope'] = $this->compileScope($parts[1]);
        $req['take'] = $this->compileTake($parts[0]);

        // Add key selectors if necessary
        if (isset($parts[2]) && !empty($parts[2])) {
            $req['keys'] = $this->compileKeys($parts[2]);
        } else {
            $req['keys'] = [];
        }

        // Add order declarations if necessary
        if (isset($parts[3]) && !empty($parts[3])) {
            $req['order'] = $this->compileOrderBy($parts[3]);
        } else {
            $req['order'] = [];
        }

        return $req;
    }

    /**
     * Sets the result accessor
     *
     * @param string $req
     * @return string
     * @throws RequestSyntaxException
     */
    protected function compileAccessor($req)
    {
        // Use whatever comes before the limit marker
        $r = explode('[', $req);
        $req = $r[0];

        if (is_null($req)) {
            throw new RequestSyntaxException("Accessor cannot be null.");
        }

        if (!$this->isUnique($req)) {
            throw new RequestSyntaxException("Invalid accessor name: cannot override global variable.");
        }

        if (!preg_match('/^[a-z]+/', $req)) {
            throw new RequestSyntaxException("'$req' is not a valid accessor name.");
        }

        return $req;
    }

    /**
     * Sets the name of the model
     *
     * @param string $req
     * @return string
     * @throws RequestSyntaxException
     */
    protected function compileModel($req)
    {
        // Model and scope are separated with :
        $m = explode(':', $req);
        $req = $m[0];

        if (empty($req)) {
            throw new RequestSyntaxException("Model name cannot be empty.");
        }

        if (!preg_match('/^[a-z,]+/', $req)) {
            throw new RequestSyntaxException("'$req' is not a valid model name.");
        }

        if (!$this->isRequestable($req)) {
            throw new RequestSyntaxException("Unknown model '$req'.");
        }

        return $req;
    }

    /**
     * Sets the result scope
     *
     * @param string $req
     * @return array
     * @throws RequestSyntaxException
     */
    protected function compileScope($req)
    {
        // Model and scope are separated with :
        $s = explode(':', $req);

        // Remove the model declaration
        unset($s[0]);

        // Loop through scope declarations
        $scopes = [];
        foreach ($s as $scope) {
            if (is_null($scope)) {
                throw new RequestSyntaxException("Request scope cannot be null.");
            }

            if (!$this->isValidScope($scope)) {
                throw new RequestSyntaxException("'$scope' is not a valid scope.");
            }

            $s = explode('=', $scope);

            // Scope is a one-part value, e.g. all
            if (!isset($s[1])) {
                array_push($scopes, $s[0]);
            }

            // Scope is a where clause
            if (isset($s[1])) {
                // Remove unnecessary quotes
                $s[1] = preg_replace('/["\']/', '', $s[1]);

                $scopes[$s[0]] = $s[1]; // Fetch the first replace result
            } else {
                array_push($scopes, $s[0]);
            }
        }

        // If no scope is set, fetch all entries
        if (empty($scopes)) {
            array_push($scopes, 'all');
        }

        return $scopes;
    }

    /**
     * Limits the number of results
     * and adds pagination support
     *
     * @param string $req
     * @return array
     * @throws RequestSyntaxException
     */
    protected function compileTake($req)
    {
        // Match formats [n] or [n]+
        preg_match('/(\[\d+\]\+?)/', $req, $r);

        // No declaration was found
        if (count($r) == 0) {
            preg_match('/(\[.*]\+?)/', $req, $r);

            if (!empty($r)) {
                throw new RequestSyntaxException("$r[0] is not a valid pagination call.");
            }

            // No limits are set
            return [];
        }

        // Use the first preg_match result
        $req = $r[0];

        // Limit contains a pagination declaration
        if (ends_with($req, '+')) {
            if ($this->hasPagination) {
                throw new RequestSyntaxException("This request already has a paginator.");
            }

            $this->hasPagination = true;

            $count = intval(substr($req, 1, -2)); // transform to integer

            if ($count <= 1) {
                throw new RequestSyntaxException("$count is not a valid pagination value.");
            }

            return [
                'count' => $count,
                'pagination' => true,
            ];
        }

        // Limits contains no pagination
        $count = intval(substr($req, 1, -1)); // transform to integer

        if ($count < 1) {
            throw new RequestSyntaxException("$count is not a valid pagination value.");
        }

        return [
            'count' => $count,
            'pagination' => false,
        ];
    }

    /**
     * Sets the included table columns
     *
     * @param string $req
     * @return array
     * @throws RequestSyntaxException
     */
    protected function compileKeys($req)
    {
        if (empty($req)) {
            return null;
        }

        // Ensure valid table names
        if (!preg_match('/^[a-z,]+$/', $req)) {
            throw new RequestSyntaxException("'$req' is not a valid key pattern.");
        }

        // Split names for easier parsing
        $keys = explode(',', $req);

        if (count($keys) == 0) {
            throw new RequestSyntaxException("No keys are selected.");
        }

        if (in_array(null, $keys)) {
            throw new RequestSyntaxException("'$req' is not a valid key pattern.");
        }

        return $keys;
    }

    /**
     * Sets the result order
     *
     * @param string $req
     * @return array
     * @throws RequestSyntaxException
     */
    protected function compileOrderBy($req)
    {
        // Split order definitions
        $req = explode(',', $req);

        foreach ($req as $order) {
            if (!preg_match('/^(asc|desc):[a-z]+$/', $order)) {
                throw new RequestSyntaxException("'$order' is not a valid order pattern.");
            }
        }

        return $req;
    }

    /**
     * Returns the request parts
     *
     * @param string $request
     * @return array
     */
    protected function split($request)
    {
        $request = preg_replace('/\s+/', '', $request);

        return array_map('trim', explode('|', $request));
    }

    /**
     * Returns true if the model can be queried for
     *
     * @param string $model
     * @return bool
     */
    protected function isRequestable($model)
    {
        $config = config('cms.requests.requestable');

        if (is_null($config)) {
            return false;
        }

        return in_array($model, $config);
    }

    /**
     * Returns true if the accessor isn't in global use
     *
     * @param string $accessor
     * @return bool
     */
    protected function isUnique($accessor)
    {
        $globals = \CMS::getGlobals();
        $context = \CMS::mergeShared($globals);

        return !isset($context[$accessor]);
    }

    /**
     * Returns true if the query scope is valid
     *
     * @param string $scope
     * @return bool
     */
    protected function isValidScope($scope)
    {
        $config = config('cms.requests.scopes');

        // Remove any white space interfering with recognition
        $config = preg_replace('/\s+/', '', $config);

        // Search direct scope matches
        if (in_array($scope, $config)) {
            return true;
        }

        // Search scope matches for key=? format
        $scope = preg_replace('/^([a-z]+=)(.*)$/', '${1}?', $scope);

        if (in_array($scope, $config)) {
            return true;
        }

        // Search scope matches for any key
        $scope = preg_replace('/^[a-z]+(.*)$/', '*${1}', $scope);

        if (in_array($scope, $config)) {
            return true;
        }

        return false;
    }
} 