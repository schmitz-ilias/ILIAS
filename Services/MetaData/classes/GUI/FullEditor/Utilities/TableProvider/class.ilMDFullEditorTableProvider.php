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

use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorTableProvider
{
    protected Factory $factory;
    protected Renderer $renderer;
    protected ilMDLOMPresenter $presenter;
    protected ilMDFullEditorDataFinder $data_finder;

    public function __construct(
        Factory $factory,
        Renderer $renderer,
        ilMDLOMPresenter $presenter,
        ilMDFullEditorDataFinder $data_finder,
    ) {
        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->presenter = $presenter;
        $this->data_finder = $data_finder;
    }

    public function getTable(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): ilMDFullEditorTableGUI {
        $table =  new ilMDFullEditorTableGUI(
            null,
            $root,
            $path,
            $this->factory,
            $this->renderer,
            $this->presenter,
            $this->data_finder
        );
        $table->init();
        return $table;
    }
}
