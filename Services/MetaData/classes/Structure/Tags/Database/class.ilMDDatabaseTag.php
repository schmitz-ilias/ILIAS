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
class ilMDDatabaseTag extends ilMDTag
{
    protected string $create;
    protected string $read;
    protected string $update;
    protected string $delete;

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
     * @param string    $create
     * @param string    $read
     * @param string    $update
     * @param string    $delete
     * @param string    $table
     * @param string[]  $expected_params
     */
    public function __construct(
        string $create,
        string $read,
        string $update,
        string $delete,
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

    public function getCreate(): string
    {
        return $this->create;
    }

    public function getRead(): string
    {
        return $this->read;
    }

    public function getUpdate(): string
    {
        return $this->update;
    }

    public function getDelete(): string
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

    public function withIsParent(bool $is_parent): ilMDDatabaseTag
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
