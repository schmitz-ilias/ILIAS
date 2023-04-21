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

use ILIAS\UI\Factory;
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Button\Standard as StandardButton;
use ILIAS\UI\Component\Button\Shy as ShyButton;
use classes\Elements\ilMDScaffoldElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorActionButtonProvider
{
    protected Factory $factory;
    protected ilMDLOMPresenter $presenter;

    public function __construct(
        Factory $factory,
        ilMDLOMPresenter $presenter
    ) {
        $this->factory = $factory;
        $this->presenter = $presenter;
    }

    public function delete(
        ilMDFullEditorFlexibleSignal $signal,
        bool $is_shy = false,
        bool $long_text = false
    ): Button {
        $label = $this->presenter->txt(
            $long_text ? 'meta_delete_this_element' : 'delete'
        );
        if ($is_shy) {
            return $this->getShyButton($label, $signal);
        }
        return $this->getStandardButton($label, $signal);
    }

    public function update(
        ilMDFullEditorFlexibleSignal $signal
    ): ShyButton {
        $label = $this->presenter->txt('edit');
        return $this->getShyButton($label, $signal);
    }

    public function create(
        ilMDFullEditorFlexibleSignal $signal,
        ilMDScaffoldElement $element,
        bool $is_shy = false
    ): Button {
        $label = $this->presenter->txtFill(
            'meta_add_element',
            [$this->presenter->getElementName($element)]
        );
        if ($is_shy) {
            return $this->getShyButton($label, $signal);
        }
        return $this->getStandardButton($label, $signal);
    }

    protected function getShyButton(
        string $label,
        ilMDFullEditorFlexibleSignal $signal
    ): ShyButton {
        return $this->factory->button()->shy($label, $signal->get());
    }

    protected function getStandardButton(
        string $label,
        ilMDFullEditorFlexibleSignal $signal
    ): StandardButton {
        return $this->factory->button()->standard($label, $signal->get());
    }
}
