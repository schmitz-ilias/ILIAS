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
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\HTTP\GlobalHttpState;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\GlobalScreen\Services as GlobalScreen;
use ILIAS\Data\Factory as Data;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use classes\Elements\Data\ilMDLOMDataFactory;
use Validation\ilMDLOMDataConstraintProvider;
use classes\Elements\Markers\ilMDMarkerFactory;

/**
 * Meta Data editor
 * @author       Stefan Meyer <smeyer.ilias@gmx.de>
 * @ilCtrl_Calls ilMDEditorGUI: ilFormPropertyDispatchGUI
 */
class ilMDEditorGUI
{
    public const MD_SET = 'md_set';
    public const MD_LINK = 'md_link';
    public const MD_NODE_PATH = 'node_path';
    public const MD_ACTION_PATH = 'action_path';

    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    protected ilTabsGUI $tabs_gui;
    protected Factory $ui_factory;
    protected Renderer $ui_renderer;
    protected ilToolbarGUI $toolbarGUI;
    protected GlobalHttpState $http;
    protected Refinery $refinery;
    protected GlobalScreen $global_screen;

    protected ilMDLOMDatabaseRepository $repo;
    protected ilMDPathFactory $path_factory;
    protected ilMDMarkerFactory $marker_factory;
    protected ilMDLOMLibrary $library;
    protected ilMDLOMPresenter $presenter;
    protected ilRbacSystem $rbac_system;
    protected Data $data;

    protected array $observers = [];

    protected int $rbac_id;
    protected int $obj_id;
    public string $obj_type;

    public function __construct(int $a_rbac_id, int $a_obj_id, string $a_obj_type)
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs_gui = $DIC->tabs();
        $this->ctrl = $DIC->ctrl();
        $this->toolbarGUI = $DIC->toolbar();
        $this->global_screen = $DIC->globalScreen();

        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();

        $this->rbac_id = $a_rbac_id;
        $this->obj_id = $a_obj_id;
        $this->obj_type = $a_obj_type;

        $this->repo = new ilMDLOMDatabaseRepository(
            $a_rbac_id,
            $a_obj_id === 0 ? $a_rbac_id : $a_obj_id,
            $a_obj_type
        );
        $this->path_factory = new ilMDPathFactory();
        $data_factory = new ilMDLOMDataFactory(
            new ilMDLOMDataConstraintProvider($this->refinery)
        );
        $this->marker_factory = new ilMDMarkerFactory($data_factory);
        $this->library = new ilMDLOMLibrary(new ilMDTagFactory());
        $this->data = new Data();
        $this->presenter = new ilMDLOMPresenter(
            $this->lng,
            $DIC->user(),
            $this->data->dateFormat(),
            $this->library->getLOMDictionary()
        );
        $this->rbac_system = $DIC->rbac()->system();

        $this->lng->loadLanguageModule('meta');
    }

    public function executeCommand(): void
    {
        $next_class = $this->ctrl->getNextClass($this);

        $cmd = $this->ctrl->getCmd();
        switch ($next_class) {
            default:
                if (!$cmd) {
                    $cmd = "listQuickEdit";
                }
                $this->$cmd();
                break;
        }
    }

    public function debug(): bool
    {
        $xml_writer = new ilMD2XML($this->rbac_id, $this->obj_id, $this->obj_type);
        $xml_writer->startExport();

        $button = $this->renderButtonToFullEditor();

        $this->tpl->setContent($button . htmlentities($xml_writer->getXML()));
        return true;
    }

    public function listSection(): void
    {
        $this->listQuickEdit();
    }

    public function listQuickEdit(?Request $request = null): void
    {
        $button = $this->renderButtonToFullEditor();

        $digest = $this->getLOMDigest();
        $root = $this->repo->getMD();
        $modal_content = '';
        $modal_signal = null;
        $link = $this->ctrl->getLinkTarget($this, 'updateQuickEdit');
        if ($modal = $digest->prepareChangeCopyrightModal($link)) {
            $modal_content = $this->ui_renderer->render($modal);
            $modal_signal = $modal->getShowSignal();
            $this->tpl->addJavaScript(
                'Services/MetaData/js/ilMetaCopyrightListener.js'
            );
        }

        $this->tpl->setContent(
            $button .
            $modal_content .
            $this->ui_renderer->render(
                $digest->getForm($root, $link, $request, $modal_signal)
            )
        );
    }

    public function updateQuickEdit(): void
    {
        $this->checkAccess();

        $digest = $this->getLOMDigest();
        $root = $this->repo->getMD();
        $link = $this->ctrl->getLinkTarget($this, 'updateQuickEdit');
        $request = $this->http->request();
        if (!$digest->update($root, $request)) {
            $this->tpl->setOnScreenMessage(
                'failure',
                $this->lng->txt('msg_form_save_error'),
                true
            );
            $this->listQuickEdit($request);
            return;
        }

        $this->callListeners('General');
        $this->callListeners('Rights');
        $this->callListeners('Educational');
        $this->callListeners('Lifecycle');

        // Redirect here to read new title and description
        $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
        $this->ctrl->redirect($this, 'listQuickEdit');
    }

    protected function getLOMDigest(): ilMDLOMDigestGUI
    {
        return new ilMDLOMDigestGUI(
            $this->repo,
            $this->path_factory,
            $this->marker_factory,
            $this->library,
            $this->ui_factory,
            $this->refinery,
            $this->lng
        );
    }

    protected function getFullEditor(): ilMDFullEditorGUI
    {
        return new ilMDFullEditorGUI(
            $this->repo,
            $this->path_factory,
            $this->library,
            $this->ui_factory,
            $this->presenter,
            new ilMDFullEditorUtilitiesCollection(
                $this->data->uri(
                    ILIAS_HTTP_PATH . '/' .
                    $this->ctrl->getLinkTarget($this, 'fullEditor')
                ),
                $this->ui_factory,
                $this->ui_renderer,
                $this->refinery,
                $this->repo,
                $this->presenter,
                $this->library,
                $this->path_factory,
                $this->marker_factory
            )
        );
    }

    protected function fullEditorCreate(): void
    {
        $this->fullEditorEdit(true);
    }

    protected function fullEditorUpdate(): void
    {
        $this->fullEditorEdit(false);
    }

    protected function fullEditorEdit(bool $create): void
    {
        $this->checkAccess();

        // get the paths from the http request
        $node_path = $this->getNodePathFromRequest();
        $update_path = $this->getActionPathFromRequest();

        // get and prepare the MD
        $root = $this->repo->getMD();
        $editor = $this->getFullEditor();
        $root = $editor->manipulateMD()->prepare($root, $node_path);

        // update or create
        $request = $this->http->request();
        if ($create) {
            $success = $editor->manipulateMD()->create(
                $root,
                $node_path,
                $update_path,
                $request
            );
        } else {
            $success = $editor->manipulateMD()->update(
                $root,
                $node_path,
                $update_path,
                $request
            );
        }
        if (!$success) {
            if (
                $editor->decideContentType($root, $node_path) ===
                ilMDFullEditorGUI::FORM
            ) {
                $this->tpl->setOnScreenMessage(
                    'failure',
                    $this->lng->txt('msg_form_save_error'),
                    true
                );
            }
            $this->fullEditor($request, $update_path);
            return;
        }

        // call listeners
        $this->callListenersFullEditor($update_path);

        // redirect back to the full editor
        $this->tpl->setOnScreenMessage(
            'success',
            $this->lng->txt(
                $create ?
                    'meta_add_element_success' :
                    'meta_edit_element_success'
            ),
            true
        );
        $this->ctrl->setParameter(
            $this,
            self::MD_NODE_PATH,
            $node_path->getPathAsString()
        );
        $this->ctrl->redirect($this, 'fullEditor');
    }

    protected function fullEditorDelete(): void
    {
        $this->checkAccess();

        // get the paths from the http request
        $node_path = $this->getNodePathFromRequest();
        $delete_path = $this->getActionPathFromRequest();

        // get the MD
        $editor = $this->getFullEditor();
        $root = $this->repo->getMD();

        // delete
        $node_path = $editor->manipulateMD()->deleteAndTrimNodePath(
            $root,
            $node_path,
            $delete_path
        );

        // call listeners
        $this->callListenersFullEditor($delete_path);

        // redirect back to the full editor
        $this->tpl->setOnScreenMessage(
            'success',
            $this->lng->txt('meta_delete_element_success'),
            true
        );
        $this->ctrl->setParameter(
            $this,
            self::MD_NODE_PATH,
            $node_path->getPathAsString()
        );
        $this->ctrl->redirect($this, 'fullEditor');
    }

    protected function fullEditor(
        ?Request $request = null,
        ?ilMDPathFromRoot $path_for_request = null
    ): void {
        $this->setTabsForFullEditor();

        // get the MD
        $root = $this->repo->getMD();

        // add slate with tree
        $this->global_screen->tool()->context()->current()->addAdditionalData(
            self::MD_SET,
            $root
        );
        $this->global_screen->tool()->context()->current()->addAdditionalData(
            self::MD_LINK,
            $this->data->uri(
                ILIAS_HTTP_PATH . '/' .
                $this->ctrl->getLinkTarget($this, 'fullEditor')
            )
        );

        // prepare MD by adding scaffolds
        $editor = $this->getFullEditor();
        $path = $this->getNodePathFromRequest();
        $root = $editor->manipulateMD()->prepare($root, $path);

        // add content for element
        $create_modals = $editor->getCreateModals(
            $root,
            $path,
            $request,
            $path_for_request
        );
        $create_signals = array_map(
            fn ($arg) => $arg->getFlexibleSignal(),
            $create_modals
        );
        $update_modals = $editor->getUpdateModals(
            $root,
            $path,
            $request,
            $path_for_request
        );
        $update_signals = array_map(
            fn ($arg) => $arg->getFlexibleSignal(),
            $update_modals
        );
        $delete_modals = $editor->getDeleteModals($root, $path);
        $delete_signals = array_map(
            fn ($arg) => $arg->getFlexibleSignal(),
            $delete_modals
        );
        $content = $editor->getContent(
            $root,
            $path,
            $create_signals,
            $update_signals,
            $delete_signals
        );
        if ($content instanceof ilTable2GUI) {
            $content = $this->ui_factory->legacy(
                $content->getHTML()
            );
        }
        if ($request && $content instanceof StandardForm) {
            $content = $content->withRequest($request);
        }
        if ($tb_content = $editor->getToolbarContent(
            $root,
            $path,
            $create_signals,
            $update_signals,
            $delete_signals
        )) {
            $this->toolbarGUI->addComponent($tb_content);
        }
        $this->tpl->setContent(
            $this->ui_renderer->render(
                array_merge(
                    [$content],
                    array_filter(array_values(array_map(
                        fn ($arg) => $arg->getModal(),
                        $create_modals
                    ))),
                    array_filter(array_values(array_map(
                        fn ($arg) => $arg->getModal(),
                        $update_modals
                    ))),
                    array_filter(array_values(array_map(
                        fn ($arg) => $arg->getModal(),
                        $delete_modals
                    )))
                )
            )
        );
    }

    protected function setTabsForFullEditor(): void
    {
        $this->tabs_gui->clearSubTabs();
        foreach ($this->tabs_gui->target as $tab) {
            if (($tab['id'] ?? null) !== $this->tabs_gui->getActiveTab()) {
                $this->tabs_gui->removeTab($tab['id']);
            }
        }
        $this->tabs_gui->removeNonTabbedLinks();
        $this->tabs_gui->setBackTarget(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'listQuickEdit')
        );
    }

    protected function getNodePathFromRequest(): ilMDPathFromRoot
    {
        return $this->getPathFromRequest(self::MD_NODE_PATH);
    }

    protected function getActionPathFromRequest(): ilMDPathFromRoot
    {
        return $this->getPathFromRequest(self::MD_ACTION_PATH);
    }

    protected function getPathFromRequest(string $key): ilMDPathFromRoot
    {
        $request_wrapper = $this->http->wrapper()->query();
        $path = $this->path_factory->getPathFromRoot();
        if ($request_wrapper->has($key)) {
            $path_string = $request_wrapper->retrieve(
                $key,
                $this->refinery->kindlyTo()->string()
            );
            $path->setPathFromString($path_string);
        }
        return $path;
    }

    protected function callListenersFullEditor(
        ilMDPathFromRoot $action_path
    ): void {
        switch ($action_path->getStep(1)) {
            case 'general':
                $this->callListeners('General');
                break;

            case 'lifeCycle':
                $this->callListeners('Lifecycle');
                break;

            case 'metaMetadata':
                $this->callListeners('MetaMetaData');
                break;

            case 'technical':
                $this->callListeners('Technical');
                break;

            case 'educational':
                $this->callListeners('Educational');
                break;

            case 'rights':
                $this->callListeners('Rights');
                break;

            case 'relation':
                $this->callListeners('Relation');
                break;

            case 'annotation':
                $this->callListeners('Annotation');
                break;

            case 'classification':
                $this->callListeners('Classification');
                break;
        }
    }

    protected function renderButtonToFullEditor(): string
    {
        $bulky = $this->ui_factory->button()->bulky(
            $this->ui_factory->symbol()->icon()->standard(
                'mds',
                $this->lng->txt('meta_button_to_full_editor_label'),
                'medium'
            ),
            $this->lng->txt('meta_button_to_full_editor_label'),
            $this->ctrl->getLinkTarget($this, 'fullEditor')
        );
        if (DEVMODE) {
            $debug = $this->ui_factory->button()->bulky(
                $this->ui_factory->symbol()->icon()->standard(
                    'adm',
                    'Debug'
                ),
                'Debug',
                $this->ctrl->getLinkTarget($this, 'debug')
            );
        }
        return  $this->ui_renderer->render($bulky) .
            (isset($debug) ? '</p>' . $this->ui_renderer->render($debug) : '');
    }

    protected function checkAccess(): void
    {
        $ref_ids = ilObject::_getAllReferences($this->rbac_id);
        foreach ($ref_ids as $ref_id) {
            if ($this->rbac_system->checkAccess('write', $ref_id)) {
                return;
            }
        }
        throw new ilPermissionException($this->lng->txt('permission_denied'));
    }

    // Observer methods
    public function addObserver(object $a_class, string $a_method, string $a_element): bool
    {
        $this->observers[$a_element]['class'] = $a_class;
        $this->observers[$a_element]['method'] = $a_method;

        return true;
    }

    /**
     * @return mixed
     */
    public function callListeners(string $a_element)
    {
        if (isset($this->observers[$a_element])) {
            $class = &$this->observers[$a_element]['class'];
            $method = $this->observers[$a_element]['method'];

            return $class->$method($a_element);
        }
        return '';
    }
}
