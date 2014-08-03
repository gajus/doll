<?php
namespace Gajus\Doll;

/**
 * @link https://github.com/gajus/doll for the canonical source repository
 * @license https://github.com/gajus/doll/blob/master/LICENSE BSD 3-Clause
 */
class PDOStatement extends \PDOStatement
{
    private
        /**
         * @var PDO
         */
        $dbh,
        /**
         * @var string
         */
        $original_query_string,
        /**
         * @var array
         */
        $placeholders;

    /**
     * @param PDO $dbh
     */
    final protected function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * @param  string $original_query_string Original query might use named placeholders. Inherited statement will always use question-mark placeholders.
     * @param  array  $placeholders
     * @return null
     */
    public function setOriginalQueryPlaceholders($original_query_string, array $placeholders)
    {
        if ($this->placeholders !== null) {
            throw new Exception\LogicException('Placeholders can be set only at the time of building the statement.');
        }

        $this->original_query_string = $original_query_string;
        $this->placeholders = $placeholders;
    }

    /**
     * @return $this
     */
    public function nextRowset()
    {
        if (!parent::nextRowset()) {
            throw new Exception\RuntimeException('Rowset is not available.');
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function execute($parameters = [])
    {
        $execution_wall_time = -microtime(true);

        // Using named parameters
        if ($parameters && !array_key_exists(0, $parameters)) {
            $placeholder_names = array_unique(array_map(function ($pn) {
                    return $pn['name'];
            }, $this->placeholders));

            if (array_diff($placeholder_names, array_keys($parameters))) {
                // @todo Improve phrasing.
                throw new Exception\InvalidArgumentException('Prepared statement executed without values for all the placeholders.');
            } elseif (array_diff(array_keys($parameters), $placeholder_names)) {
                throw new Exception\InvalidArgumentException('Prepared statement executed with undefined parameters.');
            }

            #die(var_dump( $this->placeholders, $parameters, $this ));

            foreach ($this->placeholders as $index => $placeholder) {
                $this->bindValue($index + 1, $parameters[$placeholder['name']], $placeholder['type']);
            }

            $execute = parent::execute();
        } else {
            if ($this->placeholders) {
                throw new Exception\InvalidArgumentException('Prepared statement with named placeholders executed using list.');
            }

            $execute = parent::execute($parameters);
        }

        if ($execute === false) {
            $error = $this->errorInfo();

            if ($error[0] === 'HY093') {
                // For some odd reason PDO does no throw Exception in this case.
                // @see http://www.php.net/manual/en/pdostatement.execute.php
                throw new Exception\InvalidArgumentException('You cannot bind multiple values to a single parameter. You cannot bind more values than specified.');
            } elseif ($error[0] === '00000') {
                // @see PHP PDO bug, https://gist.github.com/gajus/df145c92c19520273ffb
            } else {
                throw new Exception\RuntimeException('Oops. Something gone terribly wrong.');
            }
        }

        $this->dbh->on('execute', $this->original_query_string, $execution_wall_time + microtime(true), $parameters);

        return $this;
    }
}
