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

use ILIAS\UI\Component\Tree\TreeRecursion;
use ILIAS\UI\Component\Tree\Node\Factory;
use ILIAS\UI\Component\Tree\Node\Node as Node;
use ILIAS\Data\URI;
use classes\Elements\ilMDElement;
use classes\Elements\ilMDRootElement;

/**
 *  Tree Recursion, putting Entries into a Tree
 */
class ilMDEditorTreeRecursion implements TreeRecursion
{
    protected const MAX_LENGTH = 128;

    protected URI $link;
    protected ilMDRootElement $root;

    /**
     * @var ilMDElement[]
     */
    protected array $current_elements;

    protected ilMDPathFactory $path_factory;
    protected ilMDLOMPresenter $presenter;

    public function __construct(
        URI $link,
        ilMDRootElement $root,
        ilMDPathFromRoot $path_to_current_element,
        ilMDPathFactory $path_factory,
        ilMDLOMPresenter $converter
    ) {
        $this->link = $link;
        $this->root = $root;
        $els = $root->getSubElementsByPath($path_to_current_element);
        if (count($els) < 1) {
            throw new ilMDGUIException(
                'When building the MD editor tree, the path to the current' .
                ' element does not lead to an element.'
            );
        }
        $super = $els[0]->getSuperElement();
        $name = $els[0]->getName();
        foreach ($els as $el) {
            if ($el->isScaffold()) {
                throw new ilMDGUIException(
                    'When building the MD editor tree, there should not be any' .
                    ' scaffolds in the MD set.'
                );
            }
            if (
                $super !== $el->getSuperElement() ||
                $name !== $el->getName()
            ) {
                throw new ilMDGUIException(
                    'When building the MD editor tree, the path to the current' .
                    ' element does not lead to a unique (collection of) element(s).'
                );
            }
        }
        $this->current_elements = $els;

        $this->path_factory = $path_factory;
        $this->presenter = $converter;
    }

    public function getChildren($record, $environment = null): array
    {
        /**
         * @var ilMDElement|ilMDElement[] $record
         * @var ilMDLOMEditorGUIStructure $environment
         */
        if (!is_array($record)) {
            return $this->getCollectedSubElements($record, $environment);
        }
        if (empty($record)) {
            throw new ilMDGUIException(
                'Nodes must have at least one MDElement.'
            );
        }
        switch (
            $this->getTagForElement($record[0], $environment)
                 ->getCollectionMode()
        ) {
            case ilMDLOMEditorGUIDictionary::COLLECTION_NODE:
                return $record;
                break;

            case ilMDLOMEditorGUIDictionary::COLLECTION_TABLE:
                return [];
                break;

            case ilMDLOMEditorGUIDictionary::NO_COLLECTION:
            default:
                throw new ilMDGUIException(
                    'Invalid collection mode when constructing ' .
                    'MD editor tree.'
                );
        }
    }

    public function build(
        Factory $factory,
        $record,
        $environment = null
    ): Node {
        /**
         * @var ilMDElement|ilMDElement[] $record
         * @var ilMDLOMEditorGUIStructure $environment
         */
        $elements = is_array($record) ? $record : [$record];
        $tag = $this->getTagForElement($elements[0], $environment);
        $mode = $tag->getCollectionMode();

        // expanded
        $is_expanded = in_array($this->current_elements[0], $elements, true);
        foreach ($elements as $el) {
            $is_expanded =
                $is_expanded || in_array(
                    $el,
                    $this->getAllSuperElements($this->current_elements[0]),
                    true
                );
        }

        //highlighted
        $is_highlited = false;
        if (
            $mode !== ilMDLOMEditorGUIDictionary::COLLECTION_NODE ||
            !is_array($record)
        ) {
            $is_highlited = $elements === $this->current_elements;
        }

        //label and value
        $label = $this->getLabel($record, $tag);
        $value = $this->getValue($record, $tag);

        //link
        if (
            $mode !== ilMDLOMEditorGUIDictionary::COLLECTION_NODE ||
            !is_array($record)
        ) {
            $link = $this->getLink($elements[0], $mode);
        }

        $node = $factory
            ->keyValue($label, $value)
            ->withExpanded($is_expanded)
            ->withHighlighted($is_highlited);

        if (isset($link)) {
            $node = $node->withLink($link);
        }

        return $node;
    }

    protected function getLabel(
        ilMDElement|array $record,
        ilMDEditorGUITag $tag
    ): string {
        $repr_path = $tag->getPathToRepresentation();
        $preview_path = $tag->getPathToPreview();
        $elements = is_array($record) ? $record : [$record];
        $mode = $tag->getCollectionMode();

        if (
            ($mode === ilMDLOMEditorGUIDictionary::COLLECTION_NODE &&
                is_array($record)) ||
            ($mode === ilMDLOMEditorGUIDictionary::COLLECTION_TABLE &&
                $elements[0]->getSuperElement()?->isRoot())
        ) {
            $label = $this->presenter->getElementName(
                $elements[0],
                true
            );
        } else {
            $label = $this->presenter->getElementsLabel(
                $elements,
                $repr_path,
                is_array($record)
            );
        }

        return $this->presenter->shortenString($label, self::MAX_LENGTH);
    }

    protected function getValue(
        ilMDElement|array $record,
        ilMDEditorGUITag $tag
    ): string {
        $repr_path = $tag->getPathToRepresentation();
        $preview_path = $tag->getPathToPreview();
        $elements = is_array($record) ? $record : [$record];
        $mode = $tag->getCollectionMode();

        if (
            ($mode === ilMDLOMEditorGUIDictionary::COLLECTION_NODE &&
                is_array($record)) ||
            ($mode === ilMDLOMEditorGUIDictionary::COLLECTION_TABLE &&
                $elements[0]->getSuperElement()?->isRoot())
        ) {
            return '';
        }

        return $this->presenter->shortenString(
            $this->presenter->getElementsPreview($elements, $preview_path),
            self::MAX_LENGTH
        );
    }

    protected function getLink(
        ilMDElement $element,
        string $mode
    ): URI {
        $path = $this->path_factory->getPathFromRoot();
        foreach (array_slice($this->getAllSuperElements($element), 1) as $el) {
            $path
                ->addStep($el->getName())
                ->addMDIDFilter($el->getMDID());
        }
        if (!$element->isRoot()) {
            $path->addStep($element->getName());
            if ($mode !== ilMDLOMEditorGUIDictionary::COLLECTION_TABLE) {
                $path->addMDIDFilter($element->getMDID());
            }
        }
        return $this->link->withParameter(
            ilMDEditorGUI::MD_NODE_PATH,
            $path->getPathAsString()
        );
    }

    /**
     * @param ilMDElement $element
     * @return ilMDElement[]
     */
    protected function getAllSuperElements(ilMDElement $element): array
    {
        $supers = [];
        while (!$element->isRoot()) {
            $element = $element->getSuperElement();
            array_unshift($supers, $element);
        }
        return $supers;
    }

    protected function getTagForElement(
        ilMDElement $element,
        ilMDLOMEditorGUIStructure $structure
    ): ?ilMDEditorGUITag {
        $structure->movePointerToRoot();
        foreach (array_slice($this->getAllSuperElements($element), 1) as $el) {
            $structure->movePointerToSubElement($el->getName());
        }
        if (!$element->isRoot()) {
            $structure->movePointerToSubElement($element->getName());
        }
        $tag = $structure->getTagAtPointer();
        $structure->movePointerToRoot();
        return $tag;
    }

    protected function isElementUnique(
        ilMDElement $element,
        ilMDLOMEditorGUIStructure $structure
    ): bool {
        $structure->movePointerToRoot();
        foreach (array_slice($this->getAllSuperElements($element), 1) as $el) {
            $structure->movePointerToSubElement($el->getName());
        }
        $structure->movePointerToSubElement($element->getName());
        $unique = $structure->isUniqueAtPointer();
        $structure->movePointerToRoot();
        return $unique;
    }

    /**
     * @param ilMDElement               $element
     * @param ilMDLOMEditorGUIStructure $structure
     * @return array{ilMDElement|ilMDElement[]}
     */
    protected function getCollectedSubElements(
        ilMDElement $element,
        ilMDLOMEditorGUIStructure $structure
    ): array {
        $res = [];
        foreach ($element->getSubElements() as $el) {
            if (!$el instanceof ilMDElement) {
                throw new ilMDGUIException(
                    'When building the MD editor tree, there should not be any' .
                    ' scaffolds in the MD set.'
                );
            }
            $tag = $this->getTagForElement($el, $structure);
            if (!$tag?->isInTree()) {
                continue;
            }
            switch ($tag->getCollectionMode()) {
                case ilMDLOMEditorGUIDictionary::NO_COLLECTION:
                    $res[] = $el;
                    break;

                case ilMDLOMEditorGUIDictionary::COLLECTION_NODE:
                case ilMDLOMEditorGUIDictionary::COLLECTION_TABLE:
                    $res[$el->getName()][] = $el;
                    break;

                default:
                    throw new ilMDGUIException(
                        'Invalid collection mode when constructing ' .
                        'MD editor tree.'
                    );
            }
        }
        return $res;
    }
}
