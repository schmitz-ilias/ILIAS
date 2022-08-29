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

use ILIAS\Filesystem\Filesystem;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;

/**
 * Class ilTermsOfServiceDocumentFormGUI
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilTermsOfServiceDocumentFormGUI extends ilPropertyFormGUI
{
    protected string $translatedError = '';
    protected string $translatedInfo = '';

    public function __construct(
        protected ilTermsOfServiceDocument $document,
        protected ilHtmlPurifierInterface $documentPurifier,
        protected ilObjUser $actor,
        protected Filesystem $tmpFileSystem,
        protected FileUpload $fileUpload,
        protected string $formAction = '',
        protected string $saveCommand = 'saveDocument',
        protected string $cancelCommand = 'showDocuments',
        protected bool $isEditable = false
    ) {
        parent::__construct();

        $this->initForm();
    }

    public function setCheckInputCalled(bool $status): void
    {
        $this->check_input_called = $status;
    }

    protected function initForm(): void
    {
        if ($this->document->getId() > 0) {
            $this->setTitle($this->lng->txt('tos_form_edit_doc_head'));
        } else {
            $this->setTitle($this->lng->txt('tos_form_new_doc_head'));
        }

        $this->setFormAction($this->formAction);

        $title = new ilTextInputGUI($this->lng->txt('tos_form_document_title'), 'title');
        $title->setInfo($this->lng->txt('tos_form_document_title_info'));
        $title->setRequired(true);
        $title->setDisabled(!$this->isEditable);
        $title->setValue($this->document->getTitle());
        $title->setMaxLength(255);
        $this->addItem($title);

        $documentLabel = $this->lng->txt('tos_form_document');
        $documentByline = $this->lng->txt('tos_form_document_info');
        if ($this->document->getId() > 0) {
            $documentLabel = $this->lng->txt('tos_form_document_new');
            $documentByline = $this->lng->txt('tos_form_document_new_info');
        }

        $document = new ilFileInputGUI($documentLabel, 'document');
        $document->setInfo($documentByline);
        if (!$this->document->getId()) {
            $document->setRequired(true);
        }
        $document->setDisabled(!$this->isEditable);
        $document->setSuffixes(['html', 'txt']);
        $this->addItem($document);

        if ($this->isEditable) {
            $this->addCommandButton($this->saveCommand, $this->lng->txt('save'));
        }

        $this->addCommandButton($this->cancelCommand, $this->lng->txt('cancel'));
    }

    public function hasTranslatedError(): bool
    {
        return $this->translatedError !== '';
    }

    public function getTranslatedError(): string
    {
        return $this->translatedError;
    }

    public function hasTranslatedInfo(): bool
    {
        return $this->translatedInfo !== '';
    }

    public function getTranslatedInfo(): string
    {
        return $this->translatedInfo;
    }

    public function saveObject(): bool
    {
        if (!$this->fillObject()) {
            $this->setValuesByPost();
            return false;
        }

        $this->document->save();

        return true;
    }

    protected function fillObject(): bool
    {
        if (!$this->checkInput()) {
            return false;
        }

        if ($this->fileUpload->hasUploads() && !$this->fileUpload->hasBeenProcessed()) {
            try {
                $this->fileUpload->process();

                /** @var UploadResult|null $uploadResult */
                $uploadResult = array_values($this->fileUpload->getResults())[0];
                if (!($uploadResult instanceof UploadResult)) {
                    $this->getItemByPostVar('document')->setAlert($this->lng->txt('form_msg_file_no_upload'));
                    throw new ilException($this->lng->txt('form_input_not_valid'));
                }

                if (!$this->document->getId() || $uploadResult->getName() !== '') {
                    if (!$uploadResult->isOK()) {
                        $this->getItemByPostVar('document')->setAlert($uploadResult->getStatus()->getMessage());
                        throw new ilException($this->lng->txt('form_input_not_valid'));
                    }

                    $this->fileUpload->moveOneFileTo(
                        $uploadResult,
                        '/agreements',
                        Location::TEMPORARY,
                        '',
                        true
                    );

                    $pathToFile = '/agreements/' . $uploadResult->getName();
                    if (!$this->tmpFileSystem->has($pathToFile)) {
                        $this->getItemByPostVar('document')->setAlert($this->lng->txt('form_msg_file_no_upload'));
                        throw new ilException($this->lng->txt('form_input_not_valid'));
                    }

                    $originalContent = $content = $this->tmpFileSystem->read($pathToFile);

                    $purifiedHtmlContent = $this->documentPurifier->purify($content);

                    $htmlValidator = new ilTermsOfServiceDocumentsContainsHtmlValidator($purifiedHtmlContent);
                    if (!$htmlValidator->isValid()) {
                        $purifiedHtmlContent = nl2br($purifiedHtmlContent);
                    }

                    if (trim($purifiedHtmlContent) !== trim($originalContent)) {
                        $this->translatedInfo = $this->lng->txt('tos_form_document_content_changed');
                    }

                    $this->document->setText($purifiedHtmlContent);
                    $this->tmpFileSystem->delete($pathToFile);
                }
            } catch (Exception $e) {
                $this->translatedError = $e->getMessage();
                return false;
            }
        }

        $this->document->setTitle($this->getInput('title'));

        if ($this->document->getId() > 0) {
            $this->document->setLastModifiedUsrId($this->actor->getId());
        } else {
            $this->document->setOwnerUsrId($this->actor->getId());

            $documentWithMaxSorting = ilTermsOfServiceDocument::orderBy('sorting', 'DESC')->limit(0, 1)->first();
            if ($documentWithMaxSorting instanceof ilTermsOfServiceDocument) {
                $this->document->setSorting((int) $documentWithMaxSorting->getSorting() + 1);
            } else {
                $this->document->setSorting(1);
            }
        }

        return true;
    }
}
