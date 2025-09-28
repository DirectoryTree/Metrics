<?php

namespace Phiki\Transformers\Decorations;

use Phiki\Phast\ClassList;

class LineDecoration
{
    /**
     * @param  int | array<int>  $line
     */
    public function __construct(
        public int|array $line,
        public ClassList $classes,
    ) {}

    public static function forLine(int $line): self
    {
        return new self($line, new ClassList);
    }

    public function class(string ...$classes): self
    {
        $this->classes->add(...$classes);

        return $this;
    }

    public function appliesToLine(int $line): bool
    {
        return $this->line === $line || (is_array($this->line) && $line >= $this->line[0] && $line <= $this->line[1]);
    }
}
