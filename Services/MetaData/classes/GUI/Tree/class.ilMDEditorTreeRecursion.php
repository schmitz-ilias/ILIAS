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

/**
 *  Tree Recursion, putting Entries into a Tree
 */
class ilMDEditorTreeRecursion implements TreeRecursion
{
    protected URI $link;
    protected ilMDRootElement $root;

    /**
     * @var ilMDElement[]
     */
    protected array $current_elements;

    protected ilLanguage $lng;
    protected ilMDPathFactory $path_factory;

    public function __construct(
        URI $link,
        ilMDRootElement $root,
        ilMDPathFromRoot $path_to_current_element,
        ilLanguage $lng,
        ilMDPathFactory $path_factory
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
        $this->lng = $lng;
        $this->lng->loadLanguageModule('meta');
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

        // expanded
        $is_expanded = $elements[0]->isRoot();
        foreach ($elements as $el) {
            $is_expanded =
                $is_expanded || in_array(
                    $el,
                    $this->getAllSuperElements($this->current_elements[0])
                );
        }

        //highlighted
        $is_highlited = $elements === $this->current_elements;

        //label and value
        if (is_array($record)) {
            $label = $this->lng->txt(
                'meta_' . strtolower($record[0]->getName()) . '_plural'
            );
        } else {
            $label = $this->lng->txt(
                'meta_' . strtolower($record->getName())
            );
        }

        //link
        $mode = $tag->getCollectionMode();
        if (
            $mode !== ilMDLOMEditorGUIDictionary::COLLECTION_NODE ||
            !is_array($record)
        ) {
            $link = $this->getLink($elements[0], $mode);
        }

        $node = $factory
            ->simple($label)
            ->withExpanded($is_expanded)
            ->withHighlighted($is_highlited);

        if (isset($link)) {
            $node = $node->withLink($link);
        }

        return $node;
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
            if ($mode === ilMDLOMEditorGUIDictionary::NO_COLLECTION) {
                $path->addMDIDFilter($element->getMDID());
            }
        }
        return $this->link->withParameter(
            'node_path',
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
            if (!isset($tag)) {
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
