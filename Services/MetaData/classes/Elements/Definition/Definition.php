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

namespace ILIAS\MetaData\Elements\Definition;

use ILIAS\MetaData\Elements\Data\LOMType;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Definition implements DefinitionInterface
{
    protected string $name;
    protected bool $unique;
    protected LOMType $data_type;

    public function __construct(
        string $name,
        bool $unique,
        LOMType $data_type
    ) {
        $this->name = $name;
        $this->unique = $unique;
        $this->data_type = $data_type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unqiue(): bool
    {
        return $this->unique;
    }

    public function dataType(): LOMType
    {
        return $this->data_type;
    }
}
