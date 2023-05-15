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

use classes\Elements\Data\ilMDLOMDataFactory;
use classes\Elements\ilMDBaseElement;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorDataFinder
{
    protected ilMDLOMDictionary $dict;

    public function __construct(ilMDLOMDictionary $dict)
    {
        $this->dict = $dict;
    }

    /**
     * @return ilMDBaseElement[]
     */
    public function getDataElements(
        ilMDBaseElement $start_element,
        bool $skip_vocab_source = false
    ): array {
        $structure = $this->dict->getStructure();
        $elements = [];
        $this->addDataElements(
            $elements,
            $start_element,
            $structure,
            $skip_vocab_source,
            0
        );
        return $elements;
    }

    /**
     * @param ilMDBaseElement[] $elements
     * @param ilMDBaseElement   $current_element
     * @param ilMDLOMStructure  $structure
     * @param bool              $skip_vocab_source
     * @param int               $depth
     */
    protected function addDataElements(
        array &$elements,
        ilMDBaseElement $current_element,
        ilMDLOMStructure $structure,
        bool $skip_vocab_source,
        int $depth
    ): void {
        //stop the recursion after a while, just to be safe.
        if ($depth >= 20) {
            throw new ilMDEditorException(
                'Recursion reached its maximum depth'
            );
        }

        $type = $this->getElementDataTypeFromStructure(
            $current_element,
            $structure
        );
        if (
            $type !== ilMDLOMDataFactory::TYPE_NULL &&
            (!$skip_vocab_source ||
                $type !==ilMDLOMDataFactory::TYPE_VOCAB_SOURCE)
        ) {
            $elements[] = $current_element;
        }
        $sub_names = $this->getElementSubElementsFromStructure(
            $current_element,
            $structure
        );
        foreach ($sub_names as $sub_name) {
            foreach ($current_element->getSubElements($sub_name) as $sub) {
                $this->addDataElements(
                    $elements,
                    $sub,
                    $structure->movePointerToRoot(),
                    $skip_vocab_source,
                    $depth + 1
                );
            }
        }
    }

    protected function getElementDataTypeFromStructure(
        ilMDBaseElement $element,
        ilMDLOMStructure $structure,
    ): string {
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $structure->movePointerToRoot();
        foreach ($name_path as $next_name) {
            $structure->movePointerToSubElement($next_name);
        }
        return $structure->getTypeAtPointer();
    }

    /**
     * @param ilMDBaseElement  $element
     * @param ilMDLOMStructure $structure
     * @return string[]
     */
    protected function getElementSubElementsFromStructure(
        ilMDBaseElement $element,
        ilMDLOMStructure $structure,
    ): array {
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $structure->movePointerToRoot();
        foreach ($name_path as $next_name) {
            $structure->movePointerToSubElement($next_name);
        }
        return $structure->getSubElementsAtPointer();
    }
}
