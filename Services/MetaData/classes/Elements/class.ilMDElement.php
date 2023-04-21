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

namespace classes\Elements;

use classes\Elements\Data\ilMDData;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDElement extends ilMDBaseElement
{
    protected int $md_id;
    protected ilMDData $data;

    /**
     * @param string            $name
     * @param bool              $unique
     * @param ilMDBaseElement[] $sub_elements
     * @param int               $md_id
     * @param ilMDData          $data
     */
    public function __construct(
        string $name,
        bool $unique,
        array $sub_elements,
        int $md_id,
        ilMDData $data
    ) {
        parent::__construct($name, $unique, $sub_elements);
        $this->md_id = $md_id;
        $this->data = $data;
    }

    public function getMDID(): int
    {
        return $this->md_id;
    }

    public function getData(): ilMDData
    {
        return $this->data;
    }
}
