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

use ILIAS\Data\URI;
use ILIAS\UI\Factory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorActionProvider
{
    /**
     * These constants correspond to method names in the ilMDFullEditorGUI.
     */
    public const CREATE = 'fullEditorCreate';
    public const UPDATE = 'fullEditorUpdate';
    public const DELETE = 'fullEditorDelete';

    protected ilMDFullEditorActionButtonProvider $button_provider;
    protected ilMDFullEditorActionModalProvider $modal_provider;
    protected ilMDFullEditorActionLinkProvider $link_provider;

    public function __construct(
        ilMDFullEditorActionLinkProvider $link_provider,
        ilMDFullEditorActionButtonProvider $button_provider,
        ilMDFullEditorActionModalProvider $modal_provider
    ) {
        $this->link_provider = $link_provider;
        $this->button_provider = $button_provider;
        $this->modal_provider = $modal_provider;
    }

    public function getModal(): ilMDFullEditorActionModalProvider
    {
        return $this->modal_provider;
    }

    public function getButton(): ilMDFullEditorActionButtonProvider
    {
        return $this->button_provider;
    }

    public function getLink(): ilMDFullEditorActionLinkProvider
    {
        return $this->link_provider;
    }

    public function isElementDeletable(
        ilMDRootElement $root,
        ilMDLOMEditorGUIQuirkStructure $quirk_structure,
        ilMDPathFromRoot $path_to_element
    ): bool {
        // remove all filters from the path
        $clean_path = clone $path_to_element;
        $steps = [];
        while (!$clean_path->isAtStart()) {
            array_unshift($steps, $clean_path->getStep());
            $clean_path->removeLastStep();
        }
        foreach ($steps as $step) {
            $clean_path->addStep($step);
        }

        // find the index
        $element = $root->getSubElementsByPath($path_to_element)[0];
        $index = 1 + array_search(
            $element,
            $root->getSubElementsByPath($clean_path),
            true
        );

        $indices_not_deletable = $quirk_structure
            ->movePointerToEndOfPath($path_to_element)
            ->getTagAtPointer()
            ?->getIndicesNotDeletable() ?? [];
        return !in_array($index, $indices_not_deletable);
    }
}
