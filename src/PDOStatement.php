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
         * @var array
         */
        $placeholders;
    
    /**
     * @param PDO $dbh
     */
    final protected function __construct(PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * @return null
     */
    public function setPlaceholders (array $placeholders) {
        if ($this->placeholders !== null) {
            throw new Exception\LogicException('Placeholders can be set only at the time of building the statement.');
        }

        $this->placeholders = $placeholders;
    }

    #public function get
    
    /**
     * @return $this
     */
    public function nextRowset() {
         if (!parent::nextRowset()) {
            throw new Exception\RuntimeException('Rowset is not available.');
        }
        
        return $this;
    }

    /**
     * @return $this
     */
    public function execute ($parameters = []) {
        // Using named parameters
        if ($parameters && !array_key_exists(0, $parameters)) {
            if (array_diff(array_unique(array_column($this->placeholders, 'name')), array_keys($parameters))) {
                // @todo Improve phrasing.
                throw new Exception\InvalidArgumentException('Prepared statement executed without values for all the placeholders.');
            } else if (array_diff(array_keys($parameters), array_unique(array_column($this->placeholders, 'name')))) {
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
            } else if ($error[0] === '00000') {
                // @see PHP PDO bug, https://gist.github.com/gajus/df145c92c19520273ffb
            } else {
                throw new Exception\RuntimeException('Oops. Something gone terribly wrong.');
            }
        }

        $this->dbh->on('execute', $this->queryString, $parameters);

        return $this;
    }
}
