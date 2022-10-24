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
use ILIAS\UI\Component\Button\Standard as StandardButton;
use ILIAS\UI\Component\Button\Shy as ShyButton;
use ILIAS\UI\Component\Modal\Interruptive as InterruptiveModal;
use ILIAS\Data\URI;
use ILIAS\UI\Component\Signal as Signal;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorActionProvider
{
    public const MAX_MODAL_CHARS = 150;

    public const CREATE = 'fullEditorCreate';
    public const UPDATE = 'fullEditorUpdate';
    public const DELETE = 'fullEditorDelete';

    /**
     * @param Factory          $factory
     * @param ilMDLOMPresenter $presenter
     * @param URI              $base_link
     * @param ilMDPathFromRoot $base_path
     * @param ilMDPathFromRoot $delete_path
     * @param ilMDRootElement  $root
     * @param string[]         $content
     * @return InterruptiveModal
     */
    public function getDeleteModal(
        Factory $factory,
        ilMDLOMPresenter $presenter,
        URI $base_link,
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $delete_path,
        ilMDRootElement $root,
        array $content
    ): InterruptiveModal {
        $action = $this->getActionLink(
            $base_link,
            $base_path,
            $delete_path,
            self::DELETE
        );

        $items = [];
        $index = 0;
        foreach ($content as $title => $descr) {
            if (($len = strlen($title) - self::MAX_MODAL_CHARS) > 0) {
                $title = substr($title, 0, -$len - 1) . "\xe2\x80\xa6";
            }
            if (($len = strlen($descr) - self::MAX_MODAL_CHARS) > 0) {
                $descr = substr($descr, 0, -$len - 1) . "\xe2\x80\xa6";
            }
            $items[] = $factory->modal()->interruptiveItem(
                'md_delete_' . $index,
                $title,
                null,
                $descr
            );
            $index++;
        }

        $elements = $root->getSubElementsByPath($delete_path);
        return $factory->modal()->interruptive(
            $presenter->txtFill(
                'delete_element',
                [$presenter->getElementNameWithParents($elements[0])]
            ),
            $presenter->txt('delete_confirm'),
            (string) $action
        )->withAffectedItems($items);
    }

    public function getStandardDeleteButton(
        Signal $modal_signal,
        Factory $factory,
        ilMDLOMPresenter $presenter,
        bool $long_text = false
    ): StandardButton {
        return $this->getDeleteButton(
            false,
            $modal_signal,
            $factory,
            $presenter,
            $long_text
        );
    }

    public function getShyDeleteButton(
        Signal $modal_signal,
        Factory $factory,
        ilMDLOMPresenter $presenter,
        bool $long_text = false
    ): ShyButton {
        return $this->getDeleteButton(
            true,
            $modal_signal,
            $factory,
            $presenter,
            $long_text
        );
    }

    protected function getDeleteButton(
        bool $is_shy,
        Signal $modal_signal,
        Factory $factory,
        ilMDLOMPresenter $presenter,
        bool $long_text = false
    ): StandardButton|ShyButton {
        $label = $presenter->txt(
            $long_text ? 'delete_this_element' : 'delete'
        );
        if ($is_shy) {
            return $factory->button()->shy($label, $modal_signal);
        }
        return $factory->button()->standard($label, $modal_signal);
    }

    public function getActionLink(
        URI $base_link,
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $action_path,
        string $action_cmd
    ): URI {
        $actions = [self::CREATE, self::DELETE, self::UPDATE];
        if (!in_array($action_cmd, $actions)) {
            throw new ilMDGUIException(
                'Invalid action: ' . $action_cmd
            );
        }

        return $base_link
            ->withParameter(
                ilMDEditorGUI::MD_NODE_PATH,
                $base_path->getPathAsString()
            )
            ->withParameter(
                ilMDEditorGUI::MD_ACTION_PATH,
                $action_path->getPathAsString()
            )
            ->withParameter(
                'cmd',
                $action_cmd
            );
    }
}
