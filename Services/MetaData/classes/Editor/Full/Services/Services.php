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

namespace ILIAS\MetaData\Editor\Full\Services;

use ILIAS\Data\URI;
use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;
use ILIAS\Refinery\Factory as Refinery;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Services
{
    protected ilMDFullEditorActionProvider $action_provider;
    protected ilMDFullEditorInputProvider $input_provider;
    protected PropertiesFetcher $prop_provider;
    protected ilMDFullEditorFormProvider $form_provider;
    protected ilMDFullEditorTableProvider $table_provider;
    protected ilMDFullEditorDataFinder $data_finder;
    protected ilMDFullEditorMDManipulator $manipulator;

    public function __construct(
        URI $base_link,
        Factory $factory,
        Renderer $renderer,
        Refinery $refinery,
        ilMDRepository $repo,
        ilMDLOMPresenter $presenter,
        ilMDLOMLibrary $library,
        ilMDPathFactory $path_factory,
        ilMDMarkerFactory $marker_factory
    ) {
        $this->data_finder = new ilMDFullEditorDataFinder(
            $library->getLOMDictionary()
        );
        $this->prop_provider = new PropertiesFetcher(
            $library->getLOMEditorGUIDictionary($path_factory),
            $presenter,
            $this->data_finder
        );
        $this->action_provider = new ilMDFullEditorActionProvider(
            $link_provider = new ilMDFullEditorActionLinkProvider(
                $base_link
            ),
            new ilMDFullEditorActionButtonProvider(
                $factory,
                $presenter
            ),
            new ilMDFullEditorActionModalProvider(
                $link_provider,
                $factory,
                $presenter,
                $this->prop_provider
            )
        );
        $this->input_provider = new ilMDFullEditorInputProvider(
            $factory->input()->field(),
            $refinery,
            $presenter,
            $library->getLOMVocabulariesDictionary(
                $path_factory
            ),
            $library->getLOMConstraintDictionary(),
            $library->getLOMEditorGUIDictionary(
                $path_factory
            ),
            $this->data_finder,
            $library->getLOMDatabaseDictionary(null)
        );
        $this->form_provider = new ilMDFullEditorFormProvider(
            $factory,
            $this->action_provider,
            $this->input_provider,
            $library->getLOMEditorGUIDictionary(
                $path_factory
            )
        );
        $this->table_provider = new ilMDFullEditorTableProvider(
            $factory,
            $renderer,
            $presenter,
            $this->data_finder
        );
        $this->manipulator = new ilMDFullEditorMDManipulator(
            $repo,
            $this->form_provider,
            $marker_factory,
            $path_factory,
            $library->getLOMVocabulariesDictionary(
                $path_factory
            )
        );
    }

    public function dataFinder(): ilMDFullEditorDataFinder
    {
        return $this->data_finder;
    }

    public function inputProvider(): ilMDFullEditorInputProvider
    {
        return $this->input_provider;
    }

    public function propertiesProvider(): PropertiesFetcher
    {
        return $this->prop_provider;
    }

    public function actionProvider(): ilMDFullEditorActionProvider
    {
        return $this->action_provider;
    }

    public function formProvider(): ilMDFullEditorFormProvider
    {
        return $this->form_provider;
    }

    public function tableProvider(): ilMDFullEditorTableProvider
    {
        return $this->table_provider;
    }

    public function manipulator(): ilMDFullEditorMDManipulator
    {
        return $this->manipulator;
    }
}
