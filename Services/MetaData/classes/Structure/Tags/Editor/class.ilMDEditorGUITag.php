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
class ilMDEditorGUITag extends ilMDTag
{
    protected ?ilMDPathRelative $path_to_representation;
    protected ?ilMDPathRelative $path_to_preview;
    protected ?ilMDPathRelative $path_to_forward;
    protected string $collection_mode;
    protected bool $in_tree;
    protected bool $label_important;

    public function __construct(
        ?ilMDPathRelative $path_to_preview,
        ?ilMDPathRelative $path_to_representation,
        ?ilMDPathRelative $path_to_forward,
        string $collection_mode,
        bool $in_tree,
        bool $label_important
    ) {
        $this->path_to_preview = $path_to_preview;
        $this->path_to_representation = $path_to_representation;
        $this->path_to_forward = $path_to_forward;
        $this->collection_mode = $collection_mode;
        $this->in_tree = $in_tree;
        $this->label_important = $label_important;
    }

    public function getPathToRepresentation(): ?ilMDPathRelative
    {
        return $this->path_to_representation;
    }

    public function getPathToPreview(): ?ilMDPathRelative
    {
        return $this->path_to_preview;
    }

    public function getPathToForward(): ?ilMDPathRelative
    {
        return $this->path_to_forward;
    }

    public function getCollectionMode(): string
    {
        return $this->collection_mode;
    }

    public function isInTree(): bool
    {
        return $this->in_tree;
    }

    public function isLabelImportant(): bool
    {
        return $this->label_important;
    }
}
