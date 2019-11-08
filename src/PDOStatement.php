<?php
namespace Gajus\Doll;

/**
 * @link https://github.com/gajus/doll for the canonical source repository
 * @license https://github.com/gajus/doll/blob/master/LICENSE BSD 3-Clause
 */
class PDOStatement extends \PDOStatement {
    private
        /**
         * @var PDO
         */
        $dbh,
        /**
         * @var string
         */
        $raw_query_string,
        /**
         * @var array
         */
        $named_parameter_markers;

    /**
     * @param PDO $dbh
     */
    final protected function __construct(PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * When the prepared statement is using named parameter markers, the query is overwritten with an
     * equivalent query using question mark parameter markers. The raw query is used for logging purposes.
     *
     * Names of the parameter markers are used to locate the assigned value when executing the statement.
     *
     * @param string $raw_query_string
     * @param array $named_parameter_markers
     *
     * @return null
     * @throws Exception\LogicException
     */
    public function setParameterMarkerNames ($raw_query_string, array $named_parameter_markers) {
        if ($this->named_parameter_markers !== null) {
            throw new Exception\LogicException('Parameter markers can be set only at the time of building the statement.');
        }

        $this->raw_query_string = $raw_query_string;
        $this->named_parameter_markers = $named_parameter_markers;
    }

    /**
     * @return $this
     * @throws Exception\RuntimeException
     */
    public function nextRowset() {
        if (!parent::nextRowset()) {
            throw new Exception\RuntimeException('Rowset is not available.');
        }

        return $this;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function execute ($parameters = []) {
        $execution_wall_time = -microtime(true);

        // Using named parameters
        if ($parameters && !array_key_exists(0, $parameters)) {
            $parameter_marker_names = array_unique(array_map(function ($pn) {
                    return $pn['name'];
            }, $this->named_parameter_markers));

            if (array_diff($parameter_marker_names, array_keys($parameters))) {
                // @todo Improve phrasing.
                throw new Exception\InvalidArgumentException('Prepared statement executed without values for all the parameter markers.');
            } else if (array_diff(array_keys($parameters), $parameter_marker_names)) {
                throw new Exception\InvalidArgumentException('Prepared statement executed with undefined parameters.');
            }

            foreach ($this->named_parameter_markers as $index => $parameter_marker) {
                $this->bindValue($index + 1, $parameters[$parameter_marker['name']], $parameter_marker['type']);
            }

            $execute = parent::execute();
        } else {
            if ($this->named_parameter_markers) {
                throw new Exception\InvalidArgumentException('Prepared statement with named parameter markers executed using list.');
            }

            $execute = parent::execute($parameters);
        }

        if ($execute === false) {
            $error = $this->errorInfo();

            if ($error[0] === 'HY093') {
                // For some odd reason PDO does no throw Exception in this case.
                // @see http://www.php.net/manual/en/pdostatement.execute.php
                throw new Exception\InvalidArgumentException('You cannot bind multiple values to a single parameter. You cannot bind more values than specified.');
            } else if ($error[0] === '00000') {
                // @see PHP PDO bug, https://gist.github.com/gajus/df145c92c19520273ffb
            } else {
                throw new Exception\RuntimeException('Oops. Something gone terribly wrong.');
            }
        }

        $this->dbh->on('execute', $this->raw_query_string, $execution_wall_time + microtime(true), $parameters);

        return $this;
    }
}
