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

namespace classes\Elements;

use classes\Elements\Markers\ilMDMarker;
use ilMDBuildingBlocksException;

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

    public function __clone()
    {
        $this->super_element = null;
        // for some reason $this can't be used in closures
        $self = $this;
        $this->sub_elements = array_map(
            function (ilMDBaseElement $arg) use ($self) {
                $arg = clone $arg;
                $arg->setSuperElement($self);
                return $arg;
            },
            $this->sub_elements
        );
    }

    /**
     * If a name is given, only returns sub-elements with that name (including
     * scaffolds). If an ID is given, only returns sub-elements with that ID,
     * and does not return any scaffolds.
     * @return ilMDBaseElement[]
     */
    public function getSubElements(
        string $name = '',
        ?int $md_id = null
    ): array {
        $res = [];
        foreach ($this->sub_elements as $sub_element) {
            if ($name !== '' && $name !== $sub_element->getName()) {
                continue;
            }
            if (
                isset($md_id) &&
                (
                    $sub_element->isScaffold() ||
                    $md_id !== $sub_element->getMDID()
                )
            ) {
                continue;
            }
            $res[] = $sub_element;
        }
        return $res;
    }

    public function addScaffoldToSubElements(
        ilMDScaffoldElement $scaffold
    ): void {
        if ($scaffold->getSuperElement()) {
            throw new ilMDBuildingBlocksException(
                'This scaffold was already added to a different super-element.'
            );
        }
        $scaffold->setSuperElement($this);
        $this->sub_elements[] = $scaffold;
    }

    public function getSuperElement(): ?ilMDBaseElement
    {
        return $this->super_element;
    }

    protected function setSuperElement(ilMDBaseElement $super_element): void
    {
        $this->super_element = $super_element;
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

    // TODO don't put this in the public API
    public function deleteFromSubElements(ilMDBaseElement $element): void
    {
        foreach ($this->sub_elements as $key => $sub_el) {
            if ($sub_el === $element) {
                unset($this->sub_elements[$key]);
            }
        }
    }
}
