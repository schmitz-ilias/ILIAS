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
class ilMDMarkerFactory
{
    protected ilMDLOMDataFactory $data_factory;

    public function __construct($data_factory)
    {
        $this->data_factory = $data_factory;
    }

    public function Marker(ilMDData $data): ilMDMarker
    {
        return new ilMDMarker($data);
    }

    public function NullMarker(): ilMDMarker
    {
        return $this->Marker($this->data_factory->MDNullData());
    }
}
