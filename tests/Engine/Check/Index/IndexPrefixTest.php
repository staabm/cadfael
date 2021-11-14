<?php

namespace Cadfael\Tests\Engine\Check\Index;

use Cadfael\Engine\Check\Index\IndexPrefix;
use Cadfael\Engine\Entity\Column;
use Cadfael\Engine\Entity\Index;
use Cadfael\Engine\Entity\Table;
use Cadfael\Engine\Report;
use Cadfael\Tests\Engine\Check\BaseTest;
use Cadfael\Tests\Engine\Check\ColumnBuilder;
use Cadfael\Tests\Engine\Check\IndexBuilder;

class IndexPrefixTest extends BaseTest
{
    protected Column $highCardinalityColumn;
    protected Column $lowCardinalityColumn;

    protected Index $nonStringIndex;
    protected Index $smallStringIndex;
    protected Index $emptyCardinalityIndex;
    protected Index $highCardinalityIndex;
    protected Index $lowCardinalityIndex;
    protected Index $uniqueIndex;


    public function setUp(): void
    {
        $table = $this->createTable([ 'TABLE_ROWS' => 500_000 ]);

        $builder = new IndexBuilder();

        $this->nonStringIndex = $builder->name('non_string_index')
            ->generate();
        $this->nonStringIndex->setTable($table);

        $this->smallStringIndex = $builder->name('small_string_index')
            ->setColumn((new ColumnBuilder())->varchar(10)->generate())
            ->generate();
        $this->smallStringIndex->setTable($table);

        $this->emptyCardinalityIndex = $builder->name('empty_cardinality_index')
            ->setColumn((new ColumnBuilder())->varchar(120)->generate())
            ->generate();
        $this->emptyCardinalityIndex->setTable($table);
        $this->emptyCardinalityIndex->getColumns()[0]->setTable($table);
        $this->emptyCardinalityIndex->getColumns()[0]->setCardinality(00);

        $this->highCardinalityIndex = $builder->name('high_cardinality_index')
            ->setColumn((new ColumnBuilder())->varchar(120)->generate())
            ->generate();
        $this->highCardinalityIndex->setTable($table);
        $this->highCardinalityIndex->getColumns()[0]->setTable($table);
        $this->highCardinalityIndex->getColumns()[0]->setCardinality(100_000);

        $this->lowCardinalityIndex = $builder->name('low_cardinality_index')
            ->setColumn((new ColumnBuilder())->varchar(120)->generate())
            ->generate();
        $this->lowCardinalityIndex->setTable($table);
        $this->lowCardinalityIndex->getColumns()[0]->setTable($table);
        $this->lowCardinalityIndex->getColumns()[0]->setCardinality(10);

        $this->uniqueIndex = $builder->name('unique_index')->isUnique(true)
            ->setColumn((new ColumnBuilder())->varchar(120)->generate())
            ->generate();
        $this->uniqueIndex->setTable($table);
        $this->uniqueIndex->getColumns()[0]->setTable($table);
        $this->uniqueIndex->getColumns()[0]->setCardinality(100_000);
    }

    public function testSupports()
    {
        $check = new IndexPrefix();
        $this->assertTrue($check->supports($this->highCardinalityIndex), "Ensure that the supports for a column returns true.");
        $this->assertTrue($check->supports($this->lowCardinalityIndex), "Ensure that the supports for a column returns true.");
        $this->assertTrue($check->supports($this->uniqueIndex), "Ensure that the supports for a column returns true.");
    }

    public function testRun()
    {
        $check = new IndexPrefix();
        $this->assertEquals(
            Report::STATUS_OK,
            $check->run($this->nonStringIndex)->getStatus(),
            "Ensure that an OK report is returned for $this->nonStringIndex."
        );

        $this->assertEquals(
            Report::STATUS_OK,
            $check->run($this->smallStringIndex)->getStatus(),
            "Ensure that an OK report is returned for $this->smallStringIndex."
        );

        $this->assertEquals(
            Report::STATUS_OK,
            $check->run($this->emptyCardinalityIndex)->getStatus(),
            "Ensure that an OK report is returned for $this->emptyCardinalityIndex."
        );

        $this->assertEquals(
            Report::STATUS_CONCERN,
            $check->run($this->highCardinalityIndex)->getStatus(),
            "Ensure that an CONCERN report is returned for $this->highCardinalityIndex."
        );

        // Changing the column to have a prefix will let it pass the test
        $this->highCardinalityIndex->getStatistics()[0]->sub_part = 14;
        $this->assertEquals(
            Report::STATUS_OK,
            $check->run($this->highCardinalityIndex)->getStatus(),
            "Ensure that an OK report is returned for $this->highCardinalityIndex with a prefix."
        );

        $this->assertEquals(
            Report::STATUS_OK,
            $check->run($this->lowCardinalityIndex)->getStatus(),
            "Ensure that an OK report is returned for $this->lowCardinalityIndex."
        );

        $this->assertEquals(
            Report::STATUS_OK,
            $check->run($this->uniqueIndex)->getStatus(),
            "Ensure that an OK report is returned for $this->uniqueIndex with any table."
        );


    }
}
