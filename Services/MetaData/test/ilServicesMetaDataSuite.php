<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

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
        include_once("./Services/MetaData/test/ilMDPathTest.php");
        $suite->addTestSuite(ilMDPathTest::class);

        return $suite;
    }
}
