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

namespace ILIAS\MetaData\Editor\Full\Services\Tables;

use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Renderer;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Editor\Full\Services\DataFinder;
use ILIAS\MetaData\Elements\Data\Type;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactory;
use ILIAS\MetaData\Elements\ElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class TableGUI extends \ilTable2GUI
{
    protected UIFactory $ui_factory;
    protected Renderer $renderer;
    protected PresenterInterface $presenter;
    protected DataFinder $data_finder;
    protected PathFactory $path_factory;

    /**
     * @var ElementInterface[]
     */
    protected array $elements;

    public function __construct(
        ?object $parent_obj,
        UIFactory $ui_factory,
        Renderer $renderer,
        PresenterInterface $presenter,
        DataFinder $data_finder,
        PathFactory $path_factory,
        ElementInterface ...$elements
    ) {
        parent::__construct($parent_obj);
        $this->elements = $elements;

        $this->ui_factory = $ui_factory;
        $this->renderer = $renderer;
        $this->presenter = $presenter;
        $this->data_finder = $data_finder;
        $this->path_factory = $path_factory;
    }

    public function init(): void
    {
        $this->setRowTemplate(
            'tpl.full_editor_row.html',
            'Services/MetaData'
        );
        $this->setTitle($this->presenter->elements()->nameWithParents(
            $this->elements[0],
            null,
            true
        ));
        $this->setExternalSegmentation(true);

        foreach ($this->data_finder->getDataCarryingElements(
            $this->elements[0],
            true
        ) as $data_el) {
            $this->addColumn($this->presenter->elements()->nameWithParents(
                $data_el,
                $this->elements[0],
                false
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
        foreach ($this->elements as $element) {
            $res = [];
            foreach ($this->data_finder->getDataCarryingElements(
                $element,
                true
            ) as $data_el) {
                $res[] = $data_el->getData()->type() === Type::NULL ?
                    '' :
                    $this->presenter->data()->dataValue($data_el->getData());
            }
            $action_path_string = $this->path_factory->toElement($element, true)
                                                     ->toString();
            $action_buttons = [];
            if ($b = $delete_buttons[$action_path_string] ?? null) {
                $action_buttons[] = $b;
            }
            if ($b = $update_buttons[$action_path_string] ?? null) {
                $action_buttons[] = $b;
            }
            $dropdown = $this->ui_factory->dropdown()->standard($action_buttons);
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
}
