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
class ilMDEditorGUITagBuilder
{
    protected ?ilMDPathRelative $path_to_representation = null;
    protected ?ilMDPathRelative $path_to_preview = null;
    protected ?ilMDPathRelative $path_to_forward = null;
    protected string $collection_mode = ilMDLOMEditorGUIDictionary::NO_COLLECTION;
    protected bool $in_tree = true;
    protected bool $label_important = false;

    public function setPathToRepresentation(
        ?ilMDPathRelative $path
    ): ilMDEditorGUITagBuilder {
        $this->path_to_representation = $path;
        return $this;
    }

    public function setPathToPreview(
        ?ilMDPathRelative $path
    ): ilMDEditorGUITagBuilder {
        $this->path_to_preview = $path;
        return $this;
    }

    public function setPathToForward(
        ?ilMDPathRelative $path
    ): ilMDEditorGUITagBuilder {
        $this->path_to_forward = $path;
        return $this;
    }

    public function setCollectionMode(
        string $collection_mode
    ): ilMDEditorGUITagBuilder {
        $this->collection_mode = $collection_mode;
        return $this;
    }

    public function setInTree(bool $in_tree): ilMDEditorGUITagBuilder
    {
        $this->in_tree = $in_tree;
        return $this;
    }

    public function setLabelImportant(
        bool $label_important
    ): ilMDEditorGUITagBuilder {
        $this->label_important = $label_important;
        return $this;
    }

    public function getTag(): ilMDEditorGUITag
    {
        return new ilMDEditorGUITag(
            $this->path_to_preview,
            $this->path_to_representation,
            $this->path_to_forward,
            $this->collection_mode,
            $this->in_tree,
            $this->label_important
        );
    }
}
