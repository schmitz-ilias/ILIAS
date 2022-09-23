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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
abstract class ilMDPath
{
    public const SEPARATOR = ';';
    public const FILTER_OPEN = '{';
    public const FILTER_CLOSE = '}';
    public const ROOT = '@';
    public const SUPER_ELEMENT = '^';

    public const FILTER_INDEX = 'opt_index';

    protected string $path;

    /**
     * Add the name of an MD element as a step in the path.
     */
    public function addStep(string $step): self
    {
        $this->validateInput($step);
        $this->path .= self::SEPARATOR . $step;
        return $this;
    }

    /**
     * Specify which element of the name of the step should be on the path,
     * using an index starting at 1, sorted by their md_id.
     * If no index is specified, the path branches over all available elements.
     */
    public function addIndexFilter(int $index): self
    {
        $this->addFilter((string) $index);
        return $this;
    }

    public function getPathLength(): int
    {
        $path_array = explode(self::SEPARATOR, $this->path);
        return count($path_array);
    }

    /**
     * Returns the name of the final step in the path. If a
     * number n lower than the length of the path is given,
     * returns the name of the nth step instead, starting
     * with the start at 0.
     */
    public function getStep(?int $number = null): string
    {
        $path_array = explode(self::SEPARATOR, $this->path);
        $step = $path_array[array_key_last($path_array)];
        if (isset($number) && array_key_exists($number, $path_array)) {
            $step = $path_array[$number];
        }
        return (explode(self::FILTER_OPEN, $step))[0];
    }

    /**
     * Returns the index filters of the final step in the path. If a
     * number n lower than the length of the path is given,
     * returns the filters of the nth step instead, starting
     * with the root at 0.
     * @return int[]
     */
    public function getIndexFilter(?int $number = null): array
    {
        $path_array = explode(self::SEPARATOR, $this->path);
        $step = $path_array[array_key_last($path_array)];
        if (isset($number) && array_key_exists($number, $path_array)) {
            $step = $path_array[$number];
        }
        $exploded_step = explode(self::FILTER_OPEN, $step);
        array_shift($exploded_step);
        return array_map(
            fn (string $arg): int => (int) rtrim($arg, self::FILTER_CLOSE),
            $exploded_step
        );
    }

    public function isAtStart(): bool
    {
        return $this->getPathLength() === 1;
    }

    public function removeLastStep(): self
    {
        if ($this->isAtStart()) {
            throw new ilMDPathException(
                'No step in path to remove'
            );
        }
        $path_array = explode(self::SEPARATOR, $this->path);
        array_pop($path_array);
        $this->path = implode(self::SEPARATOR, $path_array);
        return $this;
    }

    /**
     * Returns the path as a string. Note that this should not be
     * used to store the path persistently, since the reserved characters
     * might change with demands on the MD.
     */
    public function getPathAsString(): string
    {
        return $this->path;
    }

    protected function addFilter(string $option): self
    {
        $this->validateInput($option);
        $this->path .= self::FILTER_OPEN . $option . self::FILTER_CLOSE;
        return $this;
    }

    /**
     * @throws ilMDPathException
     */
    protected function validateInput(string $input): void
    {
        if (!$input) {
            throw new ilMDPathException(
                'Input for path can not be empty.'
            );
        }
        $reserved_chars = [
            self::SEPARATOR,
            self::FILTER_OPEN,
            self::FILTER_CLOSE,
            self::ROOT,
            self::SUPER_ELEMENT
        ];
        $conflict = '';
        foreach ($reserved_chars as $char) {
            if (str_contains($input, $char)) {
                $conflict .= $char . ', ';
            }
        }
        if ($conflict) {
            $conflict = substr($conflict, 0, -2);
            throw new ilMDPathException(
                'Input for path matches the reserved character(s) ' .
                $conflict . '.'
            );
        }
    }
}
