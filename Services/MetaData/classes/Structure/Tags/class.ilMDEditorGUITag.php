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

use ILIAS\UI\Component\Component;
use ILIAS\UI\Factory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDEditorGUITag extends ilMDTag
{
    protected ?ilMDPathRelative $path_to_representation;
    protected ?ilMDPathRelative $path_to_preview;
    protected string $collection_mode = ilMDLOMEditorGUIDictionary::NO_COLLECTION;

    public function __construct(
        ?ilMDPathRelative $path_to_preview = null,
        ?ilMDPathRelative $path_to_representation = null
    ) {
        $this->path_to_preview = $path_to_preview;
        $this->path_to_representation = $path_to_representation;
    }

    public function getPathToRepresentation(): ?ilMDPathRelative
    {
        return $this->path_to_representation;
    }

    public function getPathToPreview(): ?ilMDPathRelative
    {
        return $this->path_to_preview;
    }

    public function withCollectionMode(
        string $collection_mode
    ): ilMDEditorGUITag {
        $this->collection_mode = $collection_mode;
        return $this;
    }

    public function getCollectionMode(): string
    {
        return $this->collection_mode;
    }
}
