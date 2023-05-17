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

namespace ILIAS\MetaData\Editor\Full\Services\Actions;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Actions
{
    protected ButtonFactory $button_provider;
    protected ModalFactory $modal_provider;
    protected LinkProvider $link_provider;

    public function __construct(
        LinkProvider $link_provider,
        ButtonFactory $button_provider,
        ModalFactory $modal_provider
    ) {
        $this->link_provider = $link_provider;
        $this->button_provider = $button_provider;
        $this->modal_provider = $modal_provider;
    }

    public function getModal(): ModalFactory
    {
        return $this->modal_provider;
    }

    public function getButton(): ButtonFactory
    {
        return $this->button_provider;
    }

    public function getLink(): LinkProvider
    {
        return $this->link_provider;
    }
}
