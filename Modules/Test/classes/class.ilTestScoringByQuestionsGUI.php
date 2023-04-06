<?php

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

require_once 'Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * ilTestScoringByQuestionsGUI
 * @author     Michael Jansen <mjansen@databay.de>
 * @author     Björn Heyser <bheyser@databay.de>
 * @version    $Id$
 * @ingroup    ModulesTest
 * @extends    ilTestServiceGUI
 */
class ilTestScoringByQuestionsGUI extends ilTestScoringGUI
{
    public const ONLY_FINALIZED = 1;
    public const EXCEPT_FINALIZED = 2;

    /**
     * @param ilObjTest $a_object
     */
    public function __construct(ilObjTest $a_object)
    {
        parent::__construct($a_object);
    }

    /**
     * @return string
     */
    protected function getDefaultCommand(): string
    {
        return 'showManScoringByQuestionParticipantsTable';
    }

    /**
     * @return string
     */
    protected function getActiveSubTabId(): string
    {
        return 'man_scoring_by_qst';
    }

    /**
     * @param array $manPointsPost
     */
    protected function showManScoringByQuestionParticipantsTable($manPointsPost = array())
    {
        global $DIC;

        $tpl = $DIC->ui()->mainTemplate();

        $DIC->tabs()->activateTab(ilTestTabsManager::TAB_ID_MANUAL_SCORING);

        if (!$this->testAccess->checkScoreParticipantsAccess()) {
            $this->tpl->setOnScreenMessage('info', $this->lng->txt('cannot_edit_test'), true);
            $this->ctrl->redirectByClass('ilobjtestgui', 'infoScreen');
        }

        iljQueryUtil::initjQuery();
        ilYuiUtil::initPanel();
        ilYuiUtil::initOverlay();

        $mathJaxSetting = new ilSetting('MathJax');

        if ($mathJaxSetting->get("enable")) {
            $tpl->addJavaScript($mathJaxSetting->get("path_to_mathjax"));
        }

        $tpl->addJavaScript("./Services/JavaScript/js/Basic.js");
        $tpl->addJavaScript("./Services/Form/js/Form.js");
        $tpl->addJavascript('./Services/UIComponent/Modal/js/Modal.js');
        $this->lng->toJSMap(['answer' => $this->lng->txt('answer')]);

        $table = new ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI($this);

        $qst_id = (int) $table->getFilterItemByPostVar('question')->getValue();
        $passNr = $table->getFilterItemByPostVar('pass')->getValue();
        $finalized_filter = $table->getFilterItemByPostVar('finalize_evaluation')->getValue();
        $answered_filter = $table->getFilterItemByPostVar('only_answered')->getChecked();
        $table_data = [];
        $selected_questionData = null;
        $complete_feedback = $this->object->getCompleteManualFeedback($qst_id);

        if (is_numeric($qst_id)) {
            $info = assQuestion::_getQuestionInfo($qst_id);
            $selected_questionData = $info;
        }

        if ($selected_questionData && is_numeric($passNr)) {
            $data = $this->object->getCompleteEvaluationData(false);
            $participants = $data->getParticipants();
            $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
            $participantData->setActiveIdsFilter(array_keys($data->getParticipants()));
            $participantData->setParticipantAccessFilter(
                ilTestParticipantAccessFilter::getScoreParticipantsUserFilter($this->ref_id)
            );
            $participantData->load($this->object->getTestId());

            foreach ($participantData->getActiveIds() as $active_id) {
                $participant = $participants[$active_id];
                $testResultData = $this->object->getTestResult($active_id, $passNr - 1);

                foreach ($testResultData as $questionData) {
                    $feedback = [];
                    $is_answered = (bool) ($questionData['answered'] ?? false);
                    $finalized_evaluation = (bool) ($questionData['finalized_evaluation'] ?? false);

                    if (isset($complete_feedback[$active_id][$passNr - 1][$qst_id])) {
                        $feedback = $complete_feedback[$active_id][$passNr - 1][$qst_id];
                    }

                    $check_filter =
                        ($finalized_filter != self::ONLY_FINALIZED || $finalized_evaluation) &&
                        ($finalized_filter != self::EXCEPT_FINALIZED || !$finalized_evaluation);

                    $check_answered = $answered_filter == false || $is_answered;

                    if (
                        isset($questionData['qid']) &&
                        $questionData['qid'] == $selected_questionData['question_id'] &&
                        $check_filter &&
                        $check_answered
                    ) {
                        $table_data[] = [
                            'pass_id' => $passNr - 1,
                            'active_id' => $active_id,
                            'qst_id' => $questionData['qid'],
                            'reached_points' => assQuestion::_getReachedPoints($active_id, $questionData['qid'], $passNr - 1),
                            'maximum_points' => assQuestion::_getMaximumPoints($questionData['qid']),
                            'name' => $participant->getName()
                        ] + $feedback;
                    }
                }
            }
        } else {
            $table->disable('header');
        }

        $table->setTitle($this->lng->txt('tst_man_scoring_by_qst'));

        if ($selected_questionData) {
            $maxpoints = assQuestion::_getMaximumPoints($selected_questionData['question_id']);
            $table->setCurQuestionMaxPoints($maxpoints);
            $maxpoints = ' (' . $maxpoints . ' ' . $this->lng->txt('points') . ')';
            if ($maxpoints == 1) {
                $maxpoints = ' (' . $maxpoints . ' ' . $this->lng->txt('point') . ')';
            }

            $table->setTitle(
                $this->lng->txt('tst_man_scoring_by_qst') . ': ' . $selected_questionData['title'] . $maxpoints .
                ' [' . $this->lng->txt('question_id_short') . ': ' . $selected_questionData['question_id'] . ']'
            );
        }

        $table->setData($table_data);
        $tpl->setContent($table->getHTML());
    }

    protected function saveManScoringByQuestion(bool $ajax = false): void
    {
        global $DIC;
        $ilAccess = $DIC->access();

        if (
            false === $ilAccess->checkAccess("write", "", $this->ref_id) &&
            false === $ilAccess->checkAccess("man_scoring_access", "", $this->ref_id)
        ) {
            if ($ajax) {
                echo $this->lng->txt('cannot_edit_test');
                exit();
            }

            $this->tpl->setOnScreenMessage('info', $this->lng->txt('cannot_edit_test'), true);
            $this->ctrl->redirectByClass('ilobjtestgui', 'infoScreen');
        }

        if (!isset($_POST['scoring']) || !is_array($_POST['scoring'])) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('tst_save_manscoring_failed_unknown'));
            $this->showManScoringByQuestionParticipantsTable();
            return;
        }

        $pass = key($_POST['scoring']);
        $activeData = current($_POST['scoring']);
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        $manPointsPost = [];
        $skipParticipant = [];
        $maxPointsByQuestionId = [];

        $participantData->setActiveIdsFilter(array_keys($activeData));
        $participantData->setParticipantAccessFilter(
            ilTestParticipantAccessFilter::getScoreParticipantsUserFilter($this->ref_id)
        );
        $participantData->load($this->object->getTestId());

        foreach ($participantData->getActiveIds() as $active_id) {
            $questions = $activeData[$active_id];

            // check for existing test result data
            if (!$this->object->getTestResult($active_id, $pass)) {
                if (!isset($skipParticipant[$pass])) {
                    $skipParticipant[$pass] = [];
                }
                $skipParticipant[$pass][$active_id] = true;

                continue;
            }

            foreach ((array) $questions as $qst_id => $reached_points) {
                if (!isset($manPointsPost[$pass])) {
                    $manPointsPost[$pass] = [];
                }
                if (!isset($manPointsPost[$pass][$active_id])) {
                    $manPointsPost[$pass][$active_id] = [];
                }
                $maxPointsByQuestionId[$qst_id] = assQuestion::_getMaximumPoints($qst_id);
                $manPointsPost[$pass][$active_id][$qst_id] = $reached_points;
                if ($reached_points > $maxPointsByQuestionId[$qst_id]) {
                    $this->tpl->setOnScreenMessage('failure', sprintf($this->lng->txt('tst_save_manscoring_failed'), $pass + 1), false);
                    $this->showManScoringByQuestionParticipantsTable($manPointsPost);
                    return;
                }
            }
        }

        $changed_one = false;
        $lastAndHopefullyCurrentQuestionId = null;

        foreach ($participantData->getActiveIds() as $active_id) {
            $questions = $activeData[$active_id];
            $update_participant = false;
            $qst_id = null;

            if (!($skipParticipant[$pass][$active_id] ?? false)) {
                foreach ((array) $questions as $qst_id => $reached_points) {
                    $this->saveFeedback((int) $active_id, (int) $qst_id, (int) $pass, $ajax);
                    // fix #35543: save manual points only if they differ from the existing points
                    // this prevents a question being set to "answered" if only feedback is entered
                    $old_points = assQuestion::_getReachedPoints($active_id, $qst_id, $pass);
                    if ($reached_points != $old_points) {
                        $update_participant = assQuestion::_setReachedPoints(
                            $active_id,
                            $qst_id,
                            $reached_points,
                            $maxPointsByQuestionId[$qst_id],
                            $pass,
                            true,
                            $this->object->areObligationsEnabled()
                        );
                    }
                }

                if ($update_participant) {
                    ilLPStatusWrapper::_updateStatus(
                        $this->object->getId(),
                        ilObjTestAccess::_getParticipantId($active_id)
                    );
                }

                $changed_one = true;
                $lastAndHopefullyCurrentQuestionId = $qst_id;
            }
        }

        $correction_feedback = [];
        $correction_points = 0;

        if ($changed_one) {
            $qTitle = '';

            if ($lastAndHopefullyCurrentQuestionId) {
                $question = assQuestion::_instantiateQuestion($lastAndHopefullyCurrentQuestionId);
                $qTitle = $question->getTitle();
            }

            $msg = sprintf(
                $this->lng->txt('tst_saved_manscoring_by_question_successfully'),
                $qTitle,
                $pass + 1
            );

            $this->tpl->setOnScreenMessage('success', $msg, true);

            if (isset($active_id) && $lastAndHopefullyCurrentQuestionId) {
                $correction_feedback = ilObjTest::getSingleManualFeedback(
                    (int) $active_id,
                    (int) $lastAndHopefullyCurrentQuestionId,
                    (int) $pass
                );
                $correction_points = assQuestion::_getReachedPoints($active_id, $lastAndHopefullyCurrentQuestionId, $pass);
            }
        }

        if ($ajax && is_array($correction_feedback)) {
            $finalized_by_usr_id = $correction_feedback['finalized_by_usr_id'];
            if (! $finalized_by_usr_id) {
                $finalized_by_usr_id = $DIC['ilUser']->getId();
            }
            $correction_feedback['finalized_by'] = ilObjUser::_lookupFullname($finalized_by_usr_id);
            $correction_feedback['finalized_on_date'] = '';

            if (strlen($correction_feedback['finalized_tstamp']) > 0) {
                $time = new ilDateTime($correction_feedback['finalized_tstamp'], IL_CAL_UNIX);
                $correction_feedback['finalized_on_date'] = $time->get(IL_CAL_DATETIME);
            }

            if (!$correction_feedback['feedback']) {
                $correction_feedback['feedback'] = [];
            }
            if ($correction_feedback['finalized_evaluation'] == 1) {
                $correction_feedback['finalized_evaluation'] = $this->lng->txt('yes');
            } else {
                $correction_feedback['finalized_evaluation'] = $this->lng->txt('no');
            }

            echo json_encode([ 'feedback' => $correction_feedback, 'points' => $correction_points, "translation" => ['yes' => $this->lng->txt('yes'), 'no' => $this->lng->txt('no')]]);
            exit();
        }

        $this->showManScoringByQuestionParticipantsTable();
    }

    /**
     *
     */
    protected function applyManScoringByQuestionFilter()
    {
        $table = new ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI($this);
        $table->resetOffset();
        $table->writeFilterToSession();
        $this->showManScoringByQuestionParticipantsTable();
    }

    /**
     *
     */
    protected function resetManScoringByQuestionFilter()
    {
        $table = new ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI($this);
        $table->resetOffset();
        $table->resetFilter();
        $this->showManScoringByQuestionParticipantsTable();
    }

    protected function getAnswerDetail()
    {
        $active_id = $this->testrequest->getActiveId();
        $pass = $this->testrequest->getPassId();
        $question_id = (int) $this->testrequest->raw('qst_id');

        if (!$this->getTestAccess()->checkScoreParticipantsAccessForActiveId($active_id)) {
            exit; // illegal ajax call
        }

        $data = $this->object->getCompleteEvaluationData(false);
        $participant = $data->getParticipant($active_id);
        $question_gui = $this->object->createQuestionGUI('', $question_id);
        $tmp_tpl = new ilTemplate('tpl.il_as_tst_correct_solution_output.html', true, true, 'Modules/Test');
        if ($question_gui instanceof assTextQuestionGUI && $this->object->getAutosave()) {
            $aresult_output = $question_gui->getAutoSavedSolutionOutput(
                $active_id,
                $pass,
                false,
                false,
                false,
                $this->object->getShowSolutionFeedback(),
                false,
                true
            );
            $tmp_tpl->setVariable('TEXT_ASOLUTION_OUTPUT', $this->lng->txt('autosavecontent'));
            $tmp_tpl->setVariable('ASOLUTION_OUTPUT', $aresult_output);
        }
        $result_output = $question_gui->getSolutionOutput(
            $active_id,
            $pass,
            false,
            false,
            false,
            $this->object->getShowSolutionFeedback(),
            false,
            true
        );
        $max_points = $question_gui->object->getMaximumPoints();

        $this->appendUserNameToModal($tmp_tpl, $participant);
        $this->appendQuestionTitleToModal($tmp_tpl, $question_id, $max_points, $question_gui->object->getTitle());
        $this->appendSolutionAndPointsToModal(
            $tmp_tpl,
            $result_output,
            $question_gui->object->getReachedPoints($active_id, $pass),
            $max_points
        );
        $this->appendFormToModal($tmp_tpl, $pass, $active_id, $question_id, $max_points);
        $tmp_tpl->setVariable('TEXT_YOUR_SOLUTION', $this->lng->txt('answers_of') . ' ' . $participant->getName());
        $suggested_solution = assQuestion::_getSuggestedSolutionOutput($question_id);
        if ($this->object->getShowSolutionSuggested() && strlen($suggested_solution) > 0) {
            $tmp_tpl->setVariable('TEXT_SOLUTION_HINT', $this->lng->txt("solution_hint"));
            $tmp_tpl->setVariable("SOLUTION_HINT", assQuestion::_getSuggestedSolutionOutput($question_id));
        }

        $tmp_tpl->setVariable('TEXT_SOLUTION_OUTPUT', $this->lng->txt('question'));
        $tmp_tpl->setVariable('TEXT_RECEIVED_POINTS', $this->lng->txt('scoring'));
        $add_title = ' [' . $this->lng->txt('question_id_short') . ': ' . $question_id . ']';
        $question_title = $this->object->getQuestionTitle($question_gui->object->getTitle());
        $lng = $this->lng->txt('points');
        if ($max_points == 1) {
            $lng = $this->lng->txt('point');
        }

        $tmp_tpl->setVariable(
            'QUESTION_TITLE',
            $question_title . ' (' . $max_points . ' ' . $lng . ')' . $add_title
        );
        $tmp_tpl->setVariable('SOLUTION_OUTPUT', $result_output);

        $tmp_tpl->setVariable(
            'RECEIVED_POINTS',
            sprintf(
                $this->lng->txt('part_received_a_of_b_points'),
                $question_gui->object->getReachedPoints($active_id, $pass),
                $max_points
            )
        );

        echo $tmp_tpl->get();
        exit();
    }

    /**
     *
     */
    public function checkConstraintsBeforeSaving()
    {
        $this->saveManScoringByQuestion(true);
    }

    /**
     * @param ilTemplate $tmp_tpl
     * @param $participant
     */
    private function appendUserNameToModal($tmp_tpl, $participant)
    {
        global $DIC;
        $ilAccess = $DIC->access();

        $tmp_tpl->setVariable(
            'TEXT_YOUR_SOLUTION',
            $this->lng->txt('answers_of') . ' ' . $participant->getName()
        );

        if (
            $this->object->anonymity == 1 ||
            ($this->object->getAnonymity() == 2 && !$ilAccess->checkAccess('write', '', $this->object->getRefId()))
        ) {
            $tmp_tpl->setVariable(
                'TEXT_YOUR_SOLUTION',
                $this->lng->txt('answers_of') . ' ' . $this->lng->txt('anonymous')
            );
        }
    }

    /**
     * @param ilTemplate $tmp_tpl
     * @param $question_id
     * @param $max_points
     * @param $title
     */
    private function appendQuestionTitleToModal($tmp_tpl, $question_id, $max_points, $title)
    {
        $add_title = ' [' . $this->lng->txt('question_id_short') . ': ' . $question_id . ']';
        $question_title = $this->object->getQuestionTitle($title);
        $lng = $this->lng->txt('points');
        if ($max_points == 1) {
            $lng = $this->lng->txt('point');
        }

        $tmp_tpl->setVariable(
            'QUESTION_TITLE',
            $question_title . ' (' . $max_points . ' ' . $lng . ')' . $add_title
        );
    }

    /**
     * @param ilTemplate $tmp_tpl
     * @param $pass
     * @param $active_id
     * @param $question_id
     * @param $max_points
     */
    private function appendFormToModal($tmp_tpl, $pass, $active_id, $question_id, $max_points)
    {
        global $DIC;

        $ilCtrl = $DIC->ctrl();
        $post_var = '[' . $pass . '][' . $active_id . '][' . $question_id . ']';
        $scoring_post_var = 'scoring' . $post_var;
        $reached_points = assQuestion::_getReachedPoints($active_id, $question_id, $pass);
        $form = new ilPropertyFormGUI();
        $feedback = ilObjTest::getSingleManualFeedback((int) $active_id, (int) $question_id, (int) $pass);
        $disable = false;
        $form->setFormAction($ilCtrl->getFormAction($this, 'showManScoringByQuestionParticipantsTable'));
        $form->setTitle($this->lng->txt('manscoring'));

        if (isset($feedback['finalized_evaluation']) && $feedback['finalized_evaluation'] == 1) {
            $disable = true;
            $hidden_points = new ilHiddenInputGUI($scoring_post_var);
            $scoring_post_var = $scoring_post_var . '_disabled';
            $hidden_points->setValue($reached_points);
            $form->addItem($hidden_points);
        }

        $tmp_tpl->setVariable('TINYMCE_ACTIVE', ilObjAdvancedEditing::_getRichTextEditor());
        $text_area = new ilTextAreaInputGUI($this->lng->txt('set_manual_feedback'), 'm_feedback' . $post_var);
        $feedback_text = '';
        if (array_key_exists('feedback', $feedback)) {
            $feedback_text = $feedback['feedback'];
        }
        $text_area->setDisabled($disable);
        $text_area->setValue($feedback_text);
        $form->addItem($text_area);

        $reached_points_form = new ilNumberInputGUI($this->lng->txt('tst_change_points_for_question'), $scoring_post_var);
        $reached_points_form->allowDecimals(true);
        $reached_points_form->setSize(5);
        $reached_points_form->setMaxValue($max_points, true);
        $reached_points_form->setMinValue(0);
        $reached_points_form->setDisabled($disable);
        $reached_points_form->setValue($reached_points);
        $reached_points_form->setClientSideValidation(true);
        $form->addItem($reached_points_form);

        $hidden_points = new ilHiddenInputGUI('qst_max_points');
        $hidden_points->setValue($max_points);
        $form->addItem($hidden_points);

        $hidden_points_name = new ilHiddenInputGUI('qst_hidden_points_name');
        $hidden_points_name->setValue('scoring' . $post_var);
        $form->addItem($hidden_points_name);

        $hidden_feedback_name = new ilHiddenInputGUI('qst_hidden_feedback_name');
        $hidden_feedback_name->setValue('m_feedback' . $post_var);
        $form->addItem($hidden_feedback_name);

        $hidden_feedback_id = new ilHiddenInputGUI('qst_hidden_feedback_id');
        $post_id = '__' . $pass . '____' . $active_id . '____' . $question_id . '__';
        $hidden_feedback_id->setValue('m_feedback' . $post_id);
        $form->addItem($hidden_feedback_id);

        $evaluated = new ilCheckboxInputGUI($this->lng->txt('finalized_evaluation'), 'evaluated' . $post_var);
        if (isset($feedback['finalized_evaluation']) && $feedback['finalized_evaluation'] == 1) {
            $evaluated->setChecked(true);
        }
        $form->addItem($evaluated);

        $form->addCommandButton('checkConstraintsBeforeSaving', $this->lng->txt('save'));

        $tmp_tpl->setVariable(
            'MANUAL_FEEDBACK',
            $form->getHTML()
        );
        $tmp_tpl->setVariable(
            'MODAL_AJAX_URL',
            $this->ctrl->getLinkTarget($this, 'checkConstraintsBeforeSaving', '', true, false)
        );
        $tmp_tpl->setVariable(
            'INFO_TEXT_MAX_POINTS_EXCEEDS',
            sprintf($this->lng->txt('tst_manscoring_maxpoints_exceeded_input_alert'), $max_points)
        );
    }

    /**
     * @param ilTemplate $tmp_tpl
     * @param $result_output
     * @param $reached_points
     * @param $max_points
     */
    private function appendSolutionAndPointsToModal($tmp_tpl, $result_output, $reached_points, $max_points)
    {
        $tmp_tpl->setVariable(
            'SOLUTION_OUTPUT',
            $result_output
        );
        $tmp_tpl->setVariable(
            'RECEIVED_POINTS',
            sprintf(
                $this->lng->txt('part_received_a_of_b_points'),
                $reached_points,
                $max_points
            )
        );
    }

    protected function saveFeedback(int $active_id, int $qst_id, int $pass, bool $is_single_feedback): void
    {
        $feedback = null;
        if ($this->doesValueExistsInPostArray('feedback', $active_id, $qst_id, $pass)) {
            $feedback = ilUtil::stripSlashes($_POST['feedback'][$pass][$active_id][$qst_id]);
        } elseif ($this->doesValueExistsInPostArray('m_feedback', $active_id, $qst_id, $pass)) {
            $feedback = ilUtil::stripSlashes($_POST['m_feedback'][$pass][$active_id][$qst_id]);
        }
        $this->saveFinalization($active_id, $qst_id, $pass, $feedback, $is_single_feedback);
    }

    protected function saveFinalization(
        int $active_id,
        int $qst_id,
        int $pass,
        ?string $feedback,
        bool $is_single_feedback
    ): void {
        $evaluated = false;
        if ($this->doesValueExistsInPostArray('evaluated', $active_id, $qst_id, $pass)) {
            $evaluated = (bool) ($_POST['evaluated'][$pass][$active_id][$qst_id] ?? false);
        }

        $this->object->saveManualFeedback(
            $active_id,
            $qst_id,
            $pass,
            $feedback,
            $evaluated,
            $is_single_feedback
        );
    }
    /**
     * @param $post_value
     * @param $active_id
     * @param $qst_id
     * @param $pass
     * @return bool
     */
    protected function doesValueExistsInPostArray($post_value, $active_id, $qst_id, $pass): bool
    {
        return (
            isset($_POST[$post_value][$pass][$active_id][$qst_id]) &&
            $_POST[$post_value][$pass][$active_id][$qst_id] != ''
        );
    }
}
