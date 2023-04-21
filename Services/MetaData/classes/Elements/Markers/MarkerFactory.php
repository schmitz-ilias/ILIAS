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

use ILIAS\MetaData\Elements\Data\Data;
use ILIAS\MetaData\Elements\Data\DataFactory;
use ILIAS\MetaData\Elements\Data\LOMType;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class MarkerFactory
{
    protected DataFactory $data_factory;

    public function __construct($data_factory)
    {
        $this->data_factory = $data_factory;
    }

    public function marker(LOMType $data_type, string $data_value): Marker
    {
        return new Marker($this->data_factory->data($data_type, $data_value));
    }
}
