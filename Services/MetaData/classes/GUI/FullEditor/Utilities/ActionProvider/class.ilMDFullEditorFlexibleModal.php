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

use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Signal as Signal;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorFlexibleModal
{
    protected ?Modal $modal;
    protected ilMDFullEditorFlexibleSignal $flexible_signal;

    public function __construct(
        ?Modal $modal = null,
        string $alternative_link = ''
    ) {
        $this->modal = $modal;
        if (isset($this->modal)) {
            $this->flexible_signal = new ilMDFullEditorFlexibleSignal(
                $this->modal->getShowSignal()
            );
        } else {
            $this->flexible_signal = new ilMDFullEditorFlexibleSignal(
                $alternative_link
            );
        }
    }

    public function getModal(): ?Modal
    {
        return $this->modal;
    }

    public function getFlexibleSignal(): ilMDFullEditorFlexibleSignal
    {
        return $this->flexible_signal;
    }
}
