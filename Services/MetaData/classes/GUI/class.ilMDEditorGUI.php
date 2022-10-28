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
use ILIAS\UI\Component\Modal\Interruptive;
use ILIAS\UI\Component\Signal;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\HTTP\GlobalHttpState;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\GlobalScreen\Services as GlobalScreen;
use ILIAS\Data\URI;
use ILIAS\Data\Factory as Data;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;

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
    protected ilMDLOMDataFactory $data_factory;
    protected ilMDMarkerFactory $marker_factory;
    protected ilMDLOMLibrary $library;
    protected ilMDLOMPresenter $presenter;
    protected ilObjUser $user;
    protected Data $data;

    /**
     * @var ilMDTechnical|ilMDGeneral|ilMDLifecycle|ilMDEducational|ilMDRights|ilMDMetaMetadata|ilMDRelation|ilMDAnnotation|ilMDClassification $md_section
     */
    protected ?object $md_section = null;
    protected ?ilPropertyFormGUI $form = null;

    protected ilMD $md_obj;

    protected array $observers = [];

    protected int $rbac_id;
    protected int $obj_id;
    protected string $obj_type;

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

        $this->md_obj = new ilMD($a_rbac_id, $a_obj_id, $a_obj_type);

        $this->repo = new ilMDLOMDatabaseRepository(
            $a_rbac_id,
            $a_obj_id === 0 ? $a_rbac_id : $a_obj_id,
            $a_obj_type
        );
        $this->path_factory = new ilMDPathFactory();
        $this->data_factory = new ilMDLOMDataFactory($this->refinery);
        $this->marker_factory = new ilMDMarkerFactory($this->data_factory);
        $this->library = new ilMDLOMLibrary(new ilMDTagFactory());
        $this->presenter = new ilMDLOMPresenter(
            $this->lng,
            $DIC->user(),
            $this->library->getLOMDictionary()
        );
        $this->user = $DIC->user();
        $this->data = new Data();

        $this->lng->loadLanguageModule('meta');
    }

    protected function initMetaIndexFromQuery(): int
    {
        $meta_index = 0;
        if ($this->http->wrapper()->query()->has('meta_index')) {
            $meta_index = $this->http->wrapper()->query()->retrieve(
                'meta_index',
                $this->refinery->kindlyTo()->int()
            );
        }
        return $meta_index;
    }

    protected function initSectionFromQuery(): string
    {
        $section = '';
        if ($this->http->wrapper()->query()->has('section')) {
            $section = $this->http->wrapper()->query()->retrieve(
                'section',
                $this->refinery->kindlyTo()->string()
            );
        }

        return $section;
    }

    public function executeCommand(): void
    {
        $next_class = $this->ctrl->getNextClass($this);

        $cmd = $this->ctrl->getCmd();
        switch ($next_class) {
            default:
                if (!$cmd) {
                    $cmd = "listSection";
                }
                $this->$cmd();
                break;
        }
    }

    public function debug(): bool
    {
        $xml_writer = new ilMD2XML($this->md_obj->getRBACId(), $this->md_obj->getObjId(), $this->md_obj->getObjType());
        $xml_writer->startExport();

        $this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.md_editor.html', 'Services/MetaData');

        $button = $this->renderButtonToFullEditor();

        $this->tpl->setVariable("MD_CONTENT", htmlentities($xml_writer->getXML()));
        return true;
    }

    /**
     * @deprecated with release 5_3
     */
    public function listQuickEdit_scorm(): void
    {
        if (!is_object($this->md_section = $this->md_obj->getGeneral())) {
            $this->md_section = $this->md_obj->addGeneral();
            $this->md_section->save();
        }

        $this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.md_editor.html', 'Services/MetaData');

        $button = $this->renderButtonToFullEditor();

        $this->tpl->addBlockFile('MD_CONTENT', 'md_content', 'tpl.md_quick_edit_scorm.html', 'Services/MetaData');

        $this->tpl->setVariable("BUTTON", $button . '</p>');

        $this->ctrl->setReturn($this, 'listGeneral');
        $this->ctrl->setParameter($this, 'section', 'meta_general');
        $this->tpl->setVariable("EDIT_ACTION", $this->ctrl->getFormAction($this));

        $this->tpl->setVariable("TXT_QUICK_EDIT", $this->lng->txt("meta_quickedit"));
        $this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("meta_language"));
        $this->tpl->setVariable("TXT_KEYWORD", $this->lng->txt("meta_keyword"));
        $this->tpl->setVariable("TXT_DESCRIPTION", $this->lng->txt("meta_description"));
        $this->tpl->setVariable("TXT_PLEASE_SELECT", $this->lng->txt("meta_please_select"));

        // Language
        $first = true;
        foreach ($ids = $this->md_section->getLanguageIds() as $id) {
            $md_lan = $this->md_section->getLanguage($id);

            if ($first) {
                $this->tpl->setCurrentBlock("language_head");
                $this->tpl->setVariable("ROWSPAN_LANG", count($ids));
                $this->tpl->setVariable("LANGUAGE_LOOP_TXT_LANGUAGE", $this->lng->txt("meta_language"));
                $this->tpl->parseCurrentBlock();
                $first = false;
            }

            if (count($ids) > 1) {
                $this->ctrl->setParameter($this, 'meta_index', $id);
                $this->ctrl->setParameter($this, 'meta_path', 'meta_language');

                $this->tpl->setCurrentBlock("language_delete");
                $this->tpl->setVariable(
                    "LANGUAGE_LOOP_ACTION_DELETE",
                    $this->ctrl->getLinkTarget($this, 'deleteElement')
                );
                $this->tpl->setVariable("LANGUAGE_LOOP_TXT_DELETE", $this->lng->txt("meta_delete"));
                $this->tpl->parseCurrentBlock();
            }
            $this->tpl->setCurrentBlock("language_loop");
            $this->tpl->setVariable("LANGUAGE_LOOP_VAL_LANGUAGE", $this->__showLanguageSelect(
                'gen_language[' . $id . '][language]',
                $md_lan->getLanguageCode()
            ));
            $this->tpl->parseCurrentBlock();
        }

        if ($first) {
            $this->tpl->setCurrentBlock("language_head");
            $this->tpl->setVariable("ROWSPAN_LANG", 1);
            $this->tpl->setVariable("LANGUAGE_LOOP_TXT_LANGUAGE", $this->lng->txt("meta_language"));
            $this->tpl->parseCurrentBlock();
            $this->tpl->setCurrentBlock("language_loop");
            $this->tpl->setVariable("LANGUAGE_LOOP_VAL_LANGUAGE", $this->__showLanguageSelect(
                'gen_language[][language]',
                ""
            ));
            $this->tpl->parseCurrentBlock();
        }

        // TITLE
        $this->tpl->setVariable("TXT_TITLE", $this->lng->txt('title'));
        $this->tpl->setVariable(
            "VAL_TITLE",
            ilLegacyFormElementsUtil::prepareFormOutput($this->md_section->getTitle())
        );
        $this->tpl->setVariable("VAL_TITLE_LANGUAGE", $this->__showLanguageSelect(
            'gen_title_language',
            $this->md_section->getTitleLanguageCode()
        ));

        // DESCRIPTION
        foreach ($ids = $this->md_section->getDescriptionIds() as $id) {
            $md_des = $this->md_section->getDescription($id);

            if (count($ids) > 1) {
                $this->ctrl->setParameter($this, 'meta_index', $id);
                $this->ctrl->setParameter($this, 'meta_path', 'meta_description');

                $this->tpl->setCurrentBlock("description_delete");
                $this->tpl->setVariable(
                    "DESCRIPTION_LOOP_ACTION_DELETE",
                    $this->ctrl->getLinkTarget($this, 'deleteElement')
                );
                $this->tpl->setVariable("DESCRIPTION_LOOP_TXT_DELETE", $this->lng->txt("meta_delete"));
                $this->tpl->parseCurrentBlock();
            }

            $this->tpl->setCurrentBlock("description_loop");
            $this->tpl->setVariable("DESCRIPTION_LOOP_NO", $id);
            $this->tpl->setVariable("DESCRIPTION_LOOP_TXT_DESCRIPTION", $this->lng->txt("meta_description"));
            $this->tpl->setVariable("DESCRIPTION_LOOP_TXT_VALUE", $this->lng->txt("meta_value"));
            $this->tpl->setVariable(
                "DESCRIPTION_LOOP_VAL",
                ilLegacyFormElementsUtil::prepareFormOutput($md_des->getDescription())
            );
            $this->tpl->setVariable("DESCRIPTION_LOOP_TXT_LANGUAGE", $this->lng->txt("meta_language"));
            $this->tpl->setVariable("DESCRIPTION_LOOP_VAL_LANGUAGE", $this->__showLanguageSelect(
                "gen_description[" . $id . '][language]',
                $md_des->getDescriptionLanguageCode()
            ));
            $this->tpl->parseCurrentBlock();
        }

        // KEYWORD
        $first = true;
        $keywords = array();
        foreach ($ids = $this->md_section->getKeywordIds() as $id) {
            $md_key = $this->md_section->getKeyword($id);
            $keywords[$md_key->getKeywordLanguageCode()][]
                = $md_key->getKeyword();
        }

        $lang = '';
        foreach ($keywords as $lang => $keyword_set) {
            if ($first) {
                $this->tpl->setCurrentBlock("keyword_head");
                $this->tpl->setVariable("ROWSPAN_KEYWORD", count($keywords));
                $this->tpl->setVariable("TXT_COMMA_SEP2", $this->lng->txt('comma_separated'));
                $this->tpl->setVariable("KEYWORD_LOOP_TXT_KEYWORD", $this->lng->txt("keywords"));
                $this->tpl->parseCurrentBlock();
                $first = false;
            }

            $this->tpl->setCurrentBlock("keyword_loop");
            $this->tpl->setVariable(
                "KEYWORD_LOOP_VAL",
                ilLegacyFormElementsUtil::prepareFormOutput(
                    implode(", ", $keyword_set)
                )
            );
            $this->tpl->setVariable("LANG", $lang);
            $this->tpl->setVariable("KEYWORD_LOOP_VAL_LANGUAGE", $this->__showLanguageSelect(
                "keyword[language][$lang]",
                $lang
            ));
            $this->tpl->parseCurrentBlock();
        }

        if ($keywords === []) {
            $this->tpl->setCurrentBlock("keyword_head");
            $this->tpl->setVariable("ROWSPAN_KEYWORD", 1);
            $this->tpl->setVariable("TXT_COMMA_SEP2", $this->lng->txt('comma_separated'));
            $this->tpl->setVariable("KEYWORD_LOOP_TXT_KEYWORD", $this->lng->txt("keywords"));
            $this->tpl->parseCurrentBlock();
            $this->tpl->setCurrentBlock("keyword_loop");
            $this->tpl->setVariable("KEYWORD_LOOP_VAL_LANGUAGE", $this->__showLanguageSelect(
                "keyword[language][$lang]",
                $lang
            ));
        }

        // Lifecycle...
        // experts
        $this->tpl->setVariable("TXT_EXPERTS", $this->lng->txt('meta_subjectmatterexpert'));
        $this->tpl->setVariable("TXT_COMMA_SEP", $this->lng->txt('comma_separated'));
        $this->tpl->setVariable("TXT_SCOPROP_EXPERT", $this->lng->txt('sco_propagate'));
        if (is_object($this->md_section = $this->md_obj->getLifecycle())) {
            $sep = $ent_str = "";
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "SubjectMatterExpert") {
                    foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);
                        $ent_str .= $sep . $md_ent->getEntity();
                        $sep = ", ";
                    }
                }
            }
            $this->tpl->setVariable("EXPERTS_VAL", ilLegacyFormElementsUtil::prepareFormOutput($ent_str));
        }
        // InstructionalDesigner
        $this->tpl->setVariable("TXT_DESIGNERS", $this->lng->txt('meta_instructionaldesigner'));
        $this->tpl->setVariable("TXT_SCOPROP_DESIGNERS", $this->lng->txt('sco_propagate'));
        if (is_object($this->md_section = $this->md_obj->getLifecycle())) {
            $sep = $ent_str = "";
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "InstructionalDesigner") {
                    foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);
                        $ent_str .= $sep . $md_ent->getEntity();
                        $sep = ", ";
                    }
                }
            }
            $this->tpl->setVariable("DESIGNERS_VAL", ilLegacyFormElementsUtil::prepareFormOutput($ent_str));
        }
        // Point of Contact
        $this->tpl->setVariable("TXT_POC", $this->lng->txt('meta_pointofcontact'));
        $this->tpl->setVariable("TXT_SCOPROP_POC", $this->lng->txt('sco_propagate'));
        if (is_object($this->md_section = $this->md_obj->getLifecycle())) {
            $sep = $ent_str = "";
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "PointOfContact") {
                    foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);
                        $ent_str .= $sep . $md_ent->getEntity();
                        $sep = ", ";
                    }
                }
            }
            $this->tpl->setVariable("POC_VAL", ilLegacyFormElementsUtil::prepareFormOutput($ent_str));
        }

        $this->tpl->setVariable("TXT_STATUS", $this->lng->txt('meta_status'));
        if (!is_object($this->md_section = $this->md_obj->getLifecycle())) {
            $this->md_section = $this->md_obj->addLifecycle();
            $this->md_section->save();
        }
        if (is_object($this->md_section = $this->md_obj->getLifecycle())) {
            $this->tpl->setVariable("SEL_STATUS", ilMDUtilSelect::_getStatusSelect(
                $this->md_section->getStatus(),
                "lif_status",
                array(0 => $this->lng->txt('meta_please_select'))
            ));
        }

        // Rights...
        // Copyright
        // smeyer 2018-09-14 not supported

        $tlt = array(0, 0, 0, 0, 0);
        $valid = true;
        if (is_object($this->md_section = $this->md_obj->getEducational())) {
            if (!$tlt = ilMDUtils::_LOMDurationToArray($this->md_section->getTypicalLearningTime())) {
                if ($this->md_section->getTypicalLearningTime() !== '') {
                    $tlt = array(0, 0, 0, 0, 0);
                    $valid = false;
                }
            }
        }
        $this->tpl->setVariable("TXT_MONTH", $this->lng->txt('md_months'));
        $this->tpl->setVariable("SEL_MONTHS", $this->__buildMonthsSelect((string) ($tlt[0] ?? '')));
        $this->tpl->setVariable("SEL_DAYS", $this->__buildDaysSelect((string) ($tlt[1] ?? '')));

        $this->tpl->setVariable("TXT_DAYS", $this->lng->txt('md_days'));
        $this->tpl->setVariable("TXT_TIME", $this->lng->txt('md_time'));

        $this->tpl->setVariable("TXT_TYPICAL_LEARN_TIME", $this->lng->txt('meta_typical_learning_time'));
        $this->tpl->setVariable(
            "SEL_TLT",
            ilLegacyFormElementsUtil::makeTimeSelect(
                'tlt',
                !$tlt[4],
                $tlt[2],
                $tlt[3],
                $tlt[4],
                false
            )
        );
        $this->tpl->setVariable("TLT_HINT", $tlt[4] ? '(hh:mm:ss)' : '(hh:mm)');

        if (!$valid) {
            $this->tpl->setCurrentBlock("tlt_not_valid");
            $this->tpl->setVariable("TXT_CURRENT_VAL", $this->lng->txt('meta_current_value'));
            $this->tpl->setVariable("TLT", $this->md_section->getTypicalLearningTime());
            $this->tpl->setVariable("INFO_TLT_NOT_VALID", $this->lng->txt('meta_info_tlt_not_valid'));
            $this->tpl->parseCurrentBlock();
        }

        $this->tpl->setVariable("TXT_SAVE", $this->lng->txt('save'));
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
        // Otherwise ('Lifecycle' 'technical' ...) simply call listSection()
        $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
        $this->ctrl->redirect($this, 'listSection');
    }

    protected function getLOMDigest(): ilMDLOMDigestGUI
    {
        return new ilMDLOMDigestGUI(
            $this->repo,
            $this->path_factory,
            $this->data_factory,
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
                $this->data,
                $this->repo,
                $this->presenter,
                $this->library,
                $this->path_factory,
                $this->marker_factory,
                $this->data_factory
            )
        );
    }

    public function updateQuickEdit_scorm_propagate(string $request, string $type): void
    {
        $module_id = $this->md_obj->getObjId();
        if ($this->md_obj->getObjType() === 'sco') {
            $module_id = $this->md_obj->getRBACId();
        }
        $tree = new ilTree($module_id);
        $tree->setTableNames('sahs_sc13_tree', 'sahs_sc13_tree_node');
        $tree->setTreeTablePK("slm_id");

        $post = $this->http->request()->getParsedBody();
        foreach ($tree->getSubTree($tree->getNodeData($tree->getRootId()), true, ['sco']) as $sco) {
            $sco_md = new ilMD($module_id, $sco['obj_id'], 'sco');
            if ($post[$request] != "") {
                if (!is_object($sco_md_section = $sco_md->getLifecycle())) {
                    $sco_md_section = $sco_md->addLifecycle();
                    $sco_md_section->save();
                }
                // determine all entered authors
                $auth_arr = explode(",", $post[$request]);
                for ($i = 0, $iMax = count($auth_arr); $i < $iMax; $i++) {
                    $auth_arr[$i] = trim($auth_arr[$i]);
                }

                $md_con_author = "";

                // update existing author entries (delete if not entered)
                foreach (($ids = $sco_md_section->getContributeIds()) as $con_id) {
                    $md_con = $sco_md_section->getContribute($con_id);
                    if ($md_con->getRole() === $type) {
                        foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                            $md_ent = $md_con->getEntity($ent_id);

                            // entered author already exists
                            if (in_array($md_ent->getEntity(), $auth_arr, true)) {
                                unset($auth_arr[array_search($md_ent->getEntity(), $auth_arr, true)]);
                            } else {  // existing author has not been entered again -> delete
                                $md_ent->delete();
                            }
                        }
                        $md_con_author = $md_con;
                    }
                }

                // insert enterd, but not existing authors
                if (count($auth_arr) > 0) {
                    if (!is_object($md_con_author)) {
                        $md_con_author = $sco_md_section->addContribute();
                        $md_con_author->setRole($type);
                        $md_con_author->save();
                    }
                    foreach ($auth_arr as $auth) {
                        $md_ent = $md_con_author->addEntity();
                        $md_ent->setEntity(ilUtil::stripSlashes($auth));
                        $md_ent->save();
                    }
                }
            } elseif (is_object($sco_md_section = $sco_md->getLifecycle())) {
                foreach (($ids = $sco_md_section->getContributeIds()) as $con_id) {
                    $md_con = $sco_md_section->getContribute($con_id);
                    if ($md_con->getRole() === $type) {
                        $md_con->delete();
                    }
                }
            }
            $sco_md->update();
        }
        $this->updateQuickEdit_scorm();
    }

    public function updateQuickEdit_scorm_prop_expert(): void
    {
        $this->updateQuickEdit_scorm_propagate("life_experts", "SubjectMatterExpert");
    }

    public function updateQuickEdit_scorm_prop_designer(): void
    {
        $this->updateQuickEdit_scorm_propagate("life_designers", "InstructionalDesigner");
    }

    public function updateQuickEdit_scorm_prop_poc(): void
    {
        $this->updateQuickEdit_scorm_propagate("life_poc", "PointOfContact");
    }

    /**
     * @todo discuss with scorm maintainer how to proceed with this quick edit implementation
     */
    public function updateQuickEdit_scorm(): void
    {
        $post = $this->http->request()->getParsedBody();

        // General values
        $this->md_section = $this->md_obj->getGeneral();
        $this->md_section->setTitle(ilUtil::stripSlashes($post['gen_title'] ?? ''));
        $this->md_section->setTitleLanguage(new ilMDLanguageItem($post['gen_title_language'] ?? ''));
        $this->md_section->update();


        // Language
        if (is_array($post['gen_language'])) {
            foreach ($post['gen_language'] as $id => $data) {
                if ($id > 0) {
                    $md_lan = $this->md_section->getLanguage($id);
                    $md_lan->setLanguage(new ilMDLanguageItem($data['language']));
                    $md_lan->update();
                } else {
                    $md_lan = $this->md_section->addLanguage();
                    $md_lan->setLanguage(new ilMDLanguageItem($data['language']));
                    $md_lan->save();
                }
            }
        }
        // Description
        if (is_array($post['gen_description'])) {
            foreach ($post['gen_description'] as $id => $data) {
                $md_des = $this->md_section->getDescription($id);
                $md_des->setDescription(ilUtil::stripSlashes($data['description']));
                $md_des->setDescriptionLanguage(new ilMDLanguageItem($data['language']));
                $md_des->update();
            }
        }

        // Keyword
        if (is_array($post["keywords"]["value"])) {
            $new_keywords = array();
            foreach ($post["keywords"]["value"] as $lang => $keywords) {
                $language = $post["keyword"]["language"][$lang];
                $keywords = explode(",", $keywords);
                foreach ($keywords as $keyword) {
                    $new_keywords[$language][] = trim($keyword);
                }
            }

            // update existing author entries (delete if not entered)
            foreach ($ids = $this->md_section->getKeywordIds() as $id) {
                $md_key = $this->md_section->getKeyword($id);

                $lang = $md_key->getKeywordLanguageCode();

                // entered keyword already exists
                if (is_array($new_keywords[$lang]) &&
                    in_array($md_key->getKeyword(), $new_keywords[$lang], true)) {
                    unset($new_keywords[$lang][array_search($md_key->getKeyword(), $new_keywords[$lang], true)]);
                } else {  // existing keyword has not been entered again -> delete
                    $md_key->delete();
                }
            }

            // insert entered, but not existing keywords
            foreach ($new_keywords as $lang => $key_arr) {
                foreach ($key_arr as $keyword) {
                    if ($keyword !== "") {
                        $md_key = $this->md_section->addKeyword();
                        $md_key->setKeyword(ilUtil::stripSlashes($keyword));
                        $md_key->setKeywordLanguage(new ilMDLanguageItem($lang));
                        $md_key->save();
                    }
                }
            }
        }
        $this->callListeners('General');

        // Copyright
        if ($post['copyright_id'] or $post['rights_copyright']) {
            if (!is_object($this->md_section = $this->md_obj->getRights())) {
                $this->md_section = $this->md_obj->addRights();
                $this->md_section->save();
            }
            if ($post['copyright_id']) {
                $this->md_section->setCopyrightAndOtherRestrictions("Yes");
                $this->md_section->setDescription('il_copyright_entry__' . IL_INST_ID . '__' . (int) $post['copyright_id']);
            } else {
                $this->md_section->setCopyrightAndOtherRestrictions("Yes");
                $this->md_section->setDescription(ilUtil::stripSlashes($post["rights_copyright"]));
            }
            $this->md_section->update();
        } elseif (is_object($this->md_section = $this->md_obj->getRights())) {
            $this->md_section->setCopyrightAndOtherRestrictions("No");
            $this->md_section->setDescription("");
            $this->md_section->update();
        }
        $this->callListeners('Rights');

        //Educational...
        // Typical Learning Time
        if ($post['tlt']['mo'] or $post['tlt']['d'] or
            $post["tlt"]['h'] or $post['tlt']['m'] or $post['tlt']['s']) {
            if (!is_object($this->md_section = $this->md_obj->getEducational())) {
                $this->md_section = $this->md_obj->addEducational();
                $this->md_section->save();
            }
            $this->md_section->setPhysicalTypicalLearningTime(
                (int) $post['tlt']['mo'],
                (int) $post['tlt']['d'],
                (int) $post['tlt']['h'],
                (int) $post['tlt']['m'],
                (int) $post['tlt']['s']
            );
            $this->md_section->update();
        } elseif (is_object($this->md_section = $this->md_obj->getEducational())) {
            $this->md_section->setPhysicalTypicalLearningTime(0, 0, 0, 0, 0);
            $this->md_section->update();
        }
        $this->callListeners('Educational');
        //Lifecycle...
        // experts
        if ($post["life_experts"] != "") {
            if (!is_object($this->md_section = $this->md_obj->getLifecycle())) {
                $this->md_section = $this->md_obj->addLifecycle();
                $this->md_section->save();
            }

            // determine all entered authors
            $auth_arr = explode(",", $post["life_experts"]);
            for ($i = 0, $iMax = count($auth_arr); $i < $iMax; $i++) {
                $auth_arr[$i] = trim($auth_arr[$i]);
            }

            $md_con_author = "";

            // update existing author entries (delete if not entered)
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "SubjectMatterExpert") {
                    foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);

                        // entered author already exists
                        if (in_array($md_ent->getEntity(), $auth_arr, true)) {
                            unset($auth_arr[array_search($md_ent->getEntity(), $auth_arr, true)]);
                        } else {  // existing author has not been entered again -> delete
                            $md_ent->delete();
                        }
                    }
                    $md_con_author = $md_con;
                }
            }

            // insert enterd, but not existing authors
            if (count($auth_arr) > 0) {
                if (!is_object($md_con_author)) {
                    $md_con_author = $this->md_section->addContribute();
                    $md_con_author->setRole("SubjectMatterExpert");
                    $md_con_author->save();
                }
                foreach ($auth_arr as $auth) {
                    $md_ent = $md_con_author->addEntity();
                    $md_ent->setEntity(ilUtil::stripSlashes($auth));
                    $md_ent->save();
                }
            }
        } elseif (is_object($this->md_section = $this->md_obj->getLifecycle())) {
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "SubjectMatterExpert") {
                    $md_con->delete();
                }
            }
        }

        // InstructionalDesigner
        if ($post["life_designers"] != "") {
            if (!is_object($this->md_section = $this->md_obj->getLifecycle())) {
                $this->md_section = $this->md_obj->addLifecycle();
                $this->md_section->save();
            }

            // determine all entered authors
            $auth_arr = explode(",", $post["life_designers"]);
            for ($i = 0, $iMax = count($auth_arr); $i < $iMax; $i++) {
                $auth_arr[$i] = trim($auth_arr[$i]);
            }

            $md_con_author = "";

            // update existing author entries (delete if not entered)
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "InstructionalDesigner") {
                    foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);

                        // entered author already exists
                        if (in_array($md_ent->getEntity(), $auth_arr, true)) {
                            unset($auth_arr[array_search($md_ent->getEntity(), $auth_arr, true)]);
                        } else {  // existing author has not been entered again -> delete
                            $md_ent->delete();
                        }
                    }
                    $md_con_author = $md_con;
                }
            }

            // insert enterd, but not existing authors
            if (count($auth_arr) > 0) {
                if (!is_object($md_con_author)) {
                    $md_con_author = $this->md_section->addContribute();
                    $md_con_author->setRole("InstructionalDesigner");
                    $md_con_author->save();
                }
                foreach ($auth_arr as $auth) {
                    $md_ent = $md_con_author->addEntity();
                    $md_ent->setEntity(ilUtil::stripSlashes($auth));
                    $md_ent->save();
                }
            }
        } elseif (is_object($this->md_section = $this->md_obj->getLifecycle())) {
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "InstructionalDesigner") {
                    $md_con->delete();
                }
            }
        }

        // Point of Contact
        if ($post["life_poc"] != "") {
            if (!is_object($this->md_section = $this->md_obj->getLifecycle())) {
                $this->md_section = $this->md_obj->addLifecycle();
                $this->md_section->save();
            }

            // determine all entered authors
            $auth_arr = explode(",", $post["life_poc"]);
            for ($i = 0, $iMax = count($auth_arr); $i < $iMax; $i++) {
                $auth_arr[$i] = trim($auth_arr[$i]);
            }

            $md_con_author = "";

            // update existing author entries (delete if not entered)
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "PointOfContact") {
                    foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);

                        // entered author already exists
                        if (in_array($md_ent->getEntity(), $auth_arr, true)) {
                            unset($auth_arr[array_search($md_ent->getEntity(), $auth_arr, true)]);
                        } else {  // existing author has not been entered again -> delete
                            $md_ent->delete();
                        }
                    }
                    $md_con_author = $md_con;
                }
            }

            // insert enterd, but not existing authors
            if (count($auth_arr) > 0) {
                if (!is_object($md_con_author)) {
                    $md_con_author = $this->md_section->addContribute();
                    $md_con_author->setRole("PointOfContact");
                    $md_con_author->save();
                }
                foreach ($auth_arr as $auth) {
                    $md_ent = $md_con_author->addEntity();
                    $md_ent->setEntity(ilUtil::stripSlashes($auth));
                    $md_ent->save();
                }
            }
        } elseif (is_object($this->md_section = $this->md_obj->getLifecycle())) {
            foreach (($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() === "PointOfContact") {
                    $md_con->delete();
                }
            }
        }

        $this->md_section = $this->md_obj->getLifecycle();
        $this->md_section->setVersionLanguage(new ilMDLanguageItem($post['lif_language']));
        $this->md_section->setVersion(ilUtil::stripSlashes($post['lif_version']));
        $this->md_section->setStatus($post['lif_status']);
        $this->md_section->update();

        $this->callListeners('Lifecycle');

        // Redirect here to read new title and description
        // Otherwise ('Lifecycle' 'technical' ...) simply call listSection()
        $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
        $this->ctrl->redirect($this, 'listSection');
    }

    public function deleteElement(): bool
    {
        $meta_path = '';
        if ($this->http->wrapper()->query()->has('meta_path')) {
            $meta_path = $this->http->wrapper()->query()->retrieve(
                'meta_path',
                $this->refinery->kindlyTo()->string()
            );
        }

        $meta_technical = 0;
        if ($this->http->wrapper()->query()->has('meta_technical')) {
            $meta_technical = $this->http->wrapper()->query()->retrieve(
                'meta_technical',
                $this->refinery->kindlyTo()->int()
            );
        }

        $md_element = ilMDFactory::_getInstance($meta_path, $this->initMetaIndexFromQuery(), $meta_technical);
        $md_element->delete();

        $this->listSection();

        return true;
    }

    public function listSection(): void
    {
        if ($this->md_obj->getObjType() === 'sahs' || $this->md_obj->getObjType() === 'sco') {
            $this->listQuickEdit_scorm();
        } else {
            $this->listQuickEdit();
        }
    }

    protected function getUniqueElement(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): ilMDBaseElement {
        $els = $root->getSubElementsByPath($path);
        if (count($els = $root->getSubElementsByPath($path)) < 1) {
            throw new ilMDGUIException(
                'The path to the to be deleted' .
                ' element does not lead to an element.'
            );
        }
        if (count($els = $root->getSubElementsByPath($path)) > 1) {
            throw new ilMDGUIException(
                'The path to the to be deleted' .
                ' element leads to multiple element.'
            );
        }
        return $els[0];
    }

    protected function fullEditorCreate(): void
    {
    }

    protected function fullEditorUpdate(): void
    {
        // get the paths from the http request
        $node_path = $this->getNodePathFromRequest();
        $update_path = $this->getActionPathFromRequest();

        // get and prepare the MD
        $root = $this->repo->getMD();
        $editor = $this->getFullEditor();
        $root = $editor->manipulateMD()->prepare($root, $update_path);

        // update
        $request = $this->http->request();
        $success = $editor->manipulateMD()->update(
            $root,
            $node_path,
            $update_path,
            $request
        );
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
            $this->lng->txt('element_updated_successfully'),
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
        // get the paths from the http request
        $node_path = $this->getNodePathFromRequest();
        $delete_path = $this->getActionPathFromRequest();

        // get the MD
        $editor = $this->getFullEditor();
        $root = $this->repo->getMD();

        // delete
        $trim_path = $editor->manipulateMD()->delete(
            $root,
            $node_path,
            $delete_path
        );

        // call listeners
        $this->callListenersFullEditor($delete_path);

        // trim the node path if it leads only to the deleted element
        if ($trim_path) {
            $node_path->removeLastStep();
        }

        // redirect back to the full editor
        $this->tpl->setOnScreenMessage(
            'success',
            $this->lng->txt('element_deleted_successfully'),
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
        $this->tpl->addJavaScript(
            'Services/MetaData/js/ilMetaModalFormButtonHandler.js'
        );

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

        // add content for element
        $path = $this->getNodePathFromRequest();

        $editor = $this->getFullEditor();
        $root = $editor->manipulateMD()->prepare($root, $path);
        $create_modals = $editor->getCreateModals(
            $root,
            $path,
            $request,
            $path_for_request
        );
        $create_signals = array_map(
            fn ($arg) => $arg->getShowSignal(),
            $create_modals
        );
        $update_modals = $editor->getUpdateModals(
            $root,
            $path,
            $request,
            $path_for_request
        );
        $update_signals = array_map(
            fn ($arg) => $arg->getShowSignal(),
            $update_modals
        );
        $delete_modals = $editor->getDeleteModals($root, $path);
        $delete_signals = array_map(
            fn ($arg) => $arg->getShowSignal(),
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
                    array_values($create_modals),
                    array_values($update_modals),
                    array_values($delete_modals)
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
            $this->ctrl->getLinkTarget($this, 'listSection')
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
                $this->lng->txt('button_to_full_editor_label'),
                'medium'
            ),
            $this->lng->txt('button_to_full_editor_label'),
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

    public function __showLanguageSelect(string $a_name, string $a_value = ""): string
    {
        $tpl = new ilTemplate(
            "tpl.lang_selection.html",
            true,
            true,
            "Services/MetaData"
        );

        foreach (ilMDLanguageItem::_getLanguages() as $code => $text) {
            $tpl->setCurrentBlock("lg_option");
            $tpl->setVariable("VAL_LG", $code);
            $tpl->setVariable("TXT_LG", $text);

            if ($a_value !== "" && $a_value === $code) {
                $tpl->setVariable("SELECTED", "selected");
            }

            $tpl->parseCurrentBlock();
        }
        $tpl->setVariable("TXT_PLEASE_SELECT", $this->lng->txt("meta_please_select"));
        $tpl->setVariable("SEL_NAME", $a_name);

        $return = $tpl->get();
        unset($tpl);

        return $return;
    }

    public function __buildMonthsSelect(string $sel_month): string
    {
        $options = [];
        for ($i = 0; $i <= 24; $i++) {
            $options[$i] = sprintf('%02d', $i);
        }
        return ilLegacyFormElementsUtil::formSelect($sel_month, 'tlt[mo]', $options, false, true);
    }

    public function __buildDaysSelect(string $sel_day): string
    {
        $options = [];
        for ($i = 0; $i <= 31; $i++) {
            $options[$i] = sprintf('%02d', $i);
        }
        return ilLegacyFormElementsUtil::formSelect($sel_day, 'tlt[d]', $options, false, true);
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
