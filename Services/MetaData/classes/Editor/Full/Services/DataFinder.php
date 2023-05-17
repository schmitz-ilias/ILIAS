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

namespace ILIAS\MetaData\Editor\Full\Services;

use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Elements\Data\Type;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class DataFinder
{
    /**
     * @return ElementInterface[]
     */
    public function getDataCarryingElements(
        ElementInterface $start_element,
        bool $skip_vocab_source = false
    ): \Generator {
        $elements = $this->getDataElementsInSubElements(
            $start_element,
            $skip_vocab_source
        );
        yield from $elements;
    }

    /**
     * @return ElementInterface[]
     */
    protected function getDataElementsInSubElements(
        ElementInterface $current_element,
        bool $skip_vocab_source
    ): array {
        $elements = [];
        $type = $current_element->getData()->type();
        if (
            $type !== Type::NULL &&
            !($skip_vocab_source && $type === Type::VOCAB_SOURCE)
        ) {
            $elements[] = $current_element;
        }
        foreach ($current_element->getSubElements() as $sub) {
            $elements = array_merge(
                $elements,
                $this->getDataElementsInSubElements(
                    $sub,
                    $skip_vocab_source
                )
            );
        }
        return $elements;
    }
}
