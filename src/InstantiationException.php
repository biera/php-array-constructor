<?php declare(strict_types=1);

namespace Biera;

class InstantiationException extends \RuntimeException
{
    private string $path;
    private array $errors;

    public function __construct(string $path, array $errors = [], \Throwable $previous = null)
    {
        if (empty($errors) && !$previous instanceof InstantiationException) {
            throw new \LogicException(
                'Parameter "errors" must not be empty when "previous" is not of "Biera\InstantiationException" type'
            );
        }

        $this->path = $path;
        $this->errors = $errors;

        parent::__construct('', 0, $previous);
    }

    public function getPath(): string
    {
        $path = [$this->path];
        $previous = $this->getPrevious();

        while ($previous instanceof InstantiationException) {
            $path[] = $previous->getPath();
            $previous = $previous->getPrevious();
        }

        return \join(' -> ', $path);
    }

    public function getErrors(): array
    {
        $deepest = $this;

        while (($previous = $deepest->getPrevious()) instanceof InstantiationException) {
            $deepest = $previous;
        }

        return $deepest->errors;
    }
}
