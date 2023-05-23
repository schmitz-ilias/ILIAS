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

namespace ILIAS\MetaData\Editor\Full\Services\Inputs;

use ILIAS\UI\Component\Input\Field\FormInput;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Repository\Validation\Data\LangValidator;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class LangFactory extends BaseFactory
{
    protected function rawInput(
        ElementInterface $element,
        ElementInterface $context_element,
        string $condition_value = ''
    ): FormInput {
        $langs = [];
        foreach (LangValidator::LANGUAGES as $key) {
            $langs[$key] = $this->presenter->data()->language($key);
        }
        return $this->ui_factory->select('placeholder', $langs);
    }
}
