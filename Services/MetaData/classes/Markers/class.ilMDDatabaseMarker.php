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
class ilMDDatabaseMarker extends ilMDMarker
{
    protected ilDBStatement $create;
    protected ilDBStatement $read;

    /**
     * Some MD elements can not be updated, since they don't carry data.
     */
    protected ?ilDBStatement $update;
    protected ilDBStatement $delete;

    /**
     * @var string[]
     */
    protected array $expected_params;

    /**
     * @param ilDBStatement      $create
     * @param ilDBStatement      $read
     * @param ilDBStatement|null $update
     * @param ilDBStatement      $delete
     * @param string[]           $expected_params
     */
    public function __construct(
        ilDBStatement $create,
        ilDBStatement $read,
        ?ilDBStatement $update,
        ilDBStatement $delete,
        array $expected_params = []
    ) {
        $this->create = $create;
        $this->read = $read;
        $this->update = $update;
        $this->delete = $delete;
        $this->expected_params = $expected_params;
    }

    public function getCreate(): ilDBStatement
    {
        return $this->create;
    }

    public function getRead(): ilDBStatement
    {
        return $this->read;
    }

    public function getUpdate(): ?ilDBStatement
    {
        return $this->update;
    }

    public function getDelete(): ilDBStatement
    {
        return $this->delete;
    }

    /**
     * @return string[]
     */
    public function getExpectedParams(): array
    {
        return $this->expected_params;
    }
}
