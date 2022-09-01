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
    public function getNeutralMarker(): ilMDMarker
    {
        return new ilMDMarker();
    }

    public function getDataMarker(ilMDData $data): ilMDDataMarker
    {
        return new ilMDDataMarker($data);
    }

    /**
     * TODO update this when implementing database markers
     */
    public function getDatabaseMarker(): ilMDDatabaseMarker
    {
        return new ilMDDatabaseMarker();
    }

    /**
     * TODO update this when implementing the GUI markers
     */
    public function getGUIMarker(): ilMDGUIMarker
    {
        return new ilMDGUIMarker();
    }
}
