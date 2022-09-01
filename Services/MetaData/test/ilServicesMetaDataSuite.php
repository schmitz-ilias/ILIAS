<?php

declare(strict_types=1);
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use PHPUnit\Framework\TestSuite;

class ilServicesMetaDataSuite extends TestSuite
{
    public static function suite(): self
    {
        $suite = new ilServicesMetaDataSuite();

        include_once("./Services/MetaData/test/ilMDBuildingBlocksTest.php");
        $suite->addTestSuite(ilMDBuildingBlocksTest::class);
        include_once("./Services/MetaData/test/ilMDLOMDataFactoryTest.php");
        $suite->addTestSuite(ilMDLOMDataFactoryTest::class);
        include_once("./Services/MetaData/test/ilMDLOMStructureTest.php");
        $suite->addTestSuite(ilMDLOMStructureTest::class);

        return $suite;
    }
}
