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

use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;
use classes\Elements\ilMDElement;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorTableGUI extends ilTable2GUI
{
    protected ilMDPathFromRoot $cmd_path;
    protected ilMDRootElement $root;

    protected Factory $factory;
    protected Renderer $renderer;
    protected ilMDLOMPresenter $presenter;
    protected ilMDFullEditorDataFinder $data_finder;

    /**
     * @var ilMDElement[]
     */
    protected array $current_elements;

    public function __construct(
        ?object $parent_obj,
        ilMDRootElement $root,
        ilMDPathFromRoot $cmd_path,
        Factory $factory,
        Renderer $renderer,
        ilMDLOMPresenter $presenter,
        ilMDFullEditorDataFinder $data_finder,
    ) {
        parent::__construct($parent_obj);
        $this->cmd_path = $cmd_path;
        $this->root = $root;
        $this->current_elements = $this->getElementsByPath(
            $root,
            $cmd_path
        );

        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->presenter = $presenter;
        $this->data_finder = $data_finder;
    }

    public function init(): void
    {
        $this->setRowTemplate(
            'tpl.full_editor_row.html',
            'Services/MetaData'
        );
        $this->setTitle($this->presenter->getElementNameWithParents(
            $this->current_elements[0],
            true
        ));
        $this->setExternalSegmentation(true);

        foreach ($this->data_finder->getDataElements(
            $this->current_elements[0],
            true
        ) as $data_el) {
            $this->addColumn($this->presenter->getElementNameWithParents(
                $data_el,
                false,
                $this->current_elements[0]->getName()
            ));
        }
        $this->addColumn('');
    }

    /**
     * @param Button[]                  $update_buttons
     * @param Button[]                  $delete_buttons
     */
    public function parse(
        array $update_buttons,
        array $delete_buttons,
    ): void {
        $data = [];
        foreach ($this->current_elements as $element) {
            $res = [];
            foreach ($this->data_finder->getDataElements(
                $element,
                true
            ) as $data_el) {
                $res[] = $data_el->isScaffold() ?
                    '' :
                    $this->presenter->getDataValue($data_el->getData());
            }
            $appended_path = (clone $this->cmd_path)
                ->addMDIDFilter($element->getMDID());
            $action_buttons = [];
            if ($b = $delete_buttons[$appended_path->getPathAsString()] ?? null) {
                $action_buttons[] = $b;
            }
            if ($b = $update_buttons[$appended_path->getPathAsString()] ?? null) {
                $action_buttons[] = $b;
            }
            $dropdown = $this->factory->dropdown()->standard($action_buttons);
            $res['dropdown'] = $this->renderer->render($dropdown);

            $data[] = $res;
        }
        $this->setData($data);
    }

    protected function fillRow(array $a_set): void
    {
        foreach ($a_set as $key => $item) {
            if ($key === 'dropdown') {
                continue;
            }
            $this->tpl->setCurrentBlock('data_column');
            $this->tpl->setVariable('COLUMN_VAL', $item);
            $this->tpl->parse('data_column');
        }
        $this->tpl->setCurrentBlock('action_column');
        $this->tpl->setVariable('ACTION_HTML', $a_set['dropdown']);
        $this->tpl->parse('action_column');
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @return ilMDElement[]
     */
    protected function getElementsByPath(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): array {
        $els = $root->getSubElementsByPath($path);
        $res = [];
        foreach ($els as $el) {
            if (!$el->isScaffold()) {
                $res[] = $el;
            }
        }
        if (count($res) < 1) {
            throw new ilMDGUIException(
                'The path to the current' .
                ' element does not lead to an element.'
            );
        }
        return $res;
    }
}
