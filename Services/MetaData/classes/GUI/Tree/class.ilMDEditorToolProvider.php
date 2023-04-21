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

use ILIAS\GlobalScreen\Scope\Tool\Provider\AbstractDynamicToolProvider;
use ILIAS\GlobalScreen\Scope\Tool\Factory\Tool;
use ILIAS\UI\Component\Tree\Tree;
use ILIAS\UI\Component\MainControls\Slate\Legacy as LegacySlate;
use ILIAS\UI\Component\Component;
use ILIAS\GlobalScreen\ScreenContext\Stack\ContextCollection;
use ILIAS\GlobalScreen\ScreenContext\Stack\CalledContexts;
use ILIAS\Data\URI;
use ILIAS\Data\Factory as Data;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDEditorToolProvider extends AbstractDynamicToolProvider
{
    use Hasher;
    public function isInterestedInContexts(): ContextCollection
    {
        return $this->context_collection->repository();
    }

    /**
     * @param CalledContexts $called_contexts
     * @return Tool[]
     */
    public function getToolsForContextStack(
        CalledContexts $called_contexts
    ): array {
        $last_context = $called_contexts->getLast();

        if ($last_context) {
            $additional_data = $last_context->getAdditionalData();
            if (
                $additional_data->exists(ilMDEditorGUI::MD_SET) &&
                $additional_data->exists(ilMDEditorGUI::MD_LINK)
            ) {
                return [$this->buildTreeAsTool(
                    $additional_data->get(ilMDEditorGUI::MD_LINK),
                    $additional_data->get(ilMDEditorGUI::MD_SET)
                )];
            }
        }

        return [];
    }

    protected function buildTreeAsTool(URI $link, ilMDRootElement $root): Tool
    {
        $id_generator = function ($id) {
            return $this->identification_provider->contextAwareIdentifier($id);
        };
        $identification = $id_generator('system_styles_tree');
        $hashed = $this->hash($identification->serialize());

        $lng = $this->dic->language();
        $lng->loadLanguageModule('meta');

        return $this->factory
            ->tool($identification)
            ->withTitle(
                $lng->txt('meta_lom_short')
            )
            ->withSymbol(
                $this->dic->ui()->factory()->symbol()->icon()->standard(
                    'mds',
                    $lng->txt('meta_lom_short')
                )
            )
            ->withContent($this->dic->ui()->factory()->legacy(
                $this->dic->ui()->renderer()->render($this->getUITree(
                    $link,
                    $root
                ))
            ))
            ->addComponentDecorator(static function (Component $c) use ($hashed): Component {
                if ($c instanceof LegacySlate) {
                    $signal_id = $c->getToggleSignal()->getId();
                    return $c->withAdditionalOnLoadCode(static function ($id) use ($hashed) {
                        return "il.UI.maincontrols.mainbar.engageTool('$hashed');";
                    });
                }
                return $c;
            });
    }

    protected function getUITree(URI $link, ilMDRootElement $root): Tree
    {
        $path_factory = new ilMDPathFactory();

        $refinery = $this->dic->refinery();
        $request_wrapper = $this->dic->http()->wrapper()->query();
        $path_to_current_element = $path_factory->getPathFromRoot();
        if ($request_wrapper->has(ilMDEditorGUI::MD_NODE_PATH)) {
            $current_path_string = $request_wrapper->retrieve(
                ilMDEditorGUI::MD_NODE_PATH,
                $refinery->kindlyTo()->string()
            );
            $path_to_current_element->setPathFromString($current_path_string);
        }

        $library = new ilMDLOMLibrary(new ilMDTagFactory());
        $structure = $library
            ->getLOMEditorGUIDictionary($path_factory)
            ->getStructure();

        $recursion = new ilMDEditorTreeRecursion(
            $link,
            $root,
            $path_to_current_element,
            $path_factory,
            new ilMDLOMPresenter(
                $this->dic->language(),
                $this->dic->user(),
                (new Data())->dateFormat(),
                $library->getLOMDictionary()
            )
        );
        $f = $this->dic->ui()->factory();

        return $f->tree()
                 ->expandable('MD Editor Tree', $recursion)
                 ->withData([$root])
                 ->withEnvironment($structure)
                 ->withHighlightOnNodeClick(true);
    }
}
