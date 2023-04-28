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

namespace ILIAS\MetaData\Elements;

use ILIAS\MetaData\Elements\Markers\Marker;
use ILIAS\MetaData\Elements\Markers\MarkableInterface;
use ILIAS\MetaData\Elements\Data\Data;
use ILIAS\MetaData\Elements\Markers\MarkerFactoryInterface;
use ILIAS\MetaData\Elements\Data\LOMType;
use ILIAS\MetaData\Elements\Scaffolds\ScaffoldableInterface;
use ILIAS\MetaData\Elements\Definition\Definition;
use ILIAS\MetaData\Elements\Base\BaseElement;
use ILIAS\MetaData\Elements\Scaffolds\ScaffoldFactoryInterface;
use ILIAS\MetaData\Elements\Definition\DefinitionInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Element extends BaseElement implements ElementInterface, MarkableInterface, ScaffoldableInterface
{
    private ?Marker $marker = null;
    private Data $data;

    public function __construct(
        NoID|int $md_id,
        Definition $definition,
        Data $data,
        BaseElement ...$sub_elements
    ) {
        $this->data = $data;
        parent::__construct($md_id, $definition, ...$sub_elements);
    }

    public function getData(): Data
    {
        return $this->data;
    }

    public function isScaffold(): bool
    {
        return $this->getMDID() === NoID::SCAFFOLD;
    }

    public function getSuperElement(): ?Element
    {
        $super = parent::getSuperElement();
        if (!isset($super) || ($super instanceof Element)) {
            return $super;
        }
        throw new \ilMDElementsException(
            'Metadata element has invalid super-element.'
        );
    }

    /**
     * @return Element[]
     */
    public function getSubElements(): \Generator
    {
        foreach (parent::getSubElements() as $sub_element) {
            if (!($sub_element instanceof Element)) {
                throw new \ilMDElementsException(
                    'Metadata element has invalid sub-element.'
                );
            }
            yield $sub_element;
        }
    }

    public function isMarked(): bool
    {
        return isset($this->marker);
    }

    public function getMarkerData(): ?Data
    {
        return $this?->marker->data();
    }

    public function mark(
        MarkerFactoryInterface $factory,
        string $data_value = ''
    ): void {
        $this->setMarker($factory->marker(
            $this->getDefinition()->dataType(),
            $data_value
        ));
        $curr_element = $this->getSuperElement();
        while ($curr_element) {
            if ($curr_element->isMarked()) {
                return;
            }
            $curr_element->setMarker($factory->marker(LOMType::NULL, ''));
            $curr_element = $curr_element->getSuperElement();
        }
    }

    protected function setMarker(?Marker $marker): void
    {
        $this->marker = $marker;
    }

    public function addScaffoldToSubElements(
        ScaffoldFactoryInterface $scaffold_factory,
        DefinitionInterface $definition
    ): void {
        $scaffold = $scaffold_factory->scaffold($definition);
        $this->addSubElement($scaffold);
    }
}
