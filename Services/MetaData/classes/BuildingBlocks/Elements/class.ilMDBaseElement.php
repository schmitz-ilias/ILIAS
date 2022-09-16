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
abstract class ilMDBaseElement
{
    /**
     * @var ilMDBaseElement[]
     */
    protected array $sub_elements;
    protected ?ilMDBaseElement $super_element = null;
    protected bool $unique;
    protected string $name;

    protected ?ilMDMarker $marker = null;

    /**
     * @param string            $name
     * @param bool              $unique
     * @param ilMDBaseElement[] $sub_elements
     */
    public function __construct(
        string $name,
        bool $unique,
        array $sub_elements
    ) {
        foreach ($sub_elements as $sub_element) {
            $sub_element->setSuperElement($this);
        }
        $this->sub_elements = $sub_elements;
        $this->unique = $unique;
        $this->name = $name;
    }

    /**
     * @return ilMDBaseElement[]
     */
    public function getSubElements(): array
    {
        return $this->sub_elements;
    }

    public function addScaffoldToSubElements(
        ilMDScaffoldElement $scaffold
    ): void {
        $scaffold->setSuperElement($this);
        $this->sub_elements[] = $scaffold;
    }

    /**
     * This method can be used to access specific sub-elements quicker.
     * Call the name of the sub-element as a method. When the element is not
     * unique, pass its ID as an argument. Note that this can not be used to
     * access scaffold elements.
     */
    public function getSubElement(string $name, ?int $md_id = null): ?ilMDElement
    {
        $res = [];
        foreach ($this->getSubElements() as $sub_element) {
            if (
                !$sub_element->isScaffold() &&
                $sub_element->getName() === $name
            ) {
                $res[] = $sub_element;
            }
        }
        if (empty($res)) {
            return null;
        }
        //if a unique element was found, return it
        if (count($res) === 1 && $res[0]->isUnique()) {
            return $res[0];
        }
        //else check for an id as argument
        if (!isset($md_id)) {
            throw new ilMDBuildingBlocksException(
                "To access the non-unique sub-element ' . $name .
                 ', pass its ID as an argument."
            );
        }
        foreach ($res as $sub_element) {
            if ($sub_element->getMDID() === $md_id) {
                return $sub_element;
            }
        }
        return null;
    }

    public function getSuperElement(): ?ilMDBaseElement
    {
        return $this->super_element;
    }

    protected function setSuperElement(ilMDBaseElement $super_element): void
    {
        if (!isset($this->super_element)) {
            $this->super_element = $super_element;
            return;
        }
        throw new ilMDBuildingBlocksException(
            "This element already has a superordinate element."
        );
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRoot(): bool
    {
        return false;
    }

    public function isScaffold(): bool
    {
        return false;
    }

    /**
     * Leaves a trail of markers from this element up to the root element.
     * Places the first marker on this element, stops when it reaches an
     * element that already has a marker.
     */
    public function leaveMarkerTrail(
        ilMDMarker $first_marker,
        ilMDMarker $trail_marker
    ): void {
        $this->setMarker($first_marker);
        $curr_element = $this->getSuperElement();
        while (isset($curr_element)) {
            if ($curr_element->getMarker() !== null) {
                return;
            }
            $curr_element->setMarker($trail_marker);
            $curr_element = $curr_element->getSuperElement();
        }
    }

    public function setMarker(?ilMDMarker $marker): void
    {
        $this->marker = $marker;
    }

    public function getMarker(): ?ilMDMarker
    {
        return $this->marker;
    }
}
