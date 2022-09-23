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

use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Component\Input\Factory as InputFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\Refinery\Factory as RefineryFactory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDigestGUI
{
    protected InputFactory $factory;
    protected RefineryFactory $refinery;
    protected ilLanguage $lng;

    public function __construct(
        InputFactory $factory,
        RefineryFactory $refinery,
        ilLanguage $lng
    ) {
        $this->factory = $factory;
        $this->refinery = $refinery;
        $this->lng = $lng;
    }

    public function getForm(
        ilMDRootElement $root,
        string $post_url,
        ?Request $request = null
    ): StandardForm {
        /**
         * TODO figure out what to do about multiple descriptions/languages
         */
        $ff = $this->factory->field();
        $root = $this->prepareMD($root);

        // section general
        $el_gen = $root->getSubElements('general')[0] ?? null;
        $el_title = $el_gen->getSubElements('title')[0] ?? null;
        $title = $ff
            ->text(
                $this->lng->txt('title_txt')
            );
        $description = $ff
            ->textarea(
                $this->lng->txt('description_txt')
            );
        $language = $ff
            ->select(
                $this->lng->txt('language_txt'),
                array_combine(
                    ilMDLOMDataFactory::LANGUAGES,
                    array_map(
                        fn (string $arg) => $arg . '_txt',
                        ilMDLOMDataFactory::LANGUAGES
                    )
                )
            );
        $keywords = $ff
            ->tag(
                $this->lng->txt('keywords_txt'),
                []
            );

        $sec_general = $ff
            ->section(
                [
                    'title' => $title,
                    'description' => $description,
                    'language' => $language,
                    'keywords' => $keywords
                ],
                $this->lng->txt('general_txt')
            );

        //section authors
        $first = $ff
            ->text(
                $this->lng->txt('first_author_txt')
            );
        $second = $ff
            ->text(
                $this->lng->txt('second_author_txt')
            );
        $third = $ff
            ->text(
                $this->lng->txt('third_author_txt')
            );

        $sec_authors = $ff
            ->section(
                [
                    'first' => $first,
                    'second' => $second,
                    'third' => $third
                ],
                $this->lng->txt('authors_txt')
            );

        //section rights
        // TODO figure out what to do about this (saved in description)
        $sec_rights = $ff
            ->section(
                [],
                $this->lng->txt('rights_txt')
            );

        //section tlt
        $years = $ff
            ->numeric(
                $this->lng->txt('tlt_years_txt')
            )
            ->withAdditionalTransformation(
                $this->refinery->int()->isGreaterThan(0)
            );
        $months = (clone $years)
            ->withLabel(
                $this->lng->txt('tlt_months_txt')
            );
        $days = (clone $years)
            ->withLabel(
                $this->lng->txt('tlt_days_txt')
            );
        $hours = (clone $years)
            ->withLabel(
                $this->lng->txt('tlt_hours_txt')
            );
        $minutes = (clone $years)
            ->withLabel(
                $this->lng->txt('tlt_minutes_txt')
            );
        $seconds = (clone $years)
            ->withLabel(
                $this->lng->txt('tlt_seconds_txt')
            );

        $sec_tlt = $ff
            ->section(
                [
                    'years' => $years,
                    'months' => $months,
                    'days' => $days,
                    'hours' => $hours,
                    'minutes' => $minutes,
                    'seconds' => $seconds
                ],
                $this->lng->txt('typical_learning_time_txt')
            );

        // Assemble the form
        $form = $this->factory->container()->form()->standard(
            $post_url,
            [
                'general' => $sec_general,
                'authors' => $sec_authors,
                'rights' => $sec_rights,
                // TODO add this when educational is done in the db dictionary
                //'tlt' => $sec_tlt
            ]
        );

        if (isset($request)) {
            return $form->withRequest($request);
        }
        return $form;
    }

    protected function prepareMD(ilMDRootElement $root): ilMDRootElement
    {
        $root = clone $root;

        // TODO fill the holes in the MD with scaffolds

        return $root;
    }
}
