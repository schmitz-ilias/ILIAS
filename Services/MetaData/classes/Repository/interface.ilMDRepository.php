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
interface ilMDRepository
{
    /**
     * Follows all trails of markers from the passed root element,
     * and creates MD elements in place of every marked scaffold
     * along the trail. Data on markers is transferred to the created
     * elements.
     */
    public function createMDElements(ilMDRootElement $root): void;

    /**
     * Returns as scaffolds the elements that could be added to this
     * element as sub-elements.
     * @return ilMDScaffoldElement[]
     */
    public function getScaffoldForElement(ilMDBaseElement $element): array;

    /**
     * Returns the root element of the MD, with the full MD set
     * as nested sub-elements.
     */
    public function getMD(): ilMDRootElement;

    /**
     * Follows all trails of markers from the passed root element,
     * and updates the data of MD elements which have data markers on them.
     */
    public function updateMDElements(ilMDRootElement $root): void;

    /**
     * Follows all trails of markers from the passed root element,
     * and deletes the respective last element of the trails, as well as all of
     * their nested subelements.
     */
    public function deleteMDElements(ilMDRootElement $root): void;

    public function deleteAllMDElements(): void;
}
