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

namespace ILIAS\MetaData\Repository;

use ILIAS\MetaData\Elements\RessourceID\RessourceIDFactoryInterface;
use ILIAS\MetaData\Repository\Utilities\ScaffoldProviderInterface;
use ILIAS\MetaData\Repository\Utilities\DatabaseManipulatorInterface;
use ILIAS\MetaData\Repository\Utilities\DatabaseReaderInterface;
use ILIAS\MetaData\Repository\Validation\CleanerInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class RepositoryProvider implements RepositoryProviderInterface
{
    protected RessourceIDFactoryInterface $ressource_factory;
    protected ScaffoldProviderInterface $scaffold_provider;
    protected DatabaseManipulatorInterface $manipulator;
    protected DatabaseReaderInterface $reader;
    protected CleanerInterface $cleaner;

    public function __construct(
        RessourceIDFactoryInterface $ressource_factory,
        ScaffoldProviderInterface $scaffold_provider,
        DatabaseManipulatorInterface $manipulator,
        DatabaseReaderInterface $reader,
        CleanerInterface $cleaner
    ) {
        $this->ressource_factory = $ressource_factory;
        $this->scaffold_provider = $scaffold_provider;
        $this->manipulator = $manipulator;
        $this->reader = $reader;
        $this->cleaner = $cleaner;
    }

    public function get(
        int $obj_id,
        int $sub_id,
        string $type
    ): RepositoryInterface {
        return new LOMDatabaseRepository(
            $this->ressource_factory->ressourceID($obj_id, $sub_id, $type),
            $this->scaffold_provider,
            $this->manipulator,
            $this->reader,
            $this->cleaner
        );
    }
}
