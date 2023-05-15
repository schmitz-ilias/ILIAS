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

use ILIAS\Data\URI;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorActionLinkProvider
{
    protected URI $base_link;

    public function __construct(
        URI $base_link,
    ) {
        $this->base_link = $base_link;
    }

    public function create(
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $action_path
    ): URI {
        return $this->getLink(
            $base_path,
            $action_path,
            ilMDFullEditorActionProvider::CREATE
        );
    }

    public function update(
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $action_path
    ): URI {
        return $this->getLink(
            $base_path,
            $action_path,
            ilMDFullEditorActionProvider::UPDATE
        );
    }

    public function delete(
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $action_path
    ): URI {
        return $this->getLink(
            $base_path,
            $action_path,
            ilMDFullEditorActionProvider::DELETE
        );
    }

    protected function getLink(
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $action_path,
        string $action_cmd
    ): URI {
        $actions = [
            ilMDFullEditorActionProvider::CREATE,
            ilMDFullEditorActionProvider::DELETE,
            ilMDFullEditorActionProvider::UPDATE
        ];
        if (!in_array($action_cmd, $actions)) {
            throw new ilMDEditorException(
                'Invalid action: ' . $action_cmd
            );
        }

        return $this->base_link
            ->withParameter(
                ilMDEditorGUI::MD_NODE_PATH,
                $base_path->getPathAsString()
            )
            ->withParameter(
                ilMDEditorGUI::MD_ACTION_PATH,
                $action_path->getPathAsString()
            )
            ->withParameter(
                ilCtrlInterface::PARAM_CMD,
                $action_cmd
            );
    }
}
