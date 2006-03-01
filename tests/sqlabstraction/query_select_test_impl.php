<?php
/**
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Database
 * @subpackage Tests
 */

/**
 * Testing the SQL abstraction layer.
 * These tests are performed on a real database and tests that
 * the implementations return the correct result.
 *
 * @package Database
 * @subpackage Tests
 * @todo, test with null input values
 */
class ezcQuerySelectTestImpl extends ezcTestCase
{
    private $q;
    private $e;
    private $db;
    public function setUp()
    {
        $this->db = ezcDbInstance::get();
        $this->q = $this->db->createSelectQuery();
        $this->e = $this->q->expr;
        $this->assertNotNull( $this->db, 'Database instance is not initialized.' );

        try 
        {
            $this->db->exec( 'DROP TABLE query_test' );
        }
        catch ( Exception $e ) {} // eat

        // insert some data
        $this->db->exec( 'CREATE TABLE query_test ( id int, company VARCHAR(255), section VARCHAR(255), employees int )' );
        $this->db->exec( "INSERT INTO query_test VALUES ( 1, 'eZ systems', 'Norway', 20 )" );
        $this->db->exec( "INSERT INTO query_test VALUES ( 2, 'IBM', 'Norway', 500 )" );
        $this->db->exec( "INSERT INTO query_test VALUES ( 3, 'eZ systems', 'Ukraine', 10 )" );
        $this->db->exec( "INSERT INTO query_test VALUES ( 4, 'IBM', 'Germany', null )" );
    }

    public function tearDown()
    {
        $this->db->exec( 'DROP TABLE query_test' );
    }

    public function testBindString()
    {
        $section = 'Norway';
        $this->q->select( 'COUNT(*)' )->from( 'query_test' )
                ->where(
                $this->e->eq( 'section', $this->q->bindParam( $section ) ) );
        $stmt = $this->q->prepare();
        $stmt->execute();
        $this->assertEquals( 2, (int)$stmt->fetchColumn( 0 ) );

        // set another value for section and try again.
        $section = 'Ukraine';
        $stmt->execute();
        $this->assertEquals( 1, (int)$stmt->fetchColumn( 0 ) );
    }

    public function testBindInteger()
    {
        $num = 0;
        $this->q->select( 'COUNT(*)' )->from( 'query_test' )
                ->where(
                $this->e->gt( 'employees', $this->q->bindParam( $num ) ) );
        $stmt = $this->q->prepare();
        $stmt->execute();
        $this->assertEquals( 3, (int)$stmt->fetchColumn( 0 ) );

        // set another value for section and try again.
        $num = 20;
        $stmt->execute();
        $this->assertEquals( 1, (int)$stmt->fetchColumn( 0 ) );
    }

    public function testBuildFrom()
    {
        $this->q->select( 'COUNT(*)' )->from( 'query_test' );
        $stmt = $this->db->query( $this->q->getQuery() );
        $this->assertEquals( 4, (int)$stmt->fetchColumn( 0 ) );
    }

    public function testBuildFromWhere()
    {
        $this->q->select( 'COUNT(*)' )->from( 'query_test' )
                ->where( $this->e->eq( 'employees', 20 ) );
        $stmt = $this->db->query( $this->q->getQuery() );
        $this->assertEquals( 1, (int)$stmt->fetchColumn( 0 ) );
    }

    public function testBuildFromWhereGroup()
    {
        $this->q->select( 'COUNT(*)' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->groupBy( 'Company' );
        $stmt = $this->db->query( $this->q->getQuery() );
        $this->assertEquals( 2, (int)$stmt->fetchColumn( 0 ) );
    }

    public function testBuildFromWhereGroupOrder()
    {
        $this->q->select( 'company', 'SUM(employees)' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->groupBy( 'company' )
                ->orderBy( 'company', ezcQuerySelect::DESC );
        $stmt = $this->db->query( $this->q->getQuery() );
        $rows = 0;
        foreach ( $stmt as $row )
        {
            $rows++;
        }
        $this->assertEquals( 2, $rows );
    }

    public function testBuildFromWhereGroupOrderLimit()
    {
        $this->q->select( 'company', 'SUM(employees)' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->groupBy( 'company' )
                ->orderBy( 'company', ezcQuerySelect::DESC )
                ->limit( 1 );
        $stmt = $this->db->query( $this->q->getQuery() );
        $rows = 0;
        foreach ( $stmt as $row )
        {
            $rows++;
        }
        $this->assertEquals( 1, $rows );
    }

    public function testBuildFromWhereOrderLimit()
    {
        $this->q->select( '*' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->orderBy( 'id', ezcQuerySelect::DESC )
                ->limit( 1 );
        $stmt = $this->db->query( $this->q->getQuery() );
        $rows = 0;
        foreach ( $stmt as $row )
        {
            $rows++;
        }
        $this->assertEquals( 1, $rows );
    }

    public function testBuildFromWhereGroupLimit()
    {
        $this->q->select( 'company', 'SUM(employees)' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->groupBy( 'company' )
                ->limit( 1 );
        $stmt = $this->db->query( $this->q->getQuery() );
        $rows = 0;
        foreach ( $stmt as $row )
        {
            $rows++;
        }
        $this->assertEquals( 1, $rows );
    }

    public function testBuildFromLimit()
    {
        $this->q->select( '*' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->limit( 1 );
        $stmt = $this->db->query( $this->q->getQuery() );
        $rows = 0;
        foreach ( $stmt as $row )
        {
            $rows++;
        }
        $this->assertEquals( 1, $rows );
    }

    // LOGIC TESTS
    public function testSelectNone()
    {
        try
        {
            $this->q->select( );
            $this->fail( "Expected exception" );
        }
        catch ( ezcQueryVariableParameterException $e ) {}
    }

    public function testSelectMulti()
    {
        $this->q->select( 'id', 'company' )->from( 'query_test' );
        $stmt = $this->db->query( $this->q->getQuery() );
        $this->assertEquals( 2, $stmt->columnCount() );
    }

    public function testSelectMultiWithAlias()
    {
        $this->q->setAliases( array( 'identifier' => 'id', 'text' => 'company' ) );
        $this->q->select( 'identifier', 'text' )->from( 'query_test' );
        $stmt = $this->db->query( $this->q->getQuery() );
        $this->assertEquals( 2, $stmt->columnCount() );
    }


    public function testAliAs()
    {
        $this->q->select( $this->q->aliAs( 'id', 'other' ) )->from( 'query_test' );
        $stmt = $this->db->query( $this->q->getQuery() );
        $meta = $stmt->getColumnMeta( 0 );
        $this->assertEquals( 'other', $meta['name'] );
    }

    public function testAliAsWithAlias()
    {
        $this->q->setAliases( array( 'identifier' => 'id', 'text' => 'company' ) );
        $this->q->select( $this->q->aliAs( 'identifier', 'other' ) )->from( 'query_test' );
        $stmt = $this->db->query( $this->q->getQuery() );
        $meta = $stmt->getColumnMeta( 0 );
        $this->assertEquals( 'other', $meta['name'] );
    }


    public function testWhereMulti()
    {
        $this->q->select( '*' )->from( 'query_test' )
            ->where( $this->e->eq( 1, 1 ), $this->e->eq( 1, 0 ) );
        $stmt = $this->db->query( $this->q->getQuery() );
        $rows = 0;
        foreach ( $stmt as $row )
        {
            $rows++;
        }
        $this->assertEquals( 0, $rows );
    }

    public function testMultipleSelect()
    {
        try
        {
            $this->q->select( '*' )->select( '*' );
        }
        catch ( ezcQueryException $e )
        {
            return;
        }
        $this->fail( "Two calls to select() did not fail" );
    }

    public function testEmptyFrom()
    {
        try
        {
            $this->q->select( 'd' )->from();
            $this->fail( "Expected exception" );
        }
        catch ( ezcQueryVariableParameterException $e ) {}
    }

    public function testMultipleFrom()
    {
        try
        {
            $this->q->from( 'id' )->from( 'id' );
        }
        catch ( ezcQueryException $e )
        {
            return;
        }
        $this->fail( "Two calls to from() did not fail" );
    }

    public function testEmptyWhere()
    {
        try
        {
            $this->q->select( 'd' )->from('d')->where();
            $this->fail( "Expected exception" );
        }
        catch ( ezcQueryVariableParameterException $e ) {}
    }

    public function testMultipleWhere()
    {
        try
        {
            $this->q->where( 'id' )->where( 'id' );
        }
        catch ( ezcQueryException $e )
        {
            return;
        }
        $this->fail( "Two calls to where() did not fail" );
    }

    public function testEmptyGroupBy()
    {
        try
        {
            $this->q->select( 'd' )->from('d')->groupBy();
            $this->fail( "Expected exception" );
        }
        catch ( ezcQueryVariableParameterException $e ) {}
    }

    public function testReset()
    {
        $this->q->select( 'company', 'SUM(employees)' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->groupBy( 'company' )
                ->orderBy( 'company', ezcQuerySelect::DESC )
                ->limit( 1 );
        $queryString = $this->q->getQuery();
        $this->q->reset();

        $this->q->select( 'company', 'SUM(employees)' )->from( 'query_test' )
                ->where( $this->e->eq( 1, 1 ) )
                ->groupBy( 'company' )
                ->orderBy( 'company', ezcQuerySelect::DESC )
                ->limit( 1 );
        $this->assertEquals( $queryString, $this->q->getQuery() );
    }

    public static function suite()
    {
        return new ezcTestSuite( 'ezcQuerySelectTestImpl' );
    }
}
?>