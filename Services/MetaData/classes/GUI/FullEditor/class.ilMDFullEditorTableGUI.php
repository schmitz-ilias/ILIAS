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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorTableGUI extends ilTable2GUI
{
    protected ilMDPathFromRoot $cmd_path;
    protected ilMDRootElement $root;

    /**
     * @var ilMDElement[]
     */
    protected array $current_elements;

    public function __construct(
        ?object $parent_obj,
        ilMDRootElement $root,
        ilMDPathFromRoot $cmd_path,
    ) {
        parent::__construct($parent_obj);
        $this->cmd_path = $cmd_path;
        if (count($els = $root->getSubElementsByPath($cmd_path)) < 1) {
            throw new ilMDGUIException(
                'The path to the current' .
                ' element does not lead to an element.'
            );
        }
        $this->root = $root;
        $this->current_elements = $root->getSubElementsByPath($cmd_path);
    }

    public function init(
        ilMDLOMStructure $structure,
        ilMDLOMPresenter $presenter,
        ilMDDataGUIUtilities $finder
    ): void {
        $this->setRowTemplate(
            'tpl.full_editor_row.html',
            'Services/MetaData'
        );
        $this->setTitle($presenter->getElementNameWithParents(
            $this->current_elements[0],
            true
        ));

        foreach ($finder->getDataElements(
            $this->current_elements[0],
            $structure
        ) as $data_el) {
            $this->addColumn($presenter->getElementNameWithParents(
                $data_el,
                false,
                $this->current_elements[0]->getName()
            ));
        }
        $this->addColumn('');
    }

    public function parse(
        ilMDLOMStructure $structure,
        ilMDLOMPresenter $presenter,
        ilMDDataGUIUtilities $finder,
        Factory $factory,
        Renderer $renderer
    ): void {
        $data = [];
        foreach ($this->current_elements as $element) {
            $res = [];
            foreach ($finder->getDataElements(
                $element,
                $structure
            ) as $data_el) {
                $res[] = $data_el->isScaffold() ?
                    '' :
                    $presenter->getDataValue($data_el->getData());
            }
            $dropdown = $factory->dropdown()->standard(
                [
                    $factory->button()->shy('edit', '#'),
                    $factory->button()->shy('delete', '#')
                ]
            );
            $res['dropdown'] = $renderer->render($dropdown);

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
}
