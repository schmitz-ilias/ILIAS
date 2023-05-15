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

use classes\Elements\ilMDBaseElement;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorPropertiesProvider
{
    protected ilMDLOMEditorGUIDictionary $dict;
    protected ilMDLOMPresenter $presenter;
    protected ilMDFullEditorDataFinder $data_finder;

    public function __construct(
        ilMDLOMEditorGUIDictionary $dict,
        ilMDLOMPresenter $presenter,
        ilMDFullEditorDataFinder $data_finder
    ) {
        $this->dict = $dict;
        $this->presenter = $presenter;
        $this->data_finder = $data_finder;
    }

    /**
     * @param ilMDBaseElement[] $elements
     * @return string[]
     */
    public function getPropertiesByPreview(
        array $elements
    ): array {
        $struct = $this->getStructureWithPointerAtElement($elements[0]);

        $sub_els = [];
        foreach ($elements as $element) {
            foreach ($element->getSubElements() as $sub_el) {
                if ($sub_el->isScaffold()) {
                    continue;
                }
                $struct->movePointerToSubElement($sub_el->getName());
                if (!($tag = $struct->getTagAtPointer())) {
                    $struct->movePointerToSuperElement();
                    continue;
                };
                $mode = $tag->getCollectionMode();
                $label = $this->presenter->getElementsLabel(
                    [$sub_el],
                    $tag->getPathToRepresentation(),
                    !$struct->isUniqueAtPointer()
                );
                $res = [
                    [$sub_el],
                    $label,
                    $tag->getPathToPreview()
                ];
                $struct->movePointerToSuperElement();
                if (!isset($sub_els[$label])) {
                    $sub_els[$label] = $res;
                    continue;
                }
                $sub_els[$label][0][] = $sub_el;
            }
        }
        $properties = [];
        foreach ($sub_els as $el) {
            $value = $this->presenter->getElementsPreview(
                $el[0],
                $el[2] ?? null
            );
            $properties[$el[1]] = $value;
        }

        return $properties;
    }

    /**
     * @param ilMDBaseElement[] $elements
     * @return string[]
     */
    public function getPropertiesByData(
        array $elements
    ): array {
        $properties = [];

        if (empty($properties)) {
            $data_els = $this->data_finder->getDataElements(
                $elements[0],
                true
            );
            foreach ($data_els as $data_el) {
                if ($data_el->isScaffold()) {
                    continue;
                }
                $title = $this->presenter->getElementNameWithParents(
                    $data_el,
                    false,
                    $elements[0]->getName()
                );
                $descr = $this->presenter->getDataValue($data_el->getData());
                $properties[$title] = $descr;
            }
        }
        return $properties;
    }

    protected function getStructureWithPointerAtElement(
        ilMDBaseElement $element
    ): ilMDLOMEditorGUIStructure {
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $structure = $this->dict->getStructure();
        $structure->movePointerToRoot();
        foreach ($name_path as $next_name) {
            $structure->movePointerToSubElement($next_name);
        }
        return $structure;
    }
}
