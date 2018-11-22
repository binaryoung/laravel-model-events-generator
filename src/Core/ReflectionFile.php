<?php

namespace Binaryoung\LaravelModelEventsGenerator\Core;

use ReflectionClass;
use InvalidArgumentException;

class ReflectionFile extends ReflectionClass
{
    public function __construct($file)
    {
        parent::__construct(
            $this->getClassByToken($file)
        );
    }

    /**
     * @param $file
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *
     * @see http://stackoverflow.com/questions/7153000/get-class-name-from-file
     */
    private function getClassByToken($file)
    {
        if (false === file_exists($file)) {
            throw new InvalidArgumentException(sprintf('File %s is not readable or not exist', $file));
        }

        $fp = fopen($file, 'r');

        $class = $namespace = $buffer = '';
        $i = 0;

        while (!$class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            list($namespace, $class) = $this->loopToken($i, $tokens, $namespace);
        }

        if ($namespace == '') {
            return $class;
        }

        return sprintf('%s\\%s', $namespace, $class);
    }

    /**
     * @param $i
     * @param $tokens
     * @param $namespace
     *
     * @return array
     */
    private function loopToken($i, $tokens, $namespace)
    {
        $class = '';

        for (; $i < count($tokens); ++$i) {
            if ($tokens[$i][0] === T_NAMESPACE && $namespace === '') {
                $namespace = $this->findNamespace($i, $tokens, $namespace);
            }

            if ($tokens[$i][0] === T_CLASS && $class === '') {
                $class = $this->findClassName($i, $tokens);
            }
        }

        return array($namespace, $class);
    }

    /**
     * @param $i
     * @param $tokens
     * @param $namespace
     *
     * @return array
     */
    private function findNamespace($i, $tokens, $namespace)
    {
        for ($j = $i + 1; $j < count($tokens); ++$j) {
            if ($this->tokenIsAString($tokens, $j)) {
                $namespace .= '\\'.$tokens[$j][1];
            }

            if ($this->endTokenForNamespace($tokens, $j)) {
                break;
            }
        }

        return $namespace;
    }

    /**
     * @param $i
     * @param $tokens
     *
     * @return mixed
     */
    private function findClassName($i, $tokens)
    {
        $class = '';

        for ($j = $i + 1; $j < count($tokens); ++$j) {
            if ($tokens[$j] === '{') {
                $class = $tokens[$i + 2][1];
            }
        }

        return $class;
    }

    /**
     * @param $tokens
     * @param $j
     *
     * @return bool
     */
    private function endTokenForNamespace($tokens, $j)
    {
        return $tokens[$j] === '{' || $tokens[$j] === ';';
    }

    /**
     * @param $tokens
     * @param $j
     *
     * @return bool
     */
    private function tokenIsAString($tokens, $j)
    {
        return $tokens[$j][0] === T_STRING;
    }
}
