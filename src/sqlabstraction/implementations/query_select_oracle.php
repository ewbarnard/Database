<?php
/**
 * File containing the ezcQueryOracle class.
 *
 * @package Database
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Oracle specific implementation of ezcQuery.
 *
 * This class reimplements methods where Oracle differs from
 * the standard implementation in ezcQuery.Most notably LIMIT
 * which is not supported directly in oracle.
 *
 * @see ezcQuery
 * @package Database
 */
class ezcQuerySelectOracle extends ezcQuerySelect
{
    /**
     * If a limit and/or offset has been set for this query.
     */
    private $hasLimit = false;
    private $limit = 0;
    private $offset = 0;

    /**
     * Constructs a new ezcQueryOracle object.
     */
    public function __construct( PDO $db )
    {
        parent::__construct( $db );
    }

    /**
     * Resets the query object for reuse.
     *
     * @return void
     */
    public function reset()
    {
        $this->hasLimit = false;
        $this->limit = 0;
        $this->offset = 0;
        parent::reset();
    }

    /**
     * Returns SQL to create an alias.
     *
     * This method can be used to create an alias for either a
     * table or a column.
     * Example:
     * <code>
     * // this will make the table users have the alias employees
     * // and the column user_id the alias employee_id
     * $q->select( $q->aliAs( 'user_id', 'employee_id' )
     *   ->from( $q->aliAs( 'users', 'employees' ) );
     * </code>
     *
     * @return string the query string "columnname as targetname
     */
    public function alias( $name, $alias )
    {
        $name = $this->getIdentifier( $name );
        return "{$name} {$alias}";
    }

    /**
     * Returns SQL that limits the result set.
     *
     * $limit controls the maximum number of rows that will be returned.
     * $offset controls which row that will be the first in the result
     * set from the total amount of matching rows.
     *
     * Example:
     * <code>
     * $q->select( '*' )->from( 'table' )
     *                  ->limit( 10, 0 );
     * </code>
     *
     * Oracle does not support the LIMIT keyword. A complete rewrite of the
     * query is neccessary. Queries will be rewritten like this:
     * <code>
     * Original query in MySQL syntax:
     * SELECT * FROM table LIMIT 10, 5
     * The corresponding Oracle query:
     * SELECT * FROM (SELECT a.*, ROWNUM rn FROM (SELECT * FROM table) a WHERE rownum <= 15) WHERE rn >= 6;
     * </code>
     * Even though the Oracle query is three times as long it performs about the same
     * as mysql on small result sets and a bit better on large result sets.
     *
     * Note that you will not get a result if you call buildLimit() when using the oracle database.
     *
     * @param $limit integer expression
     * @param $offset integer expression
     * @return string logical expression
     */
    public function limit( $limit, $offset = 0 )
    {
        $this->hasLimit = true;
        $this->limit = $limit;
        $this->offset = $offset;
    }


    /**
     * Returns dummy table name 'dual'.
     *
     * @return string
     */
    static public function getDummyTableName()
    {
        return 'dual';
    }

    /**
     * Transforms the query from the parent to provide LIMIT functionality.
     */
    public function getQuery()
    {
        $query = parent::build();
        if ( $this->hasLimit )
        {
            $min = $this->offset + 1;
            $max = $this->offset + $this->limit;
            $query = "SELECT * FROM( SELECT a.*, ROWNUM rn FROM( {$query} ) a WHERE rownum <= {$max} ) WHERE rn >= {$min}";
        }
        return $query;
    }
}

?>