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

namespace ILIAS\MetaData\Elements\Markers;

use ILIAS\MetaData\Elements\Data\DataInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface MarkableInterface
{
    /**
     * Elements can be marked to be created, updated or deleted.
     */
    public function isMarked(): bool;

    /**
     * When a marked element is created or updated, the marker's
     * data is transferred to the new or modified element.
     */
    public function getMarkerData(): ?DataInterface;

    /**
     * Leaves a trail of markers from this element up to the root element.
     * Places a marker with the given data value on this element, and null markers
     * on the others, leaving already marked elements alone.
     */
    public function mark(
        MarkerFactoryInterface $factory,
        string $data_value = ''
    );
}
