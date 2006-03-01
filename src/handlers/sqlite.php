<?php
/**
 * File containing the ezcDbHandlerSqlite class.
 *
 * @package Database
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * SQLite driver implementation
 *
 * @see ezcDbHandler
 * @package Database
 * @version //autogentag//
 */
class ezcDbHandlerSqlite extends ezcDbHandler
{
    /**
     * Constructs a handler object from the parameters $dbParams.
     *
     * Supported database parameters are:
     * - dbname|database: Database name
     *
     * @throws ezcDbMissingParameterException if the database name was not specified.
     * @param array $dbparams Database connection parameters (key=>value pairs).
     */
    public function __construct( $dbParams )
    {
        $database = null;

        foreach ( $dbParams as $key => $val )
        {
            switch ( $key )
            {
                case 'database':
                case 'dbname':
                    $database = $val;
                    if ( $database[0] != '/' )
                    {
                        $database = '/' . $database;
                    }
                    break;
            }
        }

        if ( !isset( $database ) )
        {
            throw new ezcDbMissingParameterException( 'database', 'dbParams' );
        }

        $dsn = "sqlite:$database";

        parent::__construct( $dbParams, $dsn );

        /* Register PHP implementations of missing functions in SQLite */
        $this->sqliteCreateFunction( 'md5', array( 'ezcQuerySqliteFunctions', 'md5Impl'), 1 );
        $this->sqliteCreateFunction( 'mod', array( 'ezcQuerySqliteFunctions', 'modImpl'), 2 );
        $this->sqliteCreateFunction( 'concat', array( 'ezcQuerySqliteFunctions', 'concatImpl') );
        $this->sqliteCreateFunction( 'now', 'time', 0 );
    }

    /**
     * Returns 'sqlite'.
     *
     * @return string
     */
    static public function getName()
    {
        return 'sqlite';
    }

    /**
     * Returns the features supported by SQLite.
     *
     * @return array(string)
     */
    static public function hasFeature( $feature )
    {
        $supportedFeatures = array( 'multi-table-delete', 'cross-table-update' );
        return in_array( $feature, $supportedFeatures );
    }

    /**
     * Returns a new ezcQuerySelect derived object with SQLite implementation specifics.
     *
     * @return ezcQuerySelectSqlite
     */
    public function createSelectQuery()
    {
        return new ezcQuerySelectSqlite( $this );
    }

    /**
     * Returns a new ezcQueryExpression derived object with SQLite implementation specifics.
     *
     * @return ezcQueryExpressionqSqlite
     */
    public function createExpression()
    {
        return new ezcQueryExpressionSqlite();
    }

    /**
     * Returns a new ezcUtilities derived object with SQLite implementation specifics.
     *
     * @return ezcUtilitiesSqlite
     */
    public function createUtilities()
    {
        return new ezcDbUtilitiesSqlite( $this );
    }
}
?>