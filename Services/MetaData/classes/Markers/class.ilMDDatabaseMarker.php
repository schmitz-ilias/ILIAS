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
    /**
     * Some MD elements are containers without their own tables,
     * and can not really be created on their own.
     */
    protected ?ilDBStatement $create;
    protected ilDBStatement $read;

    /**
     * Some MD elements can not be updated, since they don't carry data.
     */
    protected ?ilDBStatement $update;

    /**
     * Sources of vocabularies can not be deleted, since they are
     * all equal to LOMv1.0.
     */
    protected ?ilDBStatement $delete;

    protected string $table;

    /**
     * Marks elements which act as 'parents' for subordinate elements
     * in the db structure, and whose id is needed to access those
     * sub-elements (until the next parent in the hierarchy comes up).
     */
    protected bool $is_parent = false;

    /**
     * @var string[]
     */
    protected array $expected_params;

    /**
     * @param ilDBStatement|null $create
     * @param ilDBStatement      $read
     * @param ilDBStatement|null $update
     * @param ilDBStatement|null $delete
     * @param string             $table
     * @param string[]           $expected_params
     */
    public function __construct(
        ?ilDBStatement $create,
        ilDBStatement $read,
        ?ilDBStatement $update,
        ?ilDBStatement $delete,
        string $table,
        array $expected_params = []
    ) {
        $this->create = $create;
        $this->read = $read;
        $this->update = $update;
        $this->delete = $delete;
        $this->table = $table;
        $this->expected_params = $expected_params;
    }

    public function getCreate(): ?ilDBStatement
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

    public function getDelete(): ?ilDBStatement
    {
        return $this->delete;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function isParent(): bool
    {
        return $this->is_parent;
    }

    public function withIsParent(bool $is_parent): ilMDDatabaseMarker
    {
        $this->is_parent = $is_parent;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getExpectedParams(): array
    {
        return $this->expected_params;
    }
}
