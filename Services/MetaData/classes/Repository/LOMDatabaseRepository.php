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

use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Repository\Validation\CleanerInterface;
use ILIAS\MetaData\Elements\RessourceID\RessourceIDInterface;
use ILIAS\MetaData\Repository\Utilities\DatabaseManipulatorInterface;
use ILIAS\MetaData\Repository\Utilities\ScaffoldProviderInterface;
use ILIAS\MetaData\Elements\SetInterface;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Repository\Utilities\DatabaseReaderInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class LOMDatabaseRepository implements RepositoryInterface
{
    protected RessourceIDInterface $ressource_id;
    protected ScaffoldProviderInterface $scaffold_provider;
    protected DatabaseManipulatorInterface $manipulator;
    protected DatabaseReaderInterface $reader;
    protected CleanerInterface $cleaner;

    public function __construct(
        RessourceIDInterface $ressource_id,
        ScaffoldProviderInterface $scaffold_provider,
        DatabaseManipulatorInterface $manipulator,
        DatabaseReaderInterface $reader,
        CleanerInterface $cleaner
    ) {
        $this->ressource_id = $ressource_id;
        $this->scaffold_provider = $scaffold_provider;
        $this->manipulator = $manipulator;
        $this->reader = $reader;
        $this->cleaner = $cleaner;
    }

    public function getRessourceID(): RessourceIDInterface
    {
        return $this->ressource_id;
    }

    public function getMD(): SetInterface
    {
        return $this->cleaner->clean(
            $this->reader->getMD($this->ressource_id)
        );
    }

    public function getMDOnPath(PathInterface $path): SetInterface
    {
        return $this->cleaner->clean(
            $this->reader->getMDOnPath($path, $this->ressource_id)
        );
    }

    /**
     * @return ElementInterface[]
     */
    public function getScaffoldsForElement(
        ElementInterface $element
    ): \Generator {
        yield from $this->scaffold_provider->getScaffoldsForElement($element);
    }

    public function manipulateMD(SetInterface $set): void
    {
        $this->cleaner->checkMarkers($set);
        $this->manipulator->manipulateMD($set);
    }

    public function deleteAllMD(): void
    {
        $this->manipulator->deleteAllMD($this->ressource_id);
    }
}
