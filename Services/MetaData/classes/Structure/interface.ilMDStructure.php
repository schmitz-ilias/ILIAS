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
 * Fluent interface for navigating MD structure
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface ilMDStructure
{
    public function isInReadMode(): bool;

    public function switchToReadMode(): ilMDStructure;

    public function getNameAtPointer(): string;

    /**
     * Returns the path to the current position of the pointer as an array of
     * element names, starting from the root element.
     * @return string[]
     */
    public function getPointerPath(): array;

    public function isPointerAtRootElement(): bool;

    public function isUniqueAtPointer(): bool;

    /**
     * Returns the names of sub-elements of the current position of the pointer.
     * @return string[]
     */
    public function getSubElementsAtPointer(): array;

    /**
     * Returns the type of data that can be carried by the current element.
     */
    public function getTypeAtPointer(): string;

    public function getMarkerAtPointer(): ?ilMDMarker;

    /**
     * Throws an Exception if read mode is on.
     * @throws ilMDStructureException
     */
    public function setMarkerAtPointer(ilMDMarker $marker): ilMDStructure;

    public function movePointerToRoot(): ilMDStructure;

    /**
     * Throws an Exception if at the root element.
     * @throws ilMDStructureException
     */
    public function movePointerToSuperElement(): ilMDStructure;

    /**
     * Throws an exception if there is no sub-element with that name at
     * the current position of the pointer.
     * @throws ilMDStructureException
     */
    public function movePointerToSubElement(string $name): ilMDStructure;
}
