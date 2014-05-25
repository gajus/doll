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
        $placeholders,
        /**
         * @var array
         */
        $placeholder_param_types;
    
    /**
     * @param PDO $dbh
     */
    final protected function __construct(PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * @return null
     */
    public function setPlaceholders (array $placeholders, array $placeholder_param_types) {
        if ($this->placeholders !== null) {
            throw new Exception\LogicException('Placeholders can be set only at the time of building the statement.');
        }

        $this->placeholders = $placeholders;
        $this->placeholder_param_types = $placeholder_param_types;
    }
    
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
        $execute = parent::execute($parameters);

        if ($execute === false) {
            $error = $this->errorInfo();

            if ($error[0] === 'HY093') {
                // For some odd reason PDO does no throw Exception in this case.
                // @see http://www.php.net/manual/en/pdostatement.execute.php
                throw new Exception\InvalidArgumentException('You cannot bind multiple values to a single parameter. You cannot bind more values than specified.');
            } else {
                throw new Exception\RuntimeException('Oops. Something gone terribly wrong.');
            }
        }

        $this->dbh->on('execute', $this->queryString, $parameters);

        return $this;
    }
}
