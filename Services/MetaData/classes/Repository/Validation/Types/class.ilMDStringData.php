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

namespace Validation\Types;

use classes\Elements\Data\ilMDData;
use classes\Elements\Data\LOMDataType;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDStringData extends ilMDData
{
    protected const ERROR = 'This should not be empty.';

    public function getType(): LOMDataType
    {
        return LOMDataType::TYPE_STRING;
    }

    public function isValid(): bool
    {
        return $this->getValue() !== '';
    }
}
