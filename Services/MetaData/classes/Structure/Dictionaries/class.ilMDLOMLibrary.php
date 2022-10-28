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

    public function getLOMDictionary(): ilMDLOMDictionary
    {
        return new ilMDLOMDictionary();
    }

    public function getLOMDatabaseDictionary(
        ilDBInterface $db
    ): ilMDLOMDatabaseDictionary {
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

    public function getLOMEditorGUIDictionary(
        ilMDPathFactory $path_factory
    ): ilMDLOMEditorGUIDictionary {
        return new ilMDLOMEditorGUIDictionary(
            $this->factory,
            $path_factory
        );
    }

    public function getLOMEditorGUIQuirkDictionary(): ilMDLOMEditorGUIQuirkDictionary
    {
        return new ilMDLOMEditorGUIQuirkDictionary($this->factory);
    }
}
