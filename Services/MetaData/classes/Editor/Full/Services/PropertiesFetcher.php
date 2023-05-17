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

use ILIAS\MetaData\Editor\Presenter\Presenter;
use ILIAS\MetaData\Editor\Dictionary\DictionaryInterface;
use ILIAS\MetaData\Elements\ElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class PropertiesFetcher
{
    protected DictionaryInterface $dictionary;
    protected Presenter $presenter;
    protected DataFinder $data_finder;

    public function __construct(
        DictionaryInterface $dictionary,
        Presenter $presenter,
        DataFinder $data_finder
    ) {
        $this->dictionary = $dictionary;
        $this->presenter = $presenter;
        $this->data_finder = $data_finder;
    }

    /**
     * @return string[]
     */
    public function getPropertiesByPreview(
        ElementInterface $element
    ): \Generator {
        $sub_els = [];
        foreach ($element->getSubElements() as $sub) {
            if ($sub->isScaffold()) {
                continue;
            }
            $tag = $this->dictionary->tagForElement($sub);
            if ($tag?->isCollected() && $tag?->isLastInTree()) {
                $sub_els[$sub->getDefinition()->name()][] = $sub;
                continue;
            }
            $sub_els[] = $sub;
        }
        foreach ($sub_els as $el) {
            $el_array = is_array($el) ? $el : [$el];
            $label = $this->presenter->elements()->nameWithRepresentation(
                is_array($el),
                ...$el_array
            );
            $value = $this->presenter->elements()->preview(
                ...$el_array
            );
            yield $label => $value;
        }
    }

    /**
     * @return string[]
     */
    public function getPropertiesByData(
        ElementInterface $element
    ): \Generator {
        $data_els = $this->data_finder->getDataCarryingElements(
            $element,
            true
        );
        foreach ($data_els as $data_el) {
            if ($data_el->isScaffold()) {
                continue;
            }
            $title = $this->presenter->elements()->nameWithParents(
                $data_el,
                $element
            );
            $value = $this->presenter->data()->dataValue($data_el->getData());
            yield $title => $value;
        }
    }
}
