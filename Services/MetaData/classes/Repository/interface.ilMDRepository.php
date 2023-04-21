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
use classes\Elements\ilMDScaffoldElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface ilMDRepository
{
    /**
     * Follows a trail of markers from the root element,
     * and creates or updates marked MD elements along the trail.
     * Non-scaffold elements with non-null matching data markers are
     * updated according to the marker, scaffold elements with matching
     * data markers are created with the data on the marker.
     */
    public function createAndUpdateMDElements(ilMDRootElement $root): void;

    /**
     * Returns as scaffolds the elements that could be added to this
     * element as sub-elements. If a name is provided, returns only
     * scaffolds with that name.
     * @return ilMDScaffoldElement[]
     */
    public function getScaffoldForElement(
        ilMDBaseElement $element,
        string $name = ''
    ): array;

    /**
     * Returns the root element of the MD, with the full MD set
     * as nested sub-elements.
     */
    public function getMD(): ilMDRootElement;

    /**
     * Returns only the MD elements specified on a path, and all nested
     * subelements of the last elements on the path, via the
     * last element(s) on the path. Elements on the path that don't exist
     * in the MD set are added as scaffolds.
     * @return ilMDBaseElement[]
     */
    public function getMDOnPath(ilMDPathFromRoot $path): array;

    /**
     * Follows the trail of markers from the passed root element,
     * and deletes the last element of the trail, as well as all of
     * its nested subelements.
     */
    public function deleteMDElements(ilMDRootElement $root): void;

    public function deleteAllMDElements(): void;
}
