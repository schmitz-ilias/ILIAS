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

    protected Factory $factory;
    protected ilMDLOMPresenter $presenter;
    protected ilMDFullEditorPropertiesProvider $prop_provider;

    protected URI $base_link;

    public function __construct(
        URI $base_link,
        Factory $factory,
        ilMDLOMPresenter $presenter,
        ilMDFullEditorPropertiesProvider $prop_provider
    ) {
        $this->base_link = $base_link;
        $this->factory = $factory;
        $this->presenter = $presenter;
        $this->prop_provider = $prop_provider;
    }

    public function getModal(): ilMDFullEditorActionModalProvider
    {
        return new ilMDFullEditorActionModalProvider(
            $this,
            $this->factory,
            $this->presenter,
            $this->prop_provider
        );
    }

    public function getButton(): ilMDFullEditorActionButtonProvider
    {
        return new ilMDFullEditorActionButtonProvider(
            $this->factory,
            $this->presenter
        );
    }

    public function getActionLink(
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $action_path,
        string $action_cmd
    ): URI {
        $actions = [self::CREATE, self::DELETE, self::UPDATE];
        if (!in_array($action_cmd, $actions)) {
            throw new ilMDGUIException(
                'Invalid action: ' . $action_cmd
            );
        }

        return $this->base_link
            ->withParameter(
                ilMDEditorGUI::MD_NODE_PATH,
                $base_path->getPathAsString()
            )
            ->withParameter(
                ilMDEditorGUI::MD_ACTION_PATH,
                $action_path->getPathAsString()
            )
            ->withParameter(
                'cmd',
                $action_cmd
            );
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
            $root->getSubElementsByPath($clean_path)
        );

        $indices_not_deletable = $quirk_structure
            ->movePointerToEndOfPath($path_to_element)
            ->getTagAtPointer()
            ?->getIndicesNotDeletable() ?? [];
        return !in_array($index, $indices_not_deletable);
    }
}
