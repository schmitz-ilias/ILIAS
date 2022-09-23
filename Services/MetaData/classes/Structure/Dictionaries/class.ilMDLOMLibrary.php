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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMLibrary
{
    protected ilMDTagFactory $factory;

    protected ilMDLOMStructure $structure;

    public function __construct(ilMDTagFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Returns a LOM structure in read mode and the pointer at root,
     * without any tags.
     */
    public function getLOMStructure(): ilMDLOMStructure
    {
        if (!isset($this->structure)) {
            $this->structure = new ilMDLOMStructure();
            $this->structure
                ->movePointerToRoot()
                ->switchToReadMode();
        }
        return clone $this->structure;
    }

    public function getLOMDatabaseDictionary(ilDBInterface $db): ilMDLOMDatabaseDictionary
    {
        return new ilMDLOMDatabaseDictionary($this->factory, $db);
    }

    public function getLOMVocabulariesDictionary(
        ilMDPathFactory $path_factory
    ): ilMDLOMVocabulariesDictionary {
        return new ilMDLOMVocabulariesDictionary(
            $this->factory,
            $path_factory
        );
    }
}
