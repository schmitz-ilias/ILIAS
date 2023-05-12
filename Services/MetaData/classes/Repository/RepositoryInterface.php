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

namespace ILIAS\MetaData\Repository;

use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Elements\SetInterface;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Elements\RessourceID\RessourceIDInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface RepositoryInterface
{
    public function getRessourceID(): RessourceIDInterface;

    public function getMD(): SetInterface;

    /**
     * Returns an MD set with only the elements specified on a path, and all nested
     * subelements of the last elements on the path.
     * The path must start from the root element.
     * Note that resulting partial MD sets might not be completely valid, due to
     * conditions between elements. Be careful when dealing with vocabularies, or
     * Technical > Requirement > OrComposite.
     */
    public function getMDOnPath(PathInterface $path): SetInterface;

    /**
     * Returns all elements that could be added as sub-elements
     * to the given element as scaffolds. Scaffolds are used to
     * mark where elements could potentially be created.
     * @return ElementInterface[]
     */
    public function getScaffoldsForElement(
        ElementInterface $element
    ): \Generator;

    /**
     * Follows a trail of markers from the root element,
     * and creates, updates or deletes marked MD elements along the trail.
     * Non-scaffold elements with 'create or update' markers are
     * updated, and scaffold elements with 'create or update' markers
     * are created with the data value on the marker. Scaffold elements
     * with neutral markers are created with empty data.
     */
    public function manipulateMD(SetInterface $set): void;

    public function deleteAllMD(): void;
}
